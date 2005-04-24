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

function my_sel($num, $tex, $comp) {
  return "<option value='$num'" . (($num != $comp) ? '' : " selected='selected'") .
    ">$tex</option>\n";
}

function afficher_mois($mois, $attributs, $autre=false){
  return
	"<select $attributs>\n" .
	(!$autre ? '' : my_sel("00",_T('mois_non_connu'),$mois)) .
	my_sel("01", _T('date_mois_1'), $mois) .
	my_sel("02", _T('date_mois_2'), $mois) .
	my_sel("03", _T('date_mois_3'), $mois) .
	my_sel("04", _T('date_mois_4'), $mois) .
	my_sel("05", _T('date_mois_5'), $mois) .
	my_sel("06", _T('date_mois_6'), $mois) .
	my_sel("07", _T('date_mois_7'), $mois) .
	my_sel("08", _T('date_mois_8'), $mois) .
	my_sel("09", _T('date_mois_9'), $mois) .
	my_sel("10", _T('date_mois_10'), $mois) .
	my_sel("11", _T('date_mois_11'), $mois) .
	my_sel("12", _T('date_mois_12'), $mois) .
	"</select>\n";
}

function afficher_annee($annee, $attributs, $debut=1996) {
	$res = ($annee > 1996) ? '' : my_sel($annee,$annee,$annee);
	for ($i=$debut; $i < date("Y") + 3; $i++) {
		$res .= my_sel($i,$i,$annee);
	}
	return "<select $attributs>\n$res</select>\n";
}

function afficher_jour($jour, $attributs, $autre=false){

	$res = (!$autre ? "" : my_sel("00",_T('jour_non_connu_nc'),$jour));
	for($i=1;$i<32;$i++){
		if ($i<10){$aff="&nbsp;".$i;}else{$aff=$i;}
		$res .= my_sel($i,$aff,$jour);
	}
	return "<select $attributs>\n$res</select>\n";
}

function afficher_jour_mois_annee_h_m($date, $heures, $minutes, $suffixe='')
{
  return 
    afficher_jour(jour($date), "name='jour$suffixe' size='1' class='fondl verdana1'") .
    afficher_mois(mois($date), "name='mois$suffixe' size='1' class='fondl verdana1'") .
    afficher_annee(annee($date), "name='annee$suffixe' size='1' class='fondl verdana1'", date('Y')-1) .
    "&nbsp;  <input type='text' class='fondl verdana1' name='heures$suffixe' value=\"".$heures."\" size='3'/>&nbsp;".majuscules(_T('date_mot_heures'))."&nbsp;" .
    "<input type='text' class='fondl verdana1' name='minutes$suffixe' value=\"$minutes\" size='3'/>";
}

function afficher_formulaire_date($action, $texte, $jour, $mois, $annee)
{
  global $couleur_foncee;
  return
	"<form action='$action' method='POST'>"
	. "<table cellpadding='5' cellspacing='0' border='0' width='100%' background='"
	.  _DIR_IMG_PACK
	. "rien.gif'>"
	. "<tr><td bgcolor='$couleur_foncee' colspan='2'><font size='2' color='#ffffff'><b>"
	._T('texte_date_publication_article')
	. "</b></font></tr>"
	. "<tr><td align='center'>"
	. afficher_jour($jour, "name='jour' size='1' class='fondl'", true)
	. afficher_mois($mois, "name='mois' size='1' class='fondl'", true)
	. afficher_annee($annee, "name='annee' size='1' class='fondl'",1996)
	. "</td><td align='right'>"
	. "<input type='submit' name='Changer' class='fondo' value='"
	. _T('bouton_changer')
	. "'>"
	. "</td></tr></table>"
	. "</form>";
}

?>
