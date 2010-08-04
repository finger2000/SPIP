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
// pour low_sec (iCal)
include_ecrire("inc_acces.php3");


///// debut de la page
debut_page(_T("icone_suivi_activite"),  "asuivre", "synchro");

echo "<br><br><br>";
gros_titre(_T("icone_suivi_activite"));


debut_gauche();

debut_boite_info();

echo "<div class='verdana2'>";

echo _T('ical_info1').'<br /><br />';

echo _T('ical_info2');

echo "</div>";

fin_boite_info();


$suivi_edito=lire_meta("suivi_edito");
$adresse_suivi=lire_meta("adresse_suivi");
$adresse_site=lire_meta("adresse_site");
$adresse_suivi_inscription=lire_meta("adresse_suivi_inscription");

debut_droite();


///
/// Suivi par mailing-list
///

if ($suivi_edito == "oui" AND strlen($adresse_suivi) > 3 AND strlen($adresse_suivi_inscription) > 3) {
	debut_cadre_enfonce("racine-site-24.gif", false, "", _T('ical_titre_mailing'));
	$lien = propre("[->$adresse_suivi_inscription]");


	echo _T('info_config_suivi_explication');
	echo "<p align='center'><b>$lien</b></p>\n";

	fin_cadre_enfonce();
}


///
/// Suivi par agenda iCal (taches + rendez-vous)
///

debut_cadre_relief("agenda-24.gif", false, "", _T('icone_calendrier'));

echo _T('calendrier_synchro');

echo '<p>'._T('ical_info_calendrier').'</p>';


function afficher_liens_calendrier($lien, $icone, $texte) {
	global $adresse_site;
	echo debut_cadre_enfonce($icone);
	echo $texte;
	echo "<table style='width: 100%;><tr'><td style='width: 200px;'>";
		icone_horizontale (_T('ical_methode_http'), "$adresse_site/$lien", "calendrier-24.gif");
	echo "</td>";
	echo "<td> &nbsp; </td>";
	echo "<td style='width: 200px;'>";
		$webcal = ereg_replace("https?://", "webcal://", $adresse_site);
		icone_horizontale (_T('ical_methode_webcal'), "$webcal/$lien", "calendrier-24.gif");
	echo "</td></tr></table>";
	echo fin_cadre_enfonce();
}

afficher_liens_calendrier('ical.php3','', _T('ical_texte_public'));


afficher_liens_calendrier("spip_cal.php3?id=$connect_id_auteur&cle=".afficher_low_sec($connect_id_auteur,'ical'),'cadenas-24.gif',  _T('ical_texte_prive'));


fin_cadre_relief();



///
/// Suivi par RSS
///

debut_cadre_relief("site-24.gif", false, "", _T('ical_titre_rss'));

echo _T('ical_texte_rss');

echo "<p>"._T("ical_texte_rss_articles")."</p>";

echo propre("<ul><cadre>".$adresse_site."/backend.php3</cadre></ul>");

echo "<p>"._T("ical_texte_rss_articles2")."</p>";


	$query = "SELECT * FROM spip_rubriques WHERE id_parent='0' ORDER BY 0+titre, titre";
	$result = spip_query($query);

	if (spip_num_rows($result) > 0) {
		echo "<ul>";

		while($row=spip_fetch_array($result)){
			$id_rubrique=$row['id_rubrique'];
			$titre_rubrique = typo($row['titre']);
			$titre = htmlspecialchars($titre_rubrique);
			
			echo "<li>", http_href("$adresse_site/backend.php3?id_rubrique=$id_rubrique", 
	'<span class="rss-button">RSS</span>'
	. "&nbsp; $titre_rubrique",
					       $titre),
			  "</li>\n";
		}
		echo "</ul>";
	}
	

	$activer_breves = lire_meta('activer_breves');
	
	if ($activer_breves == "oui") {
		
		echo "<p>"._T("ical_texte_rss_breves")."</p>";
		echo propre('<ul><cadre>'.$adresse_site.'/backend-breves.php3</cadre></ul>');
		
	}

fin_cadre_relief();



///
/// Suivi par Javascript
///

debut_cadre_relief("doc-24.gif", false, "", _T('ical_titre_js'));

echo _T('ical_texte_js').'<p />';

echo propre('<cadre><script type="text/javascript" src="'.$adresse_site.'/distrib.php3"></script></cadre>');

fin_cadre_relief();



fin_page();

?>