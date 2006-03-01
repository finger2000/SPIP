<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_ecrire('inc_meta');
include_spip('inc/session');
include_spip('inc/acces');
include_spip('inc/texte');
include_spip('inc/lang');
include_spip('inc/mail');
include_spip('inc/forum');
include_ecrire("inc_abstract_sql");
include_local(_FILE_CONNECT);

charger_generer_url();

/*******************************/
/* GESTION DU FORMULAIRE FORUM */
/*******************************/

// Contexte du formulaire
// Mots-cles dans les forums :
// Si la variable de personnalisation $afficher_groupe[] est definie
// dans le fichier d'appel, et si la table de reference est OK, proposer
// la liste des mots-cles

global $balise_FORMULAIRE_FORUM_collecte;
$balise_FORMULAIRE_FORUM_collecte = array('id_rubrique', 'id_forum', 'id_article', 'id_breve', 'id_syndic', 'ajouter_mot', 'ajouter_groupe', 'afficher_texte');


// verification des droits a faire du forum
function balise_FORMULAIRE_FORUM_stat($args, $filtres) {

	// Note : ceci n'est pas documente !!
	// $filtres[0] peut contenir l'url sur lequel faire tourner le formulaire
	// exemple dans un squelette article.html : [(#FORMULAIRE_FORUM|forum)]

	// le denier arg peut contenir l'url sur lequel faire le retour
	// exemple dans un squelette article.html : [(#FORMULAIRE_FORUM{#SELF})]

	// recuperer les donnees du forum auquel on repond, false = forum interdit
	list ($idr, $idf, $ida, $idb, $ids, $am, $ag, $af, $url) = $args;
	$idr = intval($idr);
	$idf = intval($idf);
	$ida = intval($ida);
	$idb = intval($idb);
	$ids = intval($ids);
	if (!$r = sql_recherche_donnees_forum ($idr, $idf, $ida, $idb, $ids))
		return '';

	list ($titre, $table, $forums_publics) = $r;

	// Attention id_rubrique est passe pour les articles => on n'en veut pas
	if ($idr > 0 AND ($ida OR $idb OR $ids))
		$idr = 0;

	if (($GLOBALS['meta']["mots_cles_forums"] != "oui"))
		$table = '';

	// compatibilite: virer l'extension
	$script = preg_match(',.php3?$,', $filtres[0],$r) ? $r[1] : $filtres[0];
	return
		array($titre, $table, $forums_publics, $script,
		$idr, $idf, $ida, $idb, $ids, $am, $ag, $af, $url);
}

function balise_FORMULAIRE_FORUM_dyn(
$titre, $table, $type, $script,
$id_rubrique, $id_forum, $id_article, $id_breve, $id_syndic,
$ajouter_mot, $ajouter_groupe, $afficher_texte, $url_param_retour)
{
	// verifier l'identite des posteurs pour les forums sur abo
	if (($type == "abo") && (!$GLOBALS["auteur_session"])) {
		return array('formulaire_login_forum', 0,
			     array('inscription' => generer_url_public('spip_inscription'),
				   'oubli' => generer_url_public('spip_pass')));
	}

// exclure des id_* postees du formulaire tout ce qui n'est pas nombre > 0. 
// attention le calcul du hachage doit etre le meme ici et dans inc-messforum

	$ids = array();
	foreach (array('article', 'breve', 'forum', 'rubrique', 'syndic') as $o)
		$ids['id_'.$o] = ($x = intval(${'id_'.$o})) ? $x : '';

	if ($script)
		$url = $script;
	else
		$url = generer_url_public('forum');

	// ne pas mettre '', sinon le squelette n'affichera rien.
	$previsu = ' ';

	// au premier appel (pas de Post-var nommee "retour_forum")
	// memoriser evntuellement l'URL de retour pour y revenir apres
	// envoi du message ; aux appels suivants, reconduire la valeur.
	// Initialiser aussi l'auteur
	if (!$retour_forum = rawurldecode(_request('retour_forum'))) {
		if ($retour_forum = rawurldecode(_request('retour')))
			$retour_forum = str_replace('&var_mode=recalcul','',$retour_forum);
		else {
			// par defaut, on veut prendre url_forum(), mais elle ne sera connue
			// qu'en sortie, on inscrit donc une valeur absurde ("!")
			$retour_forum = "!";
			
			// sauf si on a passe un parametre en argument (exemple : {#SELF})
			if ($url_param_retour) {
				$retour_forum = $url_param_retour;
				$url = $retour_forum;
			}
		}
		if (isset($_COOKIE['spip_forum_user'])
		AND is_array($cookie_user = unserialize($_COOKIE['spip_forum_user']))) {
			$auteur = $cookie_user['nom'];
			$email_auteur = $cookie_user['email'];
		} else {
			$auteur = $GLOBALS['auteur_session']['nom'];
			$email_auteur = $GLOBALS['auteur_session']['email'];
		}

	} else { // appels ulterieurs

		// Recuperer le message a previsualiser
		$titre = _request('titre');
		$texte = _request('texte');
		$auteur = _request('auteur');
		$email_auteur = _request('email_auteur');
		$nom_site_forum = _request('nom_site_forum');
		$url_site = _request('url_site');

		if ($retour_forum != "!") $url = $retour_forum;
		
		if ($afficher_texte != 'non') 
			$previsu = inclure_previsu($texte, $titre, $email_auteur, $auteur, $url_site, $nom_site_forum, $ajouter_mot);

		$alea = forum_fichier_tmp();

		$hash = calculer_action_auteur('ajout_forum'.join(' ', $ids).' '.$alea);
	}

	$url_post = $url;
	foreach ($ids as $id => $v)
		$url_post = parametre_url($url_post, $id, $v, '&');

	return array('formulaire_forum', 0,
	array(
		'auteur' => $auteur,
		'disabled' => ($type == "abo")? "disabled" : '',
		'email_auteur' => $email_auteur,
		'modere' => (($type != 'pri') ? '' : ' '),
		'nom_site_forum' => $nom_site_forum,
		'retour_forum' => $retour_forum,
		'afficher_texte' => $afficher_texte,
		'previsu' => $previsu,
		'table' => $table,
		'texte' => $texte,
		'titre' => extraire_multi($titre),
		'url' => $url, # ce sur quoi on fait le action='...'
		'url_post' => $url_post, # pour les variables hidden
		'url_site' => ($url_site ? $url_site : "http://"),
		'alea' => $alea,
		'hash' => $hash,
		'ajouter_groupe' => $ajouter_groupe,
		'ajouter_mot' => (is_array($ajouter_mot) ? $ajouter_mot : array()),

		));
}

function inclure_previsu($texte,$titre, $email_auteur, $auteur, $url_site, $nom_site_forum, $ajouter_mot)
{
	$mots_forum = $erreur = $bouton = '';
	if (is_array($ajouter_mot)) {
		$result_mots = spip_query("SELECT id_mot, titre, type
			FROM spip_mots
			WHERE id_mot IN (" #securite XSS
			. preg_replace('/[^0-9,]/', '', join(',',$ajouter_mot))
			. ") ORDER BY 0+type,type,0+titre,titre");
		if (spip_num_rows($result_mots)>0) {
			$mots_forum = "<p>"._T('forum_avez_selectionne')."</p><ul>";
			while ($row = spip_fetch_array($result_mots)) {
				$mots_forum .= "<li style='font-size: 80%;'> "
				. typo($row['type']) . "&nbsp;: <b>"
				. typo($row['titre']) ."</b></li>";
			}
			$mots_forum .= '</ul>';
		}
	}

	if (strlen($texte) < 10 AND !$mots_forum)
		$erreur = _T('forum_attention_dix_caracteres');
	else if (strlen($titre) < 3)
		$erreur = _T('forum_attention_trois_caracteres');
	else
		$bouton = _T('forum_message_definitif');

	// supprimer les <form> de la previsualisation
	// (sinon on ne peut pas faire <cadre>...</cadre> dans les forums)
	return preg_replace("@<(/?)f(orm[>[:space:]])@ism",
			    "<\\1no-f\\2",
		inclure_balise_dynamique(array('formulaire_forum_previsu',
		      0,
		      array(
			'titre' => safehtml(typo($titre)),
			'email_auteur' => safehtml($email_auteur),
			'auteur' => safehtml(typo($auteur)),
			'texte' => safehtml(propre($texte)),
			'url_site' => safehtml($url_site),
			'nom_site_forum' => safehtml(typo($nom_site_forum)),
			'mots_forum' => $mots_forum,
			'erreur' => $erreur,
			'bouton' => $bouton
			)
					       ),
					 false));
}

// Une securite qui nous protege contre :
// - les doubles validations de forums (derapages humains ou des brouteurs)
// - les abus visant a mettre des forums malgre nous sur un article (??)
// On installe un fichier temporaire dans _DIR_SESSIONS (et pas _DIR_CACHE
// afin de ne pas bugguer quand on vide le cache)
// Le lock est leve au moment de l'insertion en base (inc-messforum)
// Ce systeme n'est pas fonctionnel pour les forums sans previsu (notamment
// si $afficher_texte = 'non')

function forum_fichier_tmp()
{
# astuce : mt_rand pour autoriser les hits simultanes
	while (($alea = time() + @mt_rand())
	       AND @file_exists($f = _DIR_SESSIONS."forum_$alea.lck"))
	  {};
	spip_touch ($f);

# et maintenant on purge les locks de forums ouverts depuis > 4 h

	if ($dh = @opendir(_DIR_SESSIONS))
		while (($file = @readdir($dh)) !== false)
			if (preg_match('/^forum_([0-9]+)\.lck$/', $file)
			AND (time()-@filemtime(_DIR_SESSIONS.$file) > 4*3600))
				@unlink(_DIR_SESSIONS.$file);
	return $alea;
}


/*******************************************************/
/* FONCTIONS DE CALCUL DES DONNEES DU FORMULAIRE FORUM */
/*******************************************************/

//
// Chercher le titre et la configuration du forum de l'element auquel on repond
//

function sql_recherche_donnees_forum ($idr, $idf, $ida, $idb, $ids) {

	// changer la table de reference s'il y a lieu (pour afficher_groupes[] !!)
	if ($ida) {
		$r = "SELECT titre FROM spip_articles WHERE id_article = $ida";
		$table = "articles";
	} else if ($idb) {
		$r = "SELECT titre FROM spip_breves WHERE id_breve = $idb";
		$table = "breves";
	} else if ($ids) {
		$r = "SELECT nom_site AS titre FROM spip_syndic WHERE id_syndic = $ids";
		$table = "syndic";
	} else if ($idr) {
		$r = "SELECT titre FROM spip_rubriques WHERE id_rubrique = $idr";
		$table = "rubriques";
	}

	if ($idf)
		$r = "SELECT titre FROM spip_forum WHERE id_forum = $idf";

	if ($r) {
		list($titre) = spip_fetch_array(spip_query($r));
		$titre = supprimer_numero($titre);
	} else 
		return;

	// quelle est la configuration du forum ?
	if ($ida)
		list($accepter_forum) = spip_fetch_array(spip_query(
		"SELECT accepter_forum FROM spip_articles WHERE id_article=$ida"));
	if (!$accepter_forum)
		$accepter_forum = substr($GLOBALS['meta']["forums_publics"],0,3);
	// valeurs possibles : 'pos'teriori, 'pri'ori, 'abo'nnement
	if ($accepter_forum == "non")
		return false;

	return array ($titre, $table, $accepter_forum);
}

?>
