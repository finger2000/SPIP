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

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_CALENDRIER")) return;
define("_ECRIRE_INC_CALENDRIER", "1");

include_ecrire("inc_texte.php3");
charger_generer_url();

//  Typographie generale des calendriers de 3 type: jour/semaine/mois(ou plus)

// Notes: pour toutes les fonctions ayant parmi leurs parametres
// annee, mois, jour, echelle, partie_cal, script, ancre
// ceux-ci apparaissent TOUJOURS dans cet ordre 

define(DEFAUT_D_ECHELLE,120); # 1 pixel = 2 minutes

// icones standards, fonction de la direction de la langue

global $bleu, $vert, $jaune;
$bleu = http_img_pack("m_envoi_bleu$spip_lang_rtl.gif", 'B', "class='calendrier-icone'");
$vert = http_img_pack("m_envoi$spip_lang_rtl.gif", 'V', "class='calendrier-icone'");
$jaune= http_img_pack("m_envoi_jaune$spip_lang_rtl.gif", 'J', "class='calendrier-icone'");

// 
// Utilitaires sans html ni sql
//

// utilitaire de separation script / ancre
// et de retrait des arguments a remplacer

function calendrier_retire_args_ancre($script)
{
  $script = str_replace('?bonjour=oui&?','?',$script);
  if (ereg('^(.*)(#[^=&]*)$',$script, $m)) {
	  $script = $m[1];
	  $ancre = $m[2];
  } else { $ancre = ''; }
  if ($script[strlen($script)-1] == '?')  $script = substr($script,0,-1);
  foreach(array('echelle','jour','mois','annee', 'type', 'partie_cal') as $arg) {
		$script = preg_replace("/([?&])$arg=[^&]*&/",'\1', $script);
		$script = preg_replace("/([?&])$arg=[^&]*$/",'\1', $script);
	}
  return array($script, $ancre);
}

// tous les liens de navigations sont issus de cette fonction
// on peut definir generer_url_date et un htacces pour simplifier les URL

function calendrier_args_date($script, $annee, $mois, $jour, $type, $finurl) 
{
  if (function_exists('generer_url_date'))
    return generer_url_date($script, $annee, $mois, $jour, $type, $finurl);
  else return	$script .		   
	(ereg('[?&]$', $script) ?  "" : (strpos($script,'?') ? '&' : '?')) .
		'annee=' . sprintf("%04d", $annee) . '&' .
		'mois='  . sprintf("%02d", $mois) . '&' .
		'jour='  . sprintf("%02d", $jour) . '&' .
		'type='  . $type .
    		$finurl;
}

# prend une heure de debut et de fin, ainsi qu'une echelle (seconde/pixel)
# et retourne un tableau compose
# - taille d'une heure
# - taille d'une journee
# - taille de la fonte
# - taille de la marge

function calendrier_echelle($debut, $fin, $echelle)
{
  if ($echelle==0) $echelle = DEFAUT_D_ECHELLE;
  if ($fin <= $debut) $fin = $debut +1;

  $duree = $fin - $debut;
  $dimheure = floor((3600 / $echelle));
  return array($dimheure,
	       (($duree+2) * $dimheure),
	       floor (14 / (1+($echelle/240))),
	       floor(240 / $echelle));
}

# Calcule le "top" d'une heure

function calendrier_top ($heure, $debut, $fin, $dimheure, $dimjour, $fontsize) {
	
	$h_heure = substr($heure, 0, strpos($heure, ":"));
	$m_heure = substr($heure, strpos($heure,":") + 1, strlen($heure));
	$heure100 = $h_heure + ($m_heure/60);

	if ($heure100 < $debut) $heure100 = ($heure100 / $debut) + $debut - 1;
	if ($heure100 > $fin) $heure100 = (($heure100-$fin) / (24 - $fin)) + $fin;

	$top = floor(($heure100 - $debut + 1) * $dimheure);

	return $top;	
}

# Calcule la hauteur entre deux heures
function calendrier_height ($heure, $heurefin, $debut, $fin, $dimheure, $dimjour, $fontsize) {

	$height = calendrier_top ($heurefin, $debut, $fin, $dimheure, $dimjour, $fontsize) 
				- calendrier_top ($heure, $debut, $fin, $dimheure, $dimjour, $fontsize);

	$padding = floor(($dimheure / 3600) * 240);
	$height = $height - (2* $padding + 2); // pour padding interieur
	
	if ($height < ($dimheure/4)) $height = floor($dimheure/4); // eviter paves totalement ecrases
	
	return $height;	
}

//
// init: calcul generique des evenements a partir des tables SQL
//

function http_calendrier_init($time='', $ltype='', $lechelle='', $lpartie_cal='', $script='', $evt='')
{
	global $mois, $annee, $jour, $type, $echelle, $partie_cal;

	if (!$time) 
	  {
	    $today=getdate(time());
	    if (!$annee)
	      $annee = $today["year"];
	    if (!$mois)
	      $mois = $today["mon"];
	    if (!$jour)
	      $jour = $today["mday"];
	    $time = mktime(0,0,0,$mois, $jour, $annee);
	    $type= 'mois';
	  }

	$jour = date("d",$time);
	$mois = date("m",$time);
	$annee = date("Y",$time);
	if (!$ltype) $ltype = $type ? $type : 'mois';
	if (!$lechelle = intval($lechelle)) $lechelle = intval($echelle);
	if (!$lpartie_cal OR !preg_match('/^\w+$/', $$lpartie_cal)) 
		$lpartie_cal = preg_match('/^\w+$/',$partie_cal) ? 
		  $partie_cal : 'sansheure';

	list($script, $ancre) = 
	  calendrier_retire_args_ancre($script ? $script :
						  $GLOBALS['clean_link']->getUrl()); 
	if (!_DIR_RESTREINT) http_calendrier_titre($time, $ltype);
	if (!$evt) {
	  $g = 'sql_calendrier_' . $ltype;
	  $evt = sql_calendrier_interval($g($annee,$mois, $jour));
	  sql_calendrier_interval_articles("'$annee-$mois-00'", "'$annee-$mois-1'", $evt[0]);
	  // si on veut les forums, decommenter
#	  sql_calendrier_interval_forums($g($annee,$mois,$jour), $evt[0]);
	}
	$f = 'http_calendrier_' . $ltype;
	return $f($annee, $mois, $jour, $lechelle, $lpartie_cal, $script, $ancre, $evt);

}

# titre de la page, si on est dans l'espace de redaction

function http_calendrier_titre($time, $type)
{
  $date = date("Y-m-d", $time); # a optimiser
if ($type == 'semaine') {

	$GLOBALS['afficher_bandeau_calendrier_semaine'] = true;

	$titre = _T('titre_page_calendrier',
		    array('nom_mois' => nom_mois($date), 'annee' => annee($date)));
	  }
elseif ($type == 'jour') {
	$titre = nom_jour($date)." ". affdate_jourcourt($date);
 }
 else {
	$titre = _T('titre_page_calendrier',
		    array('nom_mois' => nom_mois($date), 'annee' => annee($date)));
	  }

 debut_page($titre, "redacteurs", "calendrier");
 echo "<div>&nbsp;</div>" ;
}

# affichage d'un calendrier de plusieurs semaines
# si la periode est inferieure a 31 jours, on considere que c'est un mois

function http_calendrier_mois($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre, $evt)
{
	global $spip_ecran;
	if (!isset($spip_ecran)) $spip_ecran = 'large';
	$premier_jour = '01';
	$dernier_jour = '31';

	if (is_array($evt)) {
	  list($sansduree, $evenements, $premier_jour, $dernier_jour) = $evt;
	  if (!$premier_jour) $premier_jour = '01';
	  if (!$dernier_jour)
	    {
	      $dernier_jour = 31;
	      while (!(checkdate($mois,$dernier_jour,$annee))) $dernier_jour--;
	    }
	  if ($sansduree)
	    foreach($sansduree as $d => $r) 
	      $evenements[$d] = !$evenements[$d] ? $r : array_merge($evenements[$d], $r);
	  $evt = 
	    http_calendrier_mois_noms($annee, $mois, $jour, $script, $ancre) .
	    http_calendrier_mois_sept($annee, $mois, $premier_jour, $dernier_jour,$evenements, $script, "&echelle=$echelle&partie_cal=$partie_cal$ancre") ;
	    }

	return 
	  "<table class='calendrier-table-$spip_ecran' cellspacing='0' cellpadding='0'>" .
	  http_calendrier_mois_navigation($annee, $mois, $premier_jour, $dernier_jour, $echelle, $partie_cal, $script, $ancre) .
	  $evt .
	  '</table>' .
	  http_calendrier_sans_date($annee, $mois, $evenements) .
	  (_DIR_RESTREINT ? "" : http_calendrier_aide_mess());
}

// si la periore a plus de 31 jours, c'est du genre trimestre, semestre etc
// pas de navigation suivant/precedent alors

function http_calendrier_mois_navigation($annee, $mois, $premier_jour, $dernier_jour, $echelle, $partie_cal, $script, $ancre){
	if ($dernier_jour > 31) {
	  $prec = $suiv = '';
	  $periode = affdate_mois_annee(date("Y-m-d", mktime(1,1,1,$mois,$premier_jour,$annee))) . ' - '. affdate_mois_annee(date("Y-m-d", mktime(1,1,1,$mois,$dernier_jour,$annee)));
	} else {

	$mois_suiv=$mois+1;
	$annee_suiv=$annee;
	$mois_prec=$mois-1;
	$annee_prec=$annee;
	if ($mois==1){
	  $mois_prec=12;
	  $annee_prec=$annee-1;
	}
	else if ($mois==12){$mois_suiv=1;	$annee_suiv=$annee+1;}
	$prec = array($annee_prec, $mois_prec, 1, "mois");
	$suiv = array($annee_suiv, $mois_suiv, 1, "mois");
	$periode = affdate_mois_annee("$annee-$mois-1");
	}
	return
	  "\n<tr><td colspan='7'>" .
	  http_calendrier_navigation($annee,
				   $mois,
				   $jour,
				   $echelle,
				   $partie_cal,
				   $periode,
				   $script,
				   $prec,
				   $suiv,
				   'mois',
				   $ancre) .
	  "</td></tr>";

}

function http_calendrier_mois_noms($annee, $mois, $jour, $script, $ancre){
	global $couleur_claire;

	$bandeau ="";
	for ($j=1; $j<8;$j++){
		$bandeau .= 
		  "\n\t<th class='calendrier-th'>" .
		  _T('date_jour_' . (($j%7)+1)) .
		  "</th>";
	}
	return "\n<tr" .
	  (!isset($couleur_claire) ? "" : " style='background-color: $couleur_claire'") . 
	  ">$bandeau\n</tr>";
}

# dispose les lignes d'un calendrier de 7 colonnes (les jours)
# chaque case est garnie avec les evenements du jour figurant dans $evenements

function http_calendrier_mois_sept($annee, $mois, $premier_jour, $dernier_jour,$evenements, $script, $ancre='')
{
	global $couleur_claire, $spip_lang_left, $spip_lang_right;

	if (!ereg('[?&]$', $script))
	  $script .= (strpos($script,'?') ? '&' : '?');

	// affichage du debut de semaine hors periode
	$init = '';
	$debut = date("w",mktime(1,1,1,$mois,$premier_jour,$annee));
	for ($i=$debut ? $debut : 7;$i>1;$i--)
	  {$init .= "\n\t<td style=\"border-bottom: 1px solid $couleur_claire;\">&nbsp;</td>";}

	$total = '';
	$ligne = '';
	$today=date("Ymd");
	for ($j=$premier_jour; $j<=$dernier_jour; $j++){
		$nom = mktime(1,1,1,$mois,$j,$annee);
		$jour = date("d",$nom);
		$jour_semaine = date("w",$nom);
		$mois_en_cours = date("m",$nom);
		$annee_en_cours = date("Y",$nom);
		$amj = date("Y",$nom) . $mois_en_cours . $jour;
		$couleur_lien = "black";
		$couleur_fond = "";

		if ($jour_semaine == 0) $couleur_fond = $couleur_claire;
		else if ($jour_semaine==1)
			  { 
			    $total .= "\n<tr>$init$ligne\n</tr>";
			    $ligne = $init = '';
			  }
		
		if ($amj == $today) {
			$couleur_lien = "red";
			$couleur_fond = "white";
		}
		$res = '';
		if ($evts = $evenements[$amj]) {
		  foreach ($evts as $evenement)
		    {
		      $res .= isset($evenement['DTSTART']) ?
			http_calendrier_avec_heure($evenement, $amj) :
			http_calendrier_sans_heure($evenement);
		    }
		}

		$ligne .= "\n\t\t<td\tclass='calendrier-td'
			style='height: 100px; border-bottom: 1px solid $couleur_claire; border-$spip_lang_right: 1px solid $couleur_claire;" .
		  ($couleur_fond ? " background-color: $couleur_fond;" : "") .
		  ($ligne ? "" :
		   " border-$spip_lang_left: 1px solid $couleur_claire;") .
		  "'>" .
		  (!_DIR_RESTREINT ? 
		   (http_href(calendrier_args_date($script,$annee_en_cours, $mois_en_cours, $jour, "jour", $ancre), 
			      $jour,
			      '', "color: $couleur_lien",'calendrier-helvetica16') . 
		    http_calendrier_ics_message($annee_en_cours, $mois_en_cours, $jour, false)):
		   http_calendrier_mois_clics($annee_en_cours, $mois_en_cours, $jour, $script, $ancre)) .
		  $res .
		  "\n\t</td>";
	}
	return  $total . ($ligne ? "\n<tr>$ligne\n</tr>" : '');
}

// typo pour l'espace public

function http_calendrier_mois_clics($annee, $mois, $jour, $script, $ancre)
{
      $d = mktime(0,0,0,$mois, $jour, $annee);
      $semaine = date("W", $d);
      return 
	"<table width='100%'>\n<tr><td style='text-align: left'>". 
	http_href(calendrier_args_date($script,$annee, $mois, $jour, "jour", $ancre), 
		  "$jour/$mois",
		  _T('date_jour_'. (1+date('w',$d))) .
		  " $jour " .
		  _T('date_mois_'.(0+$mois)),
		  '','calendrier-helvetica16') .
	"</td><td style='text-align: right'>" .
	http_href(calendrier_args_date($script,$annee, $mois, $jour, "semaine", $ancre),
		  $semaine,
		  _T('date_semaines') . " $semaine",
		  '',
		  'calendrier-helvetica16') .
	"</td></tr>\n</table>";
}

# dispose les evenements d'une semaine

function http_calendrier_semaine($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre, $evt)
{
	global $spip_ecran;
	if (!isset($spip_ecran)) $spip_ecran = 'large';

	$init = date("w",mktime(1,1,1,$mois,$jour,$annee));
	$init = $jour+1-($init ? $init : 7);
	$sd = '';

	if (is_array($evt))
	  {
	    $sd = http_calendrier_sans_date($annee, $mois,$evt[0]);
	    $finurl = "&echelle=$echelle&partie_cal=$partie_cal$ancre";
	    $evt =
	      http_calendrier_semaine_noms($annee, $mois, $init, $scriptep, $finurl) .
	      http_calendrier_semaine_sept($annee, $mois, $init, $echelle, $partie_cal, $evt);
	  }

	return 
	  "\n<table class='calendrier-table-$spip_ecran' cellspacing='0' cellpadding='0'>" .
	  http_calendrier_semaine_navigation($annee, $mois, $init, $echelle, $partie_cal, $script, $ancre) .
	  $evt .
	  "</table>" .
	  $sd .
	  (_DIR_RESTREINT ? "" : http_calendrier_aide_mess());
}

function http_calendrier_semaine_navigation($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre){

	$fin = mktime (1,1,1,$mois, $jour+6, $annee);
	$fjour = date("d",$fin);
	$fmois = date("m",$fin);
	$fannee = date("Y",$fin);
	$fin = date("Y-m-d", $fin);
	$debut = mktime (1,1,1,$mois, $jour, $annee);
	$djour = date("d",$debut)+0;
	$dmois = date("m",$debut);
	$dannee = date("Y",$debut);
	$debut = date("Y-m-d", $debut);
	$periode = (($dannee != $fannee) ?
		    (affdate($debut)." - ".affdate($fin)) :
		    (($dmois == $fmois) ?
		     ($djour ." - ".affdate_jourcourt($fin)) :
		     (affdate_jourcourt($debut)." - ".affdate_jourcourt($fin))));

  return
    "\n<tr><td colspan='7'>" .
    http_calendrier_navigation($annee,
			       $mois,
			       $jour,
			       $echelle,
			       $partie_cal, 
			       $periode,
			       $script,
			       array($dannee, $dmois, ($djour-7), "semaine"),
			       array($fannee, $fmois, ($fjour+1), "semaine"),
			       'semaine',
			       $ancre) .
    "</td></tr>\n";
}

function http_calendrier_semaine_noms($annee, $mois, $jour, $script, $ancre){
	global $couleur_claire;

	$bandeau = '';
	$script .= (ereg('[?&]$', $script) ? "" : (strpos($script,'?') ? '&' : '?'));
	for ($j=$jour; $j<$jour+7;$j++){
		$nom = mktime(0,0,0,$mois,$j,$annee);
		$num = intval(date("d", $nom)) ;
		$numois = date("m",$nom);
		$nomjour = _T('date_jour_'. (1+date('w',$nom)));
		$bandeau .= 
		  "\n\t<th class='calendrier-th'>" .
		  http_href((calendrier_args_date($script, date("Y",$nom), $numois, $num, 'jour', $ancre)),
			    ($nomjour .
			     " " .
			     $num .
			     (($num == 1) ? 'er' : '') .
			     ($ancre  ? ('/' . $numois) : ''))) .
		  "</th>";
	}
	return "\n<tr" .
	  (!isset($couleur_claire) ? "" : " style='background-color: $couleur_claire'") . 
	  ">$bandeau\n</tr>";
}

function http_calendrier_semaine_sept($annee, $mois, $jour, $echelle, $partie_cal, $evt)
{
	global $couleur_claire, $spip_ecran, $spip_lang_left;

	$largeur =  ($spip_ecran == "large") ? 90 : 60;

	$today=date("Ymd");
	$total = '';
	$style = "border-$spip_lang_left: 1px solid $couleur_claire; border-bottom: 1px solid $couleur_claire; border-top: 0px; border-right: 0px;";
	for ($j=$jour; $j<$jour+7;$j++){
		$v = mktime(0,0,0,$mois, $j, $annee);
		$total .= "\n<td class='calendrier-td'>" .
		  http_calendrier_ics($annee,$mois,$j, $echelle, $partie_cal, $largeur, $evt, ($style . ( (date("w",$v)==0 && isset($couleur_claire)) ? 
			  " background-color: $couleur_claire;" :
			  ((date("Ymd", $v) == $today) ? 
			   " background-color: white;" :
			   " background-color: #eeeeee;")))) .
		  "\n</td>";
	}
	return "\n<tr class='calendrier-verdana10'>$total</tr>";
}


function http_calendrier_jour($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre, $evt){
	global $spip_ecran;
	if (!isset($spip_ecran)) $spip_ecran = 'large';

	return 	
	  "\n<table class='calendrier-table-$spip_ecran'>" .
	  "\n<tr><td class='calendrier-td-gauche'></td>" .
	  "<td colspan='5' class='calendrier-td-centre'>" .
	  http_calendrier_navigation($annee, $mois, $jour, $echelle, $partie_cal,
				     (nom_jour("$annee-$mois-$jour") . " " .
				      affdate_jourcourt("$annee-$mois-$jour")),
				     $script,
				     array($annee, $mois, ($jour-1), "jour"),
				     array($annee, $mois, ($jour+1), "jour"),
				     'jour',
				     $ancre) .
	  "</td>" .
	  "<td class='calendrier-td-droit calendrier-arial10'></td>" .
	  "</tr>" .
	  (!is_array($evt) ? $evt :
	   (http_calendrier_jour_noms($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre) .
	    http_calendrier_jour_sept($annee, $mois, $jour, $echelle,  $partie_cal, $script, $ancre, $evt))) .
	  "</table>";
}

function http_calendrier_jour_noms($annee, $mois, $jour, $echelle, $partie_cal, $script, $ancre){

	global $spip_ecran;
	$finurl = "&echelle=$echelle&partie_cal=$partie_cal$ancre";

	$gauche = (_DIR_RESTREINT  || ($spip_ecran != "large"));
	return
	  "\n<tr><td class='calendrier-td-gauche'>" .
	  ($gauche ? '' :
	   http_calendrier_ics_titre($annee,$mois,$jour-1,$script, $finurl)) .
	  "</td><td colspan='5' class='calendrier-td-centre'>" .
	  (_DIR_RESTREINT ? '' :
		   ("\n\t<div class='calendrier-titre'>" .
		    http_calendrier_ics_message($annee, $mois, $jour, true) .
		    '</div>')) .
	  "</td><td class='calendrier-td-droit calendrier-arial10'> " .
	   (_DIR_RESTREINT ? '' : http_calendrier_ics_titre($annee,$mois,$jour+1,$script, $finurl)) .
	  "</td></tr>";
}

function http_calendrier_jour_sept($annee, $mois, $jour, $echelle,  $partie_cal, $script, $ancre, $evt){
	global $spip_ecran;

	$gauche = (_DIR_RESTREINT  || ($spip_ecran != "large"));
	return
	  "<tr class='calendrier-verdana10'>" .
		# afficher en reduction le tableau du jour precedent
	  "\n<td class='calendrier-td-gauche'>" .
	  ($gauche  ? '' :
	   http_calendrier_ics($annee, $mois, $jour-1, $echelle, $partie_cal, 0, $evt)) .
	  "</td><td colspan='5' class='calendrier-td-centre'>" .
	   http_calendrier_ics($annee, $mois, $jour, $echelle, $partie_cal, 300, $evt) .
	  '</td>' .
		# afficher en reduction le tableau du jour suivant
	  "\n<td class='calendrier-td-droit'>" .

	  (_DIR_RESTREINT ? '' :
	   http_calendrier_ics($annee, $mois, $jour+1, $echelle, $partie_cal, 0, $evt)) .
	  '</td>' .
	  "\n</tr>";
}


// Conversion d'un tableau de champ ics en des balises div positionnees    
// Le champ categories indique la Classe de CSS a prendre
// $echelle est le nombre de secondes representees par 1 pixel

function http_calendrier_ics($annee, $mois, $jour,$echelle, $partie_cal,  $largeur, $evt, $style='') {
	global $spip_lang_left;

	// tableau
	if ($partie_cal == "soir") {
		$debut = 12;
		$fin = 23;
	} else if ($partie_cal == "matin") {
		$debut = 4;
		$fin = 15;
	} else {
		$debut = 7;
		$fin =20;
	}
	
	if ($echelle==0) $echelle = DEFAUT_D_ECHELLE;

	list($dimheure, $dimjour, $fontsize, $padding) =
	  calendrier_echelle($debut, $fin, $echelle);
	$modif_decalage = round($largeur/8);

	$date = date("Ymd", mktime(0,0,0,$mois, $jour, $annee));
	list($sansheure, $avecheure) = $evt;
	$avecheure = $avecheure[$date];
	$sansheure = $sansheure[$date];

	$total = '';

	if ($avecheure)
    {
		$tous = 1 + count($avecheure);
		$i = 0;
		foreach($avecheure as $evenement){

			$d = $evenement['DTSTART'];
			$e = $evenement['DTEND'];
			$d_jour = substr($d,0,8);
			$e_jour = $e ? substr($e,0,8) : $d_jour;
			$debut_avant = false;
			$fin_apres = false;
			
			/* disparues sauf erreur
			 $radius_top = " radius-top";
			$radius_bottom = " radius-bottom";
			*/
			if ($d_jour <= $date AND $e_jour >= $date)
			{

			$i++;

			// Verifier si debut est jour precedent
			if (substr($d,0,8) < $date)
			{
				$heure_debut = 0; $minutes_debut = 0;
				$debut_avant = true;
				$radius_top = "";
			}
			else
			{
				$heure_debut = substr($d,-6,2);
				$minutes_debut = substr($d,-4,2);
			}

			if (!$e)
			{ 
				$heure_fin = $heure_debut ;
				$minutes_fin = $minutes_debut ;
				$bordure = "border-bottom: dashed 2px";
			}
			else
			{
				$bordure = "";
				if (substr($e,0,8) > $date) 
				{
					$heure_fin = 23; $minutes_fin = 59;
					$fin_apres = true;
					$radius_bottom = "";
				}
				else
				{
					$heure_fin = substr($e,-6,2);
					$minutes_fin = substr($e,-4,2);
				}
			}
			
			if ($debut_avant && $fin_apres)  $opacity = "-moz-opacity: 0.6; filter: alpha(opacity=60);";
			else $opacity = "";
						
			$haut = calendrier_top ("$heure_debut:$minutes_debut", $debut, $fin, $dimheure, $dimjour, $fontsize);
			$bas =  !$e ? $haut :calendrier_top ("$heure_fin:$minutes_fin", $debut, $fin, $dimheure, $dimjour, $fontsize);
			$hauteur = calendrier_height ("$heure_debut:$minutes_debut", "$heure_fin:$minutes_fin", $debut, $fin, $dimheure, $dimjour, $fontsize);
			if ($bas_prec >= $haut) $decale += $modif_decalage;
			else $decale = (4 * $fontsize);
			if ($bas > $bas_prec) $bas_prec = $bas;
			$url = $evenement['URL']; 
			$desc = propre($evenement['DESCRIPTION']);
			$perso = $evenement['ATTENDEE'];
			$lieu = $evenement['LOCATION'];
			$sum = ereg_replace(' +','&nbsp;', typo($evenement['SUMMARY']));
			if (!$sum) { $sum = $desc; $desc = '';}
			if (!$sum) { $sum = $lieu; $lieu = '';}
			if (!$sum) { $sum = $perso; $perso = '';}
			if ($sum)
			  $sum = "<span class='calendrier-verdana10'><b>$sum</b>$lieu $perso</span>";
			if (($largeur > 90) && $desc)
			  $sum .=  "\n<br /><span class='calendrier-noir'>$desc</span>";
			$colors = $evenement['CATEGORIES'];

			$total .= "\n<div class='calendrier-arial10 $colors' 
	style='cursor: auto; position: absolute; overflow: hidden;$opacity z-index: " .
				$i .
				"; $spip_lang_left: " .
				$decale .
				"px; top: " .
				$haut .
				"px; height: " .
				$hauteur .
				"px; width: ".
				($largeur - 2 * ($padding+1)) .
				"px; font-size: ".
				floor($fontsize * 1.3) .
				"px; padding: " .
				$padding . 
				"px; $bordure'
	onmouseover=\"this.style.zIndex=" . $tous . "\"
	onmouseout=\"this.style.zIndex=" . $i . "\">" .
			  ((!$url) ? 
					$sum :
			   http_href($url, $sum, $desc,"border: 0px",$colors)) . 
				"</div>";
			}
		}
    }
	return
	   "\n<div class='calendrier-jour' style='height: ${dimjour}px; font-size: ${fontsize}px;$style'>\n" .
	  http_calendrier_ics_grille($debut, $fin, $dimheure, $dimjour, $fontsize) .
	  $total .
	  "\n</div>" .
	  (!$sansheure ? "" :
	   http_calendrier_ics_trois($sansheure, $largeur, $dimjour, $fontsize, '')) ;

}

# Affiche une grille horaire 
# Selon l'echelle demandee, on affiche heure, 1/2 heure 1/4 heure, 5minutes.

function http_calendrier_ics_grille($debut, $fin, $dimheure, $dimjour, $fontsize)
{
	global $spip_lang_left, $spip_lang_right;
	$slice = floor($dimheure/(2*$fontsize));
	if ($slice%2) $slice --;
	if (!$slice) $slice = 1;

	$total = '';
	for ($i = $debut; $i < $fin; $i++) {
		for ($j=0; $j < $slice; $j++) 
		{
			$total .= "\n<div class='calendrier-heure" .
				($j  ? "face" : "pile") .
				"' style='$spip_lang_left: 0px; top: ".
				calendrier_top ("$i:".sprintf("%02d",floor(($j*60)/$slice)), $debut, $fin, $dimheure, $dimjour, $fontsize) .
				"px;'>$i:" .
				sprintf("%02d",floor(($j*60)/$slice)) . 
				"</div>";
		}
	}

	return "\n<div class='calendrier-heurepile' style='border: 0px; $spip_lang_left: 0px; top: 2px;'>0:00</div>" .
		$total .
		"\n<div class='calendrier-heurepile' style='$spip_lang_left: 0px; top: ".
		calendrier_top ("$fin:00", $debut, $fin, $dimheure, $dimjour, $fontsize).
		"px;'>$fin:00</div>" .
		"\n<div class='calendrier-heurepile' style='border: 0px; $spip_lang_left: 0px; top: ".
		($dimjour - $fontsize - 2) .
		"px;'>23:59</div>";
}

# si la largeur le permet, les evenements sans duree, 
# se placent a cote des autres, sinon en dessous

function http_calendrier_ics_trois($evt, $largeur, $dimjour, $fontsize, $border)
{
	global $spip_lang_left; 

	$types = array();
	foreach($evt as $v)	$types[$v['CATEGORIES']][] = $v;
	$res = '';
	foreach ($types as $k => $v) {
	  $res2 = '';
	  foreach ($v as $evenement) {
	    $res2 .= http_calendrier_sans_heure($evenement);
	  }
	  $res .= "\n<div class='calendrier-verdana10 calendrier-titre'>".
	    _T($k) .
	    "</div>" .
	    $res2;
	}
		
	$pos = ((_DIR_RESTREINT || ($largeur > 90)) ? "-$dimjour" : 0);
	if ($largeur > 90) $largeur += (5*$fontsize);
	else $largeur = _DIR_RESTREINT ? (3*$fontsize) : 0;
	  
	return "\n<div style='position: relative; z-index: 2; top: ${pos}px; margin-$spip_lang_left: " . $largeur . "px'>$res</div>";
}

function http_calendrier_ics_titre($annee, $mois, $jour,$script, $finurl='')
{
	$date = mktime(0,0,0,$mois, $jour, $annee);
	$jour = date("d",$date);
	$mois = date("m",$date);
	$annee = date("Y",$date);

	return "<div class='calendrier-arial10 calendrier-titre'>" .
	  http_href(calendrier_args_date($script, $annee, $mois, $jour, 'jour', $finurl),
		    affdate_jourcourt("$annee-$mois-$jour"),
		    '', '', 'calendrier-noir') .
	  "</div>";
}

function http_calendrier_ics_message($annee, $mois, $jour, $large)
{	
  global $bleu, $vert,$jaune;
  $b = _T("lien_nouvea_pense_bete");
  $v = _T("lien_nouveau_message");
  $j=  _T("lien_nouvelle_annonce");
  $href = "message_edit.php3?rv=$annee-$mois-$jour&new=oui";
  return 
    http_href("$href&type=pb", 
	      $bleu . ($large ? $b : ''), 
	      $b,
	      'color: blue;',
	      'calendrier-arial10') .
    "\n" .
    http_href("$href&type=normal",
	      $vert . ($large ? $v : ''), 
	      $v,
	      'color: green;',
	      'calendrier-arial10') .
    (($GLOBALS['connect_statut'] != "0minirezo") ? "" :
     ("\n" .
      http_href("$href&type=affich",
		$jaune . ($large ? $j : ''), 
		$j,
		'color: #ff9900;',
		'calendrier-arial10')));
}

function http_calendrier_sans_date($annee, $mois, $evenements)
{
  $r = $evenements[0+($annee . $mois . "00")];
  if (!$r) return "";
  $res = "\n<div class='calendrier-arial10 calendrier-titre'>".
    _T('info_mois_courant').
    "</div>";
  foreach ($r as $evenement) $res .= http_calendrier_sans_heure($evenement);
  return $res;
}


function http_calendrier_sans_heure($evenement)
{
	if ($evenement['CATEGORIES'] == 'info_articles')
	  $i = 'puce-verte-breve.gif';
	elseif ($evenement['CATEGORIES'] == 'info_breves')
	  $i = 'puce-blanche-breve.gif';
	else
	  $i = 'puce-orange-breve.gif';
	$desc = propre($evenement['DESCRIPTION']);
	$sum = $evenement['SUMMARY'];
	if (!$sum) $sum = $desc;
	$sum = http_img_pack($i, $desc,  "style='width: 8px; height: 9px; border: 0px'") . '&nbsp;' . $sum;
	if ($evenement['URL']) {
		$sum = http_href($evenement['URL'], $sum, $desc);
	}
	return "\n<div class='calendrier-noir calendrier-arial10'>$sum\n</div>\n"; 
}

function http_calendrier_avec_heure($evenement, $amj)
{
	$jour_debut = substr($evenement['DTSTART'], 0,8);
	$jour_fin = substr($evenement['DTEND'], 0, 8);
	if ($jour_fin <= 0) $jour_fin = $jour_debut;
	if (($jour_debut <= 0) OR ($jour_debut > $amj) OR ($jour_fin < $amj))
	  return "";
	
	$desc = propre($evenement['DESCRIPTION']);
	$sum = $evenement['SUMMARY'];
	if (!$sum) $sum = $desc;
	$sum = "<span class='calendrier-noir'>" .
	  ereg_replace(' +','&nbsp;', typo($sum)) .
	  "</span>";
	if ($evenement['URL'])
	  $sum = http_href($evenement['URL'], $sum, $desc);
	$opacity = "";
	$deb_h = substr($evenement['DTSTART'],-6,2);
	$deb_m = substr($evenement['DTSTART'],-4,2);
	$fin_h = substr($evenement['DTEND'],-6,2);
	$fin_m = substr($evenement['DTEND'],-4,2);
	
	if ($deb_h >0 OR $deb_m > 0) {
	  if ((($deb_h > 0) OR ($deb_m > 0)) AND $amj == $jour_debut)
	    { $deb = '<b>' . $deb_h . ':' . $deb_m . '</b> ';}
	  else { 
	    $deb = '...'; 
	  }
	  
	  if ((($fin_h > 0) OR ($fin_m > 0)) AND $amj == $jour_fin)
	    { $fin = '<b>' . $fin_h . ':' . $fin_m . '</b> ';}
	  else { 
	    $fin = '...'; 
	  }
	  
	  if ($amj == $jour_debut OR $amj == $jour_fin) {
	    $sum = "<div>$deb-$fin</div>$sum";
	  } else {
	    $opacity ='calendrier-opacity';
	  }
	}
	return "\n<div class='$opacity calendrier-evenement calendrier-arial10 " . $evenement['CATEGORIES'] ."'>$sum\n</div>\n"; 
}

function http_calendrier_aide_mess()
{
  global $bleu, $vert, $jaune, $spip_lang_left;
  return
   "\n<br /><br /><br />\n<font face='arial,helvetica,sans-serif' size='2'><table width='700'>\n<tr><th style='text-align: $spip_lang_left'> " .
    "<b>"._T('info_aide')."</b>" .
    "</th></tr><tr><td>$bleu\n"._T('info_symbole_bleu')."\n" .
    "</td></tr><tr><td>$vert\n"._T('info_symbole_vert')."\n" .
    "</th></tr><tr><td>$jaune\n"._T('info_symbole_jaune')."\n" .
    "</td></tr>\n</table></font>";
 }

# Bandeau superieur d'un calendrier selon son $type (jour/mois/annee):
# 2 icones vers les 2 autres types, a la meme date $jour $mois $annee
# 2 icones de loupes pour zoom sur la meme date et le meme type
# 2 fleches appelant le $script sur les periodes $pred/$suiv avec une $ancre
# et le $nom du calendrier

function http_calendrier_navigation($annee, $mois, $jour, $echelle, $partie_cal, $nom, $script, $args_pred, $args_suiv, $type, $ancre)
{
	global $spip_lang_right, $spip_lang_left, $couleur_foncee;

	if (!$echelle) $echelle = DEFAUT_D_ECHELLE;

	if ($args_pred) {
	  list($a, $m, $j, $t) = $args_pred;
	  $args_pred = calendrier_args_date($script, $a, $m, $j, $t, "&echelle=$echelle&partie_cal=$partie_cal$ancre");
	}

	if ($args_suiv) {
	  list($a, $m, $j, $t) = $args_suiv;
	  $args_suiv = calendrier_args_date($script, $a, $m, $j, $t, "&echelle=$echelle&partie_cal=$partie_cal$ancre");
	}

	$args_e = calendrier_args_date($script, $annee, $mois, $jour, $type, "&echelle=$echelle");
	$args_p = calendrier_args_date($script, $annee, $mois, $jour, $type, "&partie_cal=$partie_cal");
	$today=getdate(time());
	$jour_today = $today["mday"];
	$mois_today = $today["mon"];
	$annee_today = $today["year"];

	$id = 'nav-agenda' .ereg_replace('[^A-Za-z0-9]', '', $ancre);

	return 
	  "<div class='navigation-calendrier calendrier-moztop8'" 
	  . (!isset($couleur_foncee) ? "" : "\nstyle='background-color: $couleur_foncee;'")
	  . "><div style='float: $spip_lang_right; padding-left: 5px; padding-right: 5px;'>"
	  . (($type == "mois") ? '' :
	     (
		  http_href_img(("$args_e&partie_cal=tout$ancre"),
				 "heures-tout.png",
				 "class='calendrier-png" .
				 (($partie_cal == "tout") ? " calendrier-opacity'" : "'"),
				 _T('cal_jour_entier'))
		  .http_href_img(("$args_e&partie_cal=matin$ancre"),
				 "heures-am.png",
				 "class='calendrier-png" .
				 (($partie_cal == "matin") ? " calendrier-opacity'" : "'"),
				 _T('cal_matin'))

		  .http_href_img(("$args_e&partie_cal=soir$ancre"),
				 "heures-pm.png", 
				 "class='calendrier-png" .
				 (($partie_cal == "soir") ? " calendrier-opacity'" : "'"),
				 _T('cal_apresmidi'))
		  . "&nbsp;"
		  . http_href_img(("$args_p&echelle=" .
					  floor($echelle * 1.5)) . $ancre,
					 "loupe-moins.gif",
					 '',
					 _T('info_zoom'). '-')
		  . http_href_img(("$args_p&echelle=" .
					  floor($echelle / 1.5)) . $ancre, 
					 "loupe-plus.gif",
					 '', 
					 _T('info_zoom'). '+')
		  ))
	  . http_href_img(calendrier_args_date($script,$annee, $mois, $jour, "jour", "&echelle=$echelle&partie_cal=$partie_cal$ancre"),"cal-jour.gif",
			  (($type == 'jour') ? " class='calendrier-opacity'" : ''),
			  _T('cal_par_jour'))

	  . http_href_img(calendrier_args_date($script,$annee, $mois, $jour, "semaine", "&echelle=$echelle&partie_cal=$partie_cal$ancre"), "cal-semaine.gif", 
			  (($type == 'semaine') ?  " class='calendrier-opacity'" : "" ),
			  _T('cal_par_semaine'))

	  . http_href_img(calendrier_args_date($script,$annee, $mois, $jour, "mois", "&echelle=$echelle&partie_cal=$partie_cal$ancre"),"cal-mois.gif",
			  (($type == 'mois') ? " class='calendrier-opacity'" : "" ),
			  _T('cal_par_mois'))
	  . "</div>"
	  . "&nbsp;&nbsp;"
	  . http_href_img(calendrier_args_date($script,$annee_today, $mois_today, $jour_today, $type, "&echelle=$echelle&partie_cal=$partie_cal$ancre"),
			  "cal-today.gif",
			  (" onmouseover=\"montrer('$id');\"" .
			   (($annee == $annee_today && $mois == $mois_today && (($type == 'mois')  || ($jour == $jour_today)))
			    ? " class='calendrier-opacity'" : "")),
			  _T("ecrire:info_aujourdhui"))
	  . "&nbsp;"
	  . (!$args_pred ? '' :
	     http_href($args_pred,
		       http_img_pack("fleche-$spip_lang_left.png", '&lt;&lt;&lt;', "class='calendrier-png'"),
		       _T('precedent')))
	  . (!$args_suiv ? '' :
	     http_href($args_suiv,
		       http_img_pack("fleche-$spip_lang_right.png",  '&gt;&gt;&gt;', "class='calendrier-png'"),
		       _T('suivant')))
	  . "&nbsp;&nbsp;"
	  . $nom
	  . (_DIR_RESTREINT ? '' :  aide("messcalen"))
	  . "</div>"
	  . http_calendrier_invisible($annee, $mois, $jour, $script, "&echelle=$echelle&partie_cal=$partie_cal$ancre", $id);
}


// fabrique un petit agenda accessible par survol

function http_calendrier_invisible($annee, $mois, $jour, $script, $ancre, $id)
{
	global $spip_lang_right, $spip_lang_left, $couleur_claire;
	$gadget = "<div style='position: relative;z-index: 1000;'
			onmouseover=\"montrer('$id');\"
			onmouseout=\"cacher('$id');\">"
	  . "<table id='$id' class='calendrier-cadreagenda'"
	  . (!isset($couleur_claire) ? "" : " style='background-color: $couleur_claire'")
	  . ">\n<tr><td colspan='3' style='text-align:$spip_lang_left;'>";

	$annee_avant = $annee - 1;
	$annee_apres = $annee + 1;

	for ($i=$mois; $i < 13; $i++) {
	  $gadget .= http_href(calendrier_args_date($script,$annee_avant, $i, 1, "mois", $ancre),
				     nom_mois("$annee_avant-$i-1"),'','', 'calendrier-annee') ;
			}
	for ($i=1; $i < $mois - 1; $i++) {
	  $gadget .= http_href(calendrier_args_date($script,$annee, $i, 1, "mois", $ancre),
					nom_mois("$annee-$i-1"),'','', 'calendrier-annee');
			}
	$gadget .= "</td></tr>"
		. "\n<tr><td class='calendrier-tripleagenda'>"
		. http_calendrier_agenda($annee, $mois-1, $jour, $mois, $annee, $GLOBALS['afficher_bandeau_calendrier_semaine'], $script,$ancre) 
		. "</td>\n<td class='calendrier-tripleagenda'>"
	  . http_calendrier_agenda($annee, $mois, $jour, $mois, $annee, $GLOBALS['afficher_bandeau_calendrier_semaine'], $script,$ancre) 
		. "</td>\n<td class='calendrier-tripleagenda'>"
	  . http_calendrier_agenda($annee, $mois+1, $jour, $mois, $annee, $GLOBALS['afficher_bandeau_calendrier_semaine'], $script,$ancre) 
		. "</td>"
		. "</tr>"
		. "\n<tr><td colspan='3' style='text-align:$spip_lang_right;'>";
	for ($i=$mois+2; $i <= 12; $i++) {
	  $gadget .= http_href(calendrier_args_date($script,$annee, $i, 1, "mois", $ancre),
					nom_mois("$annee-$i-1"),'','', 'calendrier-annee');
			}
	for ($i=1; $i < $mois+1; $i++) {
	  $gadget .= http_href(calendrier_args_date($script, $annee_apres, $i, 1, "mois", $ancre),
					nom_mois("$annee_apres-$i-1"),'','', 'calendrier-annee');
			}
	return $gadget . "</td></tr></table></div>";
}

// agenda mensuel 

function http_calendrier_agenda ($annee, $mois, $jour_ved, $mois_ved, $annee_ved, $semaine = false,  $script='', $ancre='', $evt='') {

  if (!$script) $script =  $GLOBALS['PHP_SELF'] ;
  if (!strpos($script, '?')) $script .= '?';
  if (!$mois) {$mois = 12; $annee--;}
  elseif ($mois==13) {$mois = 1; $annee++;}
  if (!$evt) $evt = sql_calendrier_agenda($annee, $mois);
  return 
    "<div class='calendrier-titre calendrier-arial10'>" .
    http_href(calendrier_args_date($script, $annee, $mois, 1, 'mois', $ancre),
	      affdate_mois_annee("$annee-$mois-1"),
	      '',
	      'color: black;') .
    "<table width='100%' cellspacing='0' cellpadding='0'>" .
    http_calendrier_agenda_rv ($annee, $mois, $evt,				
			        'http_calendrier_clic', array($script, $ancre),
			        $jour_ved, $mois_ved, $annee_ved, 
				$semaine) . 
    "</table>" .
    "</div>";
}

function http_calendrier_clic($annee, $mois, $jour, $type, $couleur, $perso)
{

  list($script, $ancre) = $perso;

  return http_href(calendrier_args_date($script, $annee, $mois, $jour,$type, $ancre), 
		   $jour,
		   '',
		   "color: $couleur; font-weight: bold");
}

// typographie un mois sous forme d'un tableau de 7 colonnes

function http_calendrier_agenda_rv ($annee, $mois, $les_rv, $fclic, $perso='',
				    $jour_ved='', $mois_ved='', $annee_ved='',
				    $semaine='') {
	global $couleur_foncee, $spip_lang_left, $spip_lang_right;

	// Former une date correcte (par exemple: $mois=13; $annee=2003)
	$date_test = date("Y-m-d", mktime(0,0,0,$mois, 1, $annee));
	$mois = mois($date_test);
	$annee = annee($date_test);
	if ($semaine) 
	{
		$jour_semaine_valide = date("w",mktime(1,1,1,$mois_ved,$jour_ved,$annee_ved));
		if ($jour_semaine_valide==0) $jour_semaine_valide=7;
		$debut = mktime(1,1,1,$mois_ved,$jour_ved-$jour_semaine_valide+1,$annee_ved);
		$fin = mktime(1,1,1,$mois_ved,$jour_ved-$jour_semaine_valide+7,$annee_ved);
	} else { $debut = $fin = '';}
	
	$today=getdate(time());
	$jour_today = $today["mday"];
	$cemois = ($mois == $today["mon"] AND $annee ==  $today["year"]);

	$total = '';
	$ligne = '';
	$jour_semaine = date("w", mktime(1,1,1,$mois,1,$annee));
	if ($jour_semaine==0) $jour_semaine=7;
	for ($i=1;$i<$jour_semaine;$i++) $ligne .= "\n\t<td></td>";
	$style0 = (!isset($couleur_foncee)) ? "" : " style='border: 1px solid $couleur_foncee;'";
	for ($j=1; (checkdate($mois,$j,$annee)); $j++) {
		$style = "";
		$nom = mktime(1,1,1,$mois,$j,$annee);
		$jour_semaine = date("w",$nom);
		if ($jour_semaine==0) $jour_semaine=7;

		if ($j == $jour_ved AND $mois == $mois_ved AND $annee == $annee_ved) {
		  $class= 'calendrier-arial11 calendrier-demiagenda';
		  $type = 'jour';
		  $couleur = "black";
		  } else if ($semaine AND $nom >= $debut AND $nom <= $fin) {
		  $class= 'calendrier-arial11 calendrier-demiagenda' . 
 		      (($jour_semaine==1) ? " calendrier-$spip_lang_left"  :
		       (($jour_semaine==7) ? " calendrier-$spip_lang_right" :
			''));
		  $type = ($semaine ? 'semaine' : 'jour') ;
		  $couleur = "black";
		} else {
		  if ($j == $jour_today AND $cemois) {
			$style = $couleur_foncee;
			if(!$style) $style = '#333333';
			$couleur = "white";
		    } else {
			if ($jour_semaine == 7) {
				$style = "#aaaaaa";
				$couleur = 'white';
			} else {
				$style = "#ffffff";
				$couleur = "#aaaaaa";
			}
			if ($les_rv[$j] > 0) {
			  $style = "#ffffff";
			  $couleur = "black";
			}
		  }
		  $class= 'calendrier-arial11 calendrier-agenda';
		  $type = ($semaine ? 'semaine' : 'jour') ;
		}
		if ($style)
		  $style = " style='background-color: $style'";
		else $style = $style0;
		$ligne .= "\n\t<td><div class='$class'$style>" .
		  $fclic($annee,$mois, $j, $type, $couleur, $perso) .
		  "</div></td>";
		if ($jour_semaine==7) 
		    {
		      $total .= "\n<tr>$ligne\n</tr>";
		      $ligne = '';
		    }
	}
	return $total . (!$ligne ? '' : "\n<tr>$ligne\n</tr>");
}

// Fonction pour la messagerie et ecrire/index.php

function http_calendrier_rv($messages, $type) {
	global $spip_lang_rtl, $spip_lang_left, $spip_lang_right;

	$total = '';
	if (!$messages) return $total;
	foreach ($messages as $row) {
		if (ereg("^=([^[:space:]]+)$",$row['texte'],$match))
			$url = $match[1];
		else
			$url = "message.php3?id_message=".$row['id_message'];

		$rv = ($row['rv'] == 'oui');
		$date = $row['date_heure'];
		$date_fin = $row['date_fin'];
		if ($row['type']=="pb") $bouton = "pense-bete";
		else if ($row['type']=="affich") $bouton = "annonce";
		else $bouton = "message";

		if ($rv) {
			$date_jour = affdate_jourcourt($date);
			$total .= "<tr><td colspan='2'>" .
				(($date_jour == $date_rv) ? '' :
				"<div  class='calendrier-arial11'><b>$date_jour</b></div>") .
				"</td></tr>";
		}

		$total .= "<tr><td style='width: 24px' valign='middle'>" .
		http_href($url,
				     ($rv ?
				      http_img_pack("rv.gif", 'rv',
						    http_style_background($bouton . '.gif', "no-repeat;' border='0'")) : 
				      http_img_pack($bouton.".gif", $bouton, "style='border: 0px'")),
				     '', '') .
		"</td>" .
		"<td valign='middle'>" .
		((!$rv) ? '' :
		((affdate($date) == affdate($date_fin)) ?
		 ("<div class='calendrier-arial9 fond-agenda'>"
		  . heures($date).":".minutes($date)."<br />"
		  . heures($date_fin).":".minutes($date_fin)."</div>") :
		( "<div class='calendrier-arial9 fond-agenda' style='text-align: center;'>"
		  . heures($date).":".minutes($date)."<br />...</div>" ))) .
		"<div><b>" .
		  http_href($url, typo($row['titre']), '', '', 'calendrier-verdana10') .
		"</b></div>" .
		"</td>" .
		"</tr>\n";

		$date_rv = $date_jour;
	}

	if ($type == 'annonces') {
		$titre = _T('info_annonces_generales');
		$couleur_titre = "ccaa00";
		$couleur_texte = "black";
		$couleur_fond = "#ffffee";
	}
	else if ($type == 'pb') {
		$titre = _T('infos_vos_pense_bete');
		$couleur_titre = "#3874B0";
		$couleur_fond = "#EDF3FE";
		$couleur_texte = "white";
	}
	else if ($type == 'rv') {
		$titre = _T('info_vos_rendez_vous');
		$couleur_titre = "#666666";
		$couleur_fond = "#eeeeee";
		$couleur_texte = "white";
	}

	return
	  debut_cadre_enfonce("", true, "", $titre) .
	  "<table width='100%' border='0' cellpadding='0' cellspacing='2'>" .
	  $total .
	  "</table>" .
	  fin_cadre_enfonce(true);
}



//------- fonctions d'appel MySQL. 
// au dela cette limite, pas de production HTML

function sql_calendrier_mois($annee,$mois,$jour) {
	$avant = "'" . date("Y-m-d", mktime(0,0,0,$mois,1,$annee)) . "'";
	$apres = "'" . date("Y-m-d", mktime(0,0,0,$mois+1,1,$annee)) .
	" 00:00:00'";
	return array($avant, $apres);
}

function sql_calendrier_semaine($annee,$mois,$jour) {
	$w_day = date("w", mktime(0,0,0,$mois, $jour, $annee));
	if ($w_day == 0) $w_day = 7; // Gaffe: le dimanche est zero
	$debut = $jour-$w_day;
	$avant = "'" . date("Y-m-d", mktime(0,0,0,$mois,$debut,$annee)) . "'";
	$apres = "'" . date("Y-m-d", mktime(1,1,1,$mois,$debut+7,$annee)) .
	" 23:59:59'";
	return array($avant, $apres);
}

// ici on prend en fait le jour, la veille et le lendemain

function sql_calendrier_jour($annee,$mois,$jour) {
	$avant = "'" . date("Y-m-d", mktime(0,0,0,$mois,$jour-1,$annee)) . "'";
	$apres = "'" . date("Y-m-d", mktime(1,1,1,$mois,$jour+1,$annee)) .
	" 23:59:59'";
	return array($avant, $apres);
}

// retourne un tableau de 2 tableaux indexes par des dates
// - le premier indique les evenements du jour, sans indication de duree
// - le deuxime indique les evenements commencant ce jour, avec indication de duree

function sql_calendrier_interval($limites) {
	list($avant, $apres) = $limites;
	$evt = array();
	sql_calendrier_interval_articles($avant, $apres, $evt);
	sql_calendrier_interval_breves($avant, $apres, $evt);
	return array($evt, sql_calendrier_interval_rv($avant, $apres));
}

function  sql_calendrier_interval_forums($limites, &$evenements) {
	list($avant, $apres) = $limites;
	$result=spip_query("
SELECT	DISTINCT titre, date_heure, id_article
FROM	spip_forum
WHERE	date_heure >= $avant
 AND	date_heure < $apres
ORDER BY date_heure
");
	while($row=spip_fetch_array($result)){
		$amj = date_anneemoisjour($row['date_heure']);
		if (_DIR_RESTREINT)
		  {
		    $script = 'article';
		    $id = $row['id_article'];
		  }
		else {
		    $script = 'articles_forum';
		    $id = $row['id_article'];
		}
		$evenements[$amj][]=
		array(
			'URL' => $script . _EXTENSION_PHP . "?id_article=$id",
			'CATEGORIES' => 'info_liens_syndiques_3',
			'SUMMARY' => $row['titre']);
	}
}

# 3 fonctions retournant les evenements d'une periode
# le tableau retourne est indexe par les balises du format ics
# afin qu'il soit facile de produire de tels documents.
# Pour les articles post-dates vus de l'espace public,
# on regarde si c'est une redirection pour avoir une url interessante
# sinon on prend " ", c'est-a-dire la page d'appel du calendrier

function sql_calendrier_interval_articles($avant, $apres, &$evenements) {
	
	$result=spip_query("
SELECT	id_article, titre, date, descriptif, chapo
FROM	spip_articles
WHERE	statut='publie'
 AND	date >= $avant
 AND	date < $apres
ORDER BY date
");
	if (!_DIR_RESTREINT)
	  $script = 'articles' . _EXTENSION_PHP . "?id_article=";
	else
	  {
	    $now = date("Ymd");
	    $script = 'article' . _EXTENSION_PHP . "?id_article=";
	  }

	while($row=spip_fetch_array($result)){
		$amj = date_anneemoisjour($row['date']);
		$url = generer_url_article($row['id_article']);
		$evenements[$amj][]=
		    array(
			'CATEGORIES' => 'info_articles',
			'DESCRIPTION' => $row['descriptif'],
			'SUMMARY' => $row['titre'],
			'URL' =>  $url);
	}
}

function sql_calendrier_interval_breves($avant, $apres, &$evenements) {
	$result=spip_query("
SELECT	id_breve, titre, date_heure
FROM	spip_breves
WHERE	statut='publie'
 AND	date_heure >= $avant
 AND	date_heure < $apres
ORDER BY date_heure
");
	while($row=spip_fetch_array($result)){
		$amj = date_anneemoisjour($row['date_heure']);
		$script = generer_url_breve($row['id_breve']);
		$evenements[$amj][]=
		array(
			'URL' => $script,
			'CATEGORIES' => 'info_breves_02',
			'SUMMARY' => $row['titre']);
	}
}

function sql_calendrier_interval_rv($avant, $apres) {
	global $connect_id_auteur;
	$evenements= array();
	if (!$connect_id_auteur) return $evenements;
	$result=spip_query("
SELECT	messages.id_message, messages.titre, messages.texte,
	messages.date_heure, messages.date_fin, messages.type
FROM	spip_messages AS messages, 
	spip_auteurs_messages AS lien
WHERE	((lien.id_auteur='$connect_id_auteur'
 AND	lien.id_message=messages.id_message) OR messages.type='affich')
 AND	messages.rv='oui' 
 AND	((messages.date_fin >= $avant OR messages.date_heure >= $avant) AND messages.date_heure <= $apres)
 AND	messages.statut='publie'
GROUP BY messages.id_message
ORDER BY messages.date_heure
");
	while($row=spip_fetch_array($result)){
		$date_heure=$row["date_heure"];
		$date_fin=$row["date_fin"];
		$type=$row["type"];
		$id_message=$row['id_message'];

		if ($type=="pb")
		  $cat = 'calendrier-couleur2';
		else {
		  if ($type=="affich")
		  $cat = 'calendrier-couleur4';
		  else {
		    if ($type!="normal")
		      $cat = 'calendrier-couleur12';
		    else {
		      $cat = 'calendrier-couleur9';
		      $auteurs = array();
		      $result_aut=spip_query("
SELECT	auteurs.nom 
FROM	spip_auteurs AS auteurs,
	spip_auteurs_messages AS lien 
WHERE	(lien.id_message='$id_message' 
  AND	(auteurs.id_auteur!='$connect_id_auteur'
  AND	lien.id_auteur=auteurs.id_auteur))");
			while($row_auteur=spip_fetch_array($result_aut)){
				$auteurs[] = $row_auteur['nom'];
			}
		    }
		  }
		}

		$jour_avant = substr($avant, 9,2);
		$mois_avant = substr($avant, 6,2);
		$annee_avant = substr($avant, 1,4);
		$jour_apres = substr($apres, 9,2);
		$mois_apres = substr($apres, 6,2);
		$annee_apres = substr($apres, 1,4);
		$ical_apres = date_anneemoisjour("$annee_apres-$mois_apres-".sprintf("%02d",$jour_apres));

		// Calcul pour les semaines a cheval sur deux mois 
 		$j = 0;
		$amj = date_anneemoisjour("$annee_avant-$mois_avant-".sprintf("%02d", $j+($jour_avant)));

		while ($amj <= $ical_apres) {
		if (!($amj == date_anneemoisjour($date_fin) AND ereg("00:00:00", $date_fin)))  // Ne pas prendre la fin a minuit sur jour precedent
			$evenements[$amj][$id_message]=
			  array(
				'URL' => "message.php3?id_message=$id_message",
				'DTSTART' => date_ical($date_heure),
				'DTEND' => date_ical($date_fin),
				'DESCRIPTION' => $row['texte'],
				'SUMMARY' => $row['titre'],
				'CATEGORIES' => $cat,
				'ATTENDEE' => (count($auteurs) == 0) ? '' : join($auteurs,", "));
			
			$j ++; 
			$ladate = date("Y-m-d",mktime (1,1,1,$mois_avant, ($j + $jour_avant), $annee_avant));
			
			$amj = date_anneemoisjour($ladate);

		}

	}
  return $evenements;
}

// fonction SQL, pour la messagerie

function sql_calendrier_taches_annonces () {
	global $connect_id_auteur;
	$r = array();
	if (!$connect_id_auteur) return $r;
	$result = spip_query("
SELECT * FROM spip_messages 
WHERE type = 'affich' AND rv != 'oui' AND statut = 'publie' ORDER BY date_heure DESC");
	if (spip_num_rows($result) > 0)
		while ($x = spip_fetch_array($result)) $r[] = $x;
	return $r;
}

function sql_calendrier_taches_pb () {
	global $connect_id_auteur;
	$r = array();
	if (!$connect_id_auteur) return $r;
	$result = spip_query("
SELECT * FROM spip_messages AS messages 
WHERE id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND rv!='oui'");
	if (spip_num_rows($result) > 0){
	  $r = array();
	  while ($x = spip_fetch_array($result)) $r[] = $x;
	}
	return $r;
}

function sql_calendrier_taches_rv () {
	global $connect_id_auteur;
	$r = array();
	if (!$connect_id_auteur) return $r;
	$result = spip_query("
SELECT messages.* 
FROM spip_messages AS messages, spip_auteurs_messages AS lien 
WHERE ((lien.id_auteur='$connect_id_auteur' 
	AND lien.id_message=messages.id_message) 
	OR messages.type='affich') 
AND messages.rv='oui'
AND ( (messages.date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY) 
	AND messages.date_heure < DATE_ADD(NOW(), INTERVAL 1 MONTH))
	OR (messages.date_heure < NOW() AND messages.date_fin > NOW() ))
AND messages.statut='publie' 
GROUP BY messages.id_message 
ORDER BY messages.date_heure");
	if (spip_num_rows($result) > 0){
	  $r = array();
	  while ($x = spip_fetch_array($result)) $r[] = $x;
	}
	return  $r;
}

function sql_calendrier_agenda ($annee, $mois) {
	global $connect_id_auteur;

	$rv = array();
	if (!$connect_id_auteur) return $rv;
	$date = date("Y-m-d", mktime(0,0,0,$mois, 1, $annee));
	$mois = mois($date);
	$annee = annee($date);

	// rendez-vous personnels dans le mois
	$result_messages=spip_query("SELECT messages.date_heure FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.date_heure >='$annee-$mois-1' AND date_heure < DATE_ADD('$annee-$mois-1', INTERVAL 1 MONTH) AND messages.statut='publie'");
	while($row=spip_fetch_array($result_messages)){
		$rv[journum($row['date_heure'])] = 1;
	}
	return $rv;
}

?>