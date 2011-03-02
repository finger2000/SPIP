<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;


// http://doc.spip.org/@generer_action_auteur
function generer_action_auteur($action, $arg, $redirect="", $mode=false, $att='', $public=false)
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	return $securiser_action($action, $arg, $redirect, $mode, $att, $public);
}

// http://doc.spip.org/@redirige_action_auteur
function redirige_action_auteur($action, $arg, $ret, $gra='', $mode=false, $atts='') {

	$r = _DIR_RESTREINT . generer_url_ecrire($ret, $gra, true, true);

	return generer_action_auteur($action, $arg, $r, $mode, $atts);
}

// http://doc.spip.org/@redirige_action_post
function redirige_action_post($action, $arg, $ret, $gra, $corps, $att='') {
	$r = _DIR_RESTREINT . generer_url_ecrire($ret, $gra, false, true);
	return generer_action_auteur($action, $arg, $r, $corps, $att . " method='post'");
}



//
// Attention pour que Safari puisse manipuler cet evenement
// il faut onsubmit="return AjaxSqueeze(x,'truc',...)"
// et non pas onsubmit='return AjaxSqueeze(x,"truc",...)'
//
// http://doc.spip.org/@ajax_action_declencheur
function ajax_action_declencheur($request, $noeud, $fct_ajax='') {
	if (strpos($request, 'this') !== 0) 
		$request = "'".$request."'";

	return '"return AjaxSqueeze('
	. $request
	. ",'"
	. $noeud
	. "',"
	  . ($fct_ajax ? $fct_ajax : "''")
	. ',event)"';
}

// Place un element HTML dans une div nommee,
// sauf si c'est un appel Ajax car alors la div y est deja 
// $fonction : denomination semantique du bloc, que l'on retouve en attribut class
// $id : id de l'objet concerne si il y a lieu ou "", sert a construire un identifiant unique au bloc ("fonction-id")
// http://doc.spip.org/@ajax_action_greffe
function ajax_action_greffe($fonction, $id, $corps)
{
	$idom = $fonction.(strlen($id)?"-$id":"");
	return _AJAX
		? "$corps"
		: "\n<div id='$idom' class='ajax-action $fonction'>$corps\n</div>\n";
}

// http://doc.spip.org/@ajax_retour
function ajax_retour($corps,$xml = true)
{
	$e = "";
	if (isset($_COOKIE['spip_admin'])
	AND ((_request('var_mode') == 'debug') OR !empty($GLOBALS['tableau_des_temps'])))
		$e = erreur_squelette();
	if (isset($GLOBALS['transformer_xml']) OR $GLOBALS['exec'] == 'valider_xml') {
	$debut = _DOCTYPE_ECRIRE
	. "<html><head><title>Debug Spip Ajax</title></head>"
	.  "<body><div>\n\n"
	. "<!-- %%%%%%%%%%%%%%%%%%% Ajax %%%%%%%%%%%%%%%%%%% -->\n";

	$fin = '</div></body></html>';

	} else {
		$c = $GLOBALS['meta']["charset"];
		header('Content-Type: text/html; charset='. $c);
		$debut = (($xml AND strlen(trim($corps)))?'<' . "?xml version='1.0' encoding='" . $c . "'?" . ">\n":'');
	}
	echo $debut, $corps, $fin, $e;
}

?>
