<?php

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

global $balise_FORMULAIRE_ADMIN_collecte ;
$balise_FORMULAIRE_ADMIN_collecte = array();

# on ne peut rien dire au moment de l'execution du squelette

function balise_FORMULAIRE_ADMIN_stat($args, $filtres) {
	return $args;
}

# les boutons admin sont mis d'autorite si absents
# donc une variable statique controle si FORMULAIRE_ADMIN a ete vu.
# Toutefois, si c'est le debuger qui appelle,
# il peut avoir recopie le code dans ses donnees et il faut le lui refounir.
# Pas question de recompiler: ca fait boucler !
# Le debuger transmet donc ses donnees, et cette balise y retrouve son petit.

function balise_FORMULAIRE_ADMIN_dyn($float='', $debug='') {
  global $var_preview, $use_cache, $forcer_debug, $xhtml;
	global $id_article, $id_breve, $id_rubrique, $id_mot, $id_auteur, $id_syndic;
	static $dejafait = false;

	if (!$GLOBALS['spip_admin'])
		return '';

	if (!is_array($debug)) {
		if ($dejafait)
			return '';
	} else {
		if ($dejafait) {
			$res = '';
			foreach($debug['sourcefile'] as $k => $v) {
				if (strpos($v,'formulaire_admin.') !== false)
					return $debug['resultat'][$k . 'tout'];
			}
			return '';
		}
	}
	$dejafait = true;

	// repartir de zero pour les boutons car clean_link a pu etre utilisee
	$link = new Link();
	$link->delVar('var_mode');
	$link->delVar('var_mode_objet');
	$link->delVar('var_mode_affiche');
	$action = $link->getUrl();
	$action = ($action . ((strpos($action, '?') === false) ? '?' : '&'));

	// Ne pas afficher le bouton 'Modifier ce...' si l'objet n'existe pas
	foreach (array('article', 'breve', 'rubrique', 'mot', 'auteur', 'syndic') as $type) {
		$id_type = id_table_objet($type);
		if (!($$id_type = intval($$id_type)
		AND $s = spip_query(
		"SELECT $id_type FROM spip_".table_objet($type)."
		WHERE $id_type=".$$id_type)
		AND spip_num_rows($s)))
			$$id_type=0;
		else {
			$objet_affiche = $type;
			break;
		}
	}

	// Bouton statistiques
	if (lire_meta("activer_statistiques") != "non" 
	AND $id_article
	AND !$var_preview
	AND ($GLOBALS['auteur_session']['statut'] == '0minirezo')) {
		if ($s = spip_query("SELECT id_article
		FROM spip_articles WHERE statut='publie'
		AND id_article = $id_article")
		AND spip_fetch_array($s)) {
			include_local ("inc-stats.php3");
			$r = afficher_raccourci_stats($id_article);
			$visites = $r['visites'];
			$popularite = $r['popularite'];
			$statistiques = 'statistiques_visites.php3?'; # lien si connecte
		}
	}

	// Bouton de debug
	$debug = (($forcer_debug
		   OR $GLOBALS['bouton_admin_debug']
		   OR ($GLOBALS['var_mode'] == 'debug'
		       AND $GLOBALS['_COOKIE']['spip_debug']))
		  AND ($GLOBALS['code_activation_debug'] == 'oui'
		       OR $GLOBALS['auteur_session']['statut'] == '0minirezo')
		  AND !$var_preview
	) ? 'debug' : '';
	$analyser = !$xhtml ? "" :
	  (($xhtml === 'spip_sax') ?
	   ($action . "var_mode=debug&var_mode_affiche=validation") :
	   $GLOBALS['xhtml_check']); // cas tidy
	// hack - ne pas avoir la rubrique si un autre bouton est deja present
	if ($id_article OR $id_breve) unset ($id_rubrique);

	// Pas de "modifier ce..." ? -> donner "acces a l'espace prive"
	if (!($id_article || $id_rubrique || $id_auteur || $id_breve || $id_mot || $id_syndic))
		$ecrire = 'ecrire';

	// Bouton "preview" si l'objet demande existe et est previsualisable
	if (!$GLOBALS['var_preview'] AND (
	((lire_meta('preview')=='1comite'
		AND $GLOBALS['auteur_session']['statut'] =='1comite')
	OR (lire_meta('preview')<>''
		AND $GLOBALS['auteur_session']['statut'] =='0minirezo'))
	)) {
		if ($objet_affiche == 'article'
		OR $objet_affiche == 'breve'
		OR $objet_affiche == 'rubrique'
		OR $objet_affiche == 'syndic')
			if (spip_num_rows(spip_query(
			"SELECT id_$objet_affiche FROM spip_".table_objet($objet_affiche)."
			WHERE ".id_table_objet($objet_affiche)."=".$$id_type."
			AND statut IN ('prop', 'prive')")))
				$preview = 'preview';
	}

	return array('formulaire_admin', 0,
			array(
				'id_article' => $id_article,
				'id_rubrique' => $id_rubrique,
				'id_auteur' => $id_auteur,
				'id_breve' => $id_breve,
				'id_mot' => $id_mot,
				'id_syndic' => $id_syndic,
				'ecrire' => $ecrire,
				'action' => $action,
				'preview' => $preview,
				'debug' => $debug,
				'popularite' => ceil($popularite),
				'statistiques' => $statistiques,
				'visites' => intval($visites),
				'use_cache' => ($use_cache ? ' *' : ''),
				'divclass' => $float,
				'analyser' => $analyser,
				'xhtml_error' => $GLOBALS['xhtml_error']
			)
		);
}
?>