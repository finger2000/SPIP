<?php

include_once("inc.php3");

// Gestion d'expiration de ce jaja
$expire = $date + 3600*24;

$headers_only = http_last_modified($expire);

$date = gmdate("D, d M Y H:i:s", $date);
$expire = gmdate("D, d M Y H:i:s", $expire);
@Header ("Content-Type: text/javascript");
if ($headers_only) exit;
@Header ("Last-Modified: ".$date." GMT");
@Header ("Expires: ".$expire." GMT");

function extraire_article($id_p) {
	if (array_key_exists($id_p, $GLOBALS['db_art_cache'])) {
		return $GLOBALS['db_art_cache'][$id_p];
	} else {
		return array();
	}
}

function gen_liste_rubriques() {
	$q = "SELECT id_rubrique, id_parent, titre 
		FROM spip_rubriques 
		ORDER BY id_parent, titre";

	$res = spip_query($q);

	$GLOBALS['db_art_cache'] = array();
	if (spip_num_rows($res) > 0) { 
		while ($row = spip_fetch_array($res)) {
			$parent = $row['id_parent'];
			$id = $row['id_rubrique'];
			$GLOBALS['db_art_cache'][$parent][$id] = $row['titre'];
		}
	}
}


function bandeau_menu() {
	global $spip_ecran;

	gen_liste_rubriques(); 
	$arr_low = extraire_article(0);

	$i = sizeof($arr_low);

	$total_lignes = $i;
	if ($spip_ecran == "large") $max_lignes = 20;
	else $max_lignes = 15;

	$nb_col = ceil($total_lignes / $max_lignes);
	if ($nb_col < 1) $nb_col = 1;
	$max_lignes = ceil($total_lignes / $nb_col);

	$count_lignes = 0;

	if ($i > 0) {
		$ret = "<div>&nbsp;</div>";
		$ret .= "<div class='bandeau_rubriques' style='z-index: 1;'>";
		foreach( $arr_low as $id_rubrique => $titre_rubrique) {

			if ($count_lignes == $max_lignes) {
				$count_lignes = 0;
				$ret .= "</div></td><td valign='top' width='200'><div>&nbsp;</div><div class='bandeau_rubriques' style='z-index: 1;'>";
			}
			$count_lignes ++;

			$titre_rubrique = supprimer_numero(typo($titre_rubrique));
			$ret .= bandeau_rubrique($id_rubrique, $titre_rubrique, $i);
			$i = $i - 1;
		}
		$ret .= "</div>";
	}
	unset($GLOBALS['db_art_cache']); // On lib�re la m�moire
	return $ret;
}


function bandeau_rubrique($id_rubrique, $titre_rubrique, $z = 1) {
	global $zdecal;
	global $spip_ecran, $spip_display;
	global $spip_lang, $spip_lang_rtl, $spip_lang_left, $spip_lang_right;

	$titre_rubrique = preg_replace(',[\x00-\x1f]+,', ' ', $titre_rubrique);

	// Calcul du nombre max de sous-menus
	$zdecal = $zdecal + 1;
	if ($spip_ecran == "large") $zmax = 8;
	else $zmax= 6;
	
	// Limiter volontairement le nombre de sous-menus 
	$zmax = 6;

	if ($zindex < 1) $zindex = 1;
	if ($zdecal == 1) $image = "secteur-12.gif";
	//else $image = "rubrique-12.gif";
	else $image = '';
	
	if (strlen($image) > 1) $image = " style='background-image:url(" . _DIR_IMG_PACK . $image .");'";


	$arr_rub = extraire_article($id_rubrique);

	$i = sizeof($arr_rub);
	if ($i > 0 AND $zdecal < $zmax) {
		$ret .= '<div class=\"pos_r\" style=\"z-index: '.$z.';\" onMouseOver=\"montrer(\'b_'.$id_rubrique.'\');\" onMouseOut=\"cacher(\'b_'.$id_rubrique.'\');\">';
		$ret .= '<div class=\"brt\"><a href=\"naviguer.php3?id_rubrique='.$id_rubrique.'\" class=\"bandeau_rub\"'.$image.'>'.addslashes(supprimer_tags($titre_rubrique)).'</a></div>';
		$ret .= '<div class=\"bandeau_rub\" style=\"z-index: '.($z+1).';\" id=\"b_'.$id_rubrique.'\">';
		foreach( $arr_rub as $id_rub => $titre_rub) {
			$titre_rub = supprimer_numero(typo($titre_rub));
			$ret .= bandeau_rubrique($id_rub, $titre_rub, ($z+$i));
			$i = $i - 1;
		}
		$ret .= "</div></div>";
	} else {
		$ret .= '<div><a href=\"naviguer.php3?id_rubrique='.$id_rubrique.'\" class=\"bandeau_rub\"'.$image.'>'.addslashes(supprimer_tags($titre_rubrique)).'</a></div>';
	}
	$zdecal = $zdecal - 1;
	return $ret;
}

echo "document.write(\"";
echo "<table><tr><td valign='top' width='200'>";
echo bandeau_menu();
echo "</td></tr></table>";
echo "\");\n";

?>
