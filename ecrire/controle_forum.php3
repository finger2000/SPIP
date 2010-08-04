<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


include ("inc.php3");

function forum_parent($id_forum) {
	$row=spip_fetch_array(spip_query("
SELECT * FROM spip_forum WHERE id_forum=$id_forum AND statut != 'redac'
"));
	if (!$row) return '';
	$id_forum=$row['id_forum'];
	$forum_id_parent=$row['id_parent'];
	$forum_id_rubrique=$row['id_rubrique'];
	$forum_id_article=$row['id_article'];
	$forum_id_breve=$row['id_breve'];
	$forum_id_syndic=$row['id_syndic'];
	$forum_stat=$row['statut'];

	if ($forum_id_article > 0) {
	  $row=spip_fetch_array(spip_query("
SELECT id_article, titre, statut FROM spip_articles WHERE id_article='$forum_id_article'"));
	  $id_article = $row['id_article'];
	  $titre = $row['titre'];
	  $statut = $row['statut'];
	  if ($forum_stat == "prive" OR $forum_stat == "privoff") {
	    return array('pref' => _T('item_reponse_article'),
			 'url' => "articles.php3?id_article=$id_article",
			 'type' => 'id_article',
			 'valeur' => $id_article,
			 'titre' => $titre);
	  } else {
	    return array('pref' =>  _T('lien_reponse_article'),
			 'url' => generer_url_article($id_article),
			 'type' => 'id_article',
			 'valeur' => $id_article,
			 'titre' => $titre,
			 'avant' => "<a href='articles_forum.php3?id_article=$id_article'><font color='red'>"._T('lien_forum_public'). "</font></a><br>");
	  }
	}
	else if ($forum_id_rubrique > 0) {
	  $row = spip_fetch_array(spip_query("
SELECT * FROM spip_rubriques WHERE id_rubrique=\"$forum_id_rubrique\""));
	  $id_rubrique = $row['id_rubrique'];
	  $titre = $row['titre'];
	  return array('pref' => _T('lien_reponse_rubrique'),
		       'url' => generer_url_rubrique($id_rubrique),
		       'type' => 'id_rubrique',
		       'valeur' => $id_rubrique,
		       'titre' => $titre);
	}
	else if ($forum_id_syndic > 0) {
	  $row = spip_fetch_array(spip_query("
SELECT * FROM spip_syndic WHERE id_syndic=\"$forum_id_syndic\""));
	  $id_syndic = $row['id_syndic'];
	  $titre = $row['nom_site'];
	  $statut = $row['statut'];
	  return array('pref' => _T('lien_reponse_site_reference'),
		       'url' => "sites.php3?id_syndic=$id_syndic",
		       'type' => 'id_syndic',
		       'valeur' => $id_syndic,
		       'titre' => $titre);
	}
	else if ($forum_id_breve > 0) {
	  $row = spip_fetch_array(spip_query("
SELECT * FROM spip_breves WHERE id_breve=\"$forum_id_breve\""));
	  $id_breve = $row['id_breve'];
	  $date_heure = $row['date_heure'];
	  $titre = $row['titre'];
	  if ($forum_stat == "prive") {
	    return array('pref' => _T('lien_reponse_breve'),
			 'url' => "breves_voir.php3?id_breve=$id_breve",
			 'type' => 'id_breve',
			 'valeur' => $id_breve,
			 'titre' => $titre);
	  } else {
	    return array('pref' => _T('lien_reponse_breve_2'),
			 'url' => generer_url_breve($id_breve),
			 'type' => 'id_breve',
			 'valeur' => $id_breve,
			 'titre' => $titre);
	  }
	}
	else if ($forum_stat == "privadm") {
	  $retour = forum_parent($forum_id_parent);
	  if ($retour) return $retour;
	  else return array('pref' => _T('info_message'),
			    'url' => 'forum_admin.php3',
			    'titre' => _T('info_forum_administrateur'));
	}
	else {
	  $retour = forum_parent($forum_id_parent);
	  if ($retour) return $retour;
	  else return array('pref' => _T('info_message'),
			    'url' => 'forum.php3',
			    'titre' => _T('info_forum_interne'));
	}
}


function controle_forum($row, $rappel) {
	global $couleur_foncee;
	global $mots_cles_forums;

	$id_forum = $row['id_forum'];
	$forum_id_parent = $row['id_parent'];
	$forum_id_rubrique = $row['id_rubrique'];
	$forum_id_article = $row['id_article'];
	$forum_id_breve = $row['id_breve'];
	$forum_date_heure = $row['date_heure'];
	$forum_titre = echapper_tags($row['titre']);
	$forum_texte = echapper_tags($row['texte']);
	$forum_auteur = echapper_tags($row['auteur']);
	$forum_email_auteur = echapper_tags($row['email_auteur']);
	$forum_nom_site = echapper_tags($row['nom_site']);
	$forum_url_site = echapper_tags($row['url_site']);
	$forum_stat = $row['statut'];
	$forum_ip = $row['ip'];
	$forum_id_auteur = $row["id_auteur"];

	$r = forum_parent($id_forum);
	$avant = $r['avant'];
	$url = $r['url'];
	$titre = $r['titre'];
	$type = $r['type'];
	$valeur = $r['valeur'];
	$pref = $r['pref'];
	
	$cadre = "";
	
	$controle = "\n<br /><br /><a id='id$id_forum'></a>";
	
	$controle .= debut_cadre_thread_forum("", true, "", typo($forum_titre));

	if ($forum_stat=="off" OR $forum_stat == "privoff") {
		$controle .= "<div style='border: 2px #ff0000 dashed;'>";
	}
	else if ($forum_stat=="prop") {
		$controle .= "<div style='border: 2px yellow solid; background-color: white;'>";
	}
	else {
		$controle .= "<div>";
	}
	
	$controle .= "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n<tr><td width='100%' valign='top'><table width='100%' cellpadding='5' cellspacing='0'>\n<tr><td class='serif'><span class='arial2'>" .
	  date_relative($forum_date_heure) .
	  "</span>";
	if ($forum_auteur) {
		if ($forum_email_auteur)
			$forum_auteur="<a href=\"mailto:$forum_email_auteur?SUBJECT=".rawurlencode($forum_titre)."\">$forum_auteur</A>";
		$controle .= "<span class='arial2'> / <B>$forum_auteur</B></span>";
	}

	$controle .= boutons_controle_forum($id_forum, $forum_stat, $forum_id_auteur, "$type=$valeur", $forum_ip);

	$controle .= "\n<br />$avant<B>$pref <A HREF='$url'>$titre</A></B>" .
	  "<P align='justify'>".propre($forum_texte);

	if (strlen($forum_url_site) > 10 AND strlen($forum_nom_site) > 3)
		$controle .= "\n<div align='left' class='serif'><B><A HREF='$forum_url_site'>$forum_nom_site</A></B></div>";

	if ($mots_cles_forums == "oui") {
		$query_mots = "SELECT * FROM spip_mots AS mots, spip_mots_forum AS lien WHERE lien.id_forum = '$id_forum' AND lien.id_mot = mots.id_mot";
		$result_mots = spip_query($query_mots);

		while ($row_mots = spip_fetch_array($result_mots)) {
			$titre_mot = propre($row_mots['titre']);
			$type_mot = propre($row_mots['type']);
			$controle .= "\n<li> <b>$type_mot :</b> $titre_mot";
		}
	}

	$controle .= "</TD></TR></TABLE>";
	$controle .= "</TD></TR></TABLE>\n";

	$controle .= "</div>".fin_cadre_thread_forum(true);
	return $controle;
}

debut_page(_T('titre_page_forum_suivi'), "redacteurs", "forum-controle");

if (!$page) $page = "public";

echo "<br><br><br>";
gros_titre(_T('titre_forum_suivi'));
barre_onglets("suivi_forum", $page);

if ($connect_statut != "0minirezo" OR !$connect_toutes_rubriques) {
	echo "<B>"._T('avis_non_acces_page')."</B>";
	exit;
}

debut_gauche();
debut_boite_info();
echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2>";
echo _T('info_gauche_suivi_forum_2');
echo aide("suiviforum");
echo "</FONT>";

// Afficher le lien RSS
include_ecrire('inc_rss.php3');
$op = 'forums';
$args = array(
	'page' => $page
);
echo "<div style='text-align: "
	. $GLOBALS['spip_lang_right']
	. ";'>"
	. bouton_spip_rss($op, $args)
	."</div>";



fin_boite_info();
debut_droite();

//
// Debut de la page de controle
//

// recuperer le critere SQL qui selectionne nos forums
$query_forum = critere_statut_controle_forum($page);

// Si un id_controle_forum est demande, on adapte le debut
if ($debut_id_forum = intval($debut_id_forum)
AND $d = spip_fetch_array(spip_query("SELECT date_heure FROM spip_forum
WHERE id_forum=$debut_id_forum"))) {
	$result_forum = spip_query("SELECT id_forum FROM spip_forum
		WHERE " . $query_forum . "
		AND date_heure > '".$d['date_heure']."'");
	$debut = spip_num_rows($result_forum);
}
if (!$debut=intval($debut)) $debut = 0;

$pack = 20;	// nb de forums affiches par page
$enplus = 200;	// intervalle affiche autour du debut
$limitdeb = ($debut > $enplus) ? $debut-$enplus : 0;
$limitnb = $debut + $enplus - $limitdeb;
$rappel = "page=$page";
$mots_cles_forums = lire_meta("mots_cles_forums");
$controle = '';

echo "<div class='serif2'>";
$i = $limitdeb;
if ($i>0) echo "<a href='controle_forum.php3?$rappel'>0</a> ... | ";

$result_forum = spip_query("
SELECT	*
FROM	spip_forum
WHERE " . $query_forum . "
ORDER BY date_heure DESC LIMIT $limitdeb, $limitnb"
);

while ($row = spip_fetch_array($result_forum)) {

	// barre de navigation
	if ($i == $pack*floor($i/$pack)) {
		if ($i == $debut)
			echo "<FONT SIZE=3><B>$i</B></FONT>";
		else
			echo "<a href='controle_forum.php3?$rappel&debut=$i'>$i</a>";
		echo " | ";
	}
	// est-ce que ce message doit s'afficher dans la liste ?
	if (($i>=$debut) AND ($i<($debut + $pack)))
	  $controle .= controle_forum($row, "$rappel&debut=$debut");
	$i ++;
 }

echo "<a href='controle_forum.php3?$rappel&debut=$i'>...</a>$controle</div>";
fin_page();
?>