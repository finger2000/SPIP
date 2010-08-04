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
if (defined("_ECRIRE_INC_AUTH")) return;
define("_ECRIRE_INC_AUTH", "1");

include_ecrire ("inc_connect.php3");
include_ecrire ("inc_meta.php3");
include_ecrire ("inc_session.php3");

define("_DIR_LOGIN", _DIR_RESTREINT ? "" : "../");
define("_DIR_LOGED_IN",   _DIR_RESTREINT ? "" : _DIR_RESTREINT_ABS);


//
// Fonctions de gestion de l'acces restreint aux rubriques
//

function acces_rubrique($id_rubrique) {
	global $connect_toutes_rubriques;
	global $connect_id_rubrique;

	return ($connect_toutes_rubriques OR $connect_id_rubrique[$id_rubrique]);
}

function acces_restreint_rubrique($id_rubrique) {
	global $connect_id_rubrique;
	global $connect_statut;

	return ($connect_statut == "0minirezo" AND $connect_id_rubrique[$id_rubrique]);
}


function auth() {
	global $_POST, $_GET, $_COOKIE, $_SERVER;
	global $auth_can_disconnect, $ignore_auth_http, $ignore_remote_user;

	global $connect_id_auteur, $connect_nom, $connect_bio, $connect_email;
	global $connect_nom_site, $connect_url_site, $connect_login, $connect_pass;
	global $connect_activer_imessage, $connect_activer_messagerie;
	global $connect_statut, $connect_toutes_rubriques, $connect_id_rubrique;

	global $auteur_session, $prefs;
	global $clean_link;

	//
	// Si pas MySQL, fini
	//
	if (!$GLOBALS['db_ok']) {
		spip_log("Erreur base de donnees");
		include_ecrire('inc_presentation.php3');
		install_debut_html(_T('info_travaux_titre'));
		echo _T('titre_probleme_technique');
		echo "<p><tt>".spip_sql_errno()." ".spip_sql_error()."</tt></p>";
		install_fin_html();
		exit;
	}


	//
	// Initialiser variables (eviter hacks par URL)
	//

	$auth_login = "";
	$auth_pass = "";
	$auth_pass_ok = false;
	$auth_can_disconnect = false;
	$auth_htaccess = false;

	//
	// Recuperer les donnees d'identification
	//

	// Authentification session
	if ($cookie_session = $_COOKIE['spip_session']) {
		if (verifier_session($cookie_session)) {
			if ($auteur_session['statut'] == '0minirezo'
			OR $auteur_session['statut'] == '1comite') {
				$auth_login = $auteur_session['login'];
				$auth_pass_ok = true;
				$auth_can_disconnect = true;
			}
		}
	}

	// Peut-etre sommes-nous en auth http?
	else if ($_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']
	&& !$ignore_auth_http) {

		// Si le login existe dans la base, se loger
		if (verifier_php_auth()) {
			$auth_login = $_SERVER['PHP_AUTH_USER'];
			$auth_pass_ok = true;
			$auth_can_disconnect = true;
			$_SERVER['PHP_AUTH_PW'] = '';
		}
		// Sinon c'est un login d'intranet independant de spip, on ignore
	}

	// Authentification .htaccess old style, car .htaccess semble
	// souvent definir *aussi* PHP_AUTH_USER et PHP_AUTH_PW
	else if ($GLOBALS['_SERVER']['REMOTE_USER']
	&& !$ignore_remote_user) {
		$auth_login = $GLOBALS['_SERVER']['REMOTE_USER'];
		$auth_pass_ok = true;
		$auth_htaccess = true;
	}

	// Tentative de login echec
	if ($_GET['bonjour'] == 'oui' AND !$auth_login) {
		$link = new Link(_DIR_LOGIN . "spip_cookie.php3?test_echec_cookie=oui");
		$clean_link->delVar('bonjour');
		$url = str_replace('/./', '/', _DIR_LOGED_IN .$clean_link->getUrl());
		$link->addVar('url', $url);
		redirige_par_entete($link->getUrl());
		exit;
	}

	// Si pas authentifie, demander login / mdp
	if (!$auth_login) {
		$url = str_replace('/./', '/',  _DIR_LOGED_IN
			. $clean_link->getUrl());
		redirige_par_entete(_DIR_LOGIN . "spip_login.php3?url=".urlencode($url));
		exit;
	}


	//
	// Chercher le login dans la table auteurs
	//

	$auth_login = addslashes($auth_login);
	$query = "SELECT * FROM spip_auteurs WHERE login='$auth_login' AND statut!='5poubelle' AND statut!='6forum'";
	$result = @spip_query($query);

	if ($row = spip_fetch_array($result)) {
		$connect_id_auteur = $row['id_auteur'];
		$connect_nom = $row['nom'];
		$connect_bio = $row['bio'];
		$connect_email = $row['email'];
		$connect_nom_site = $row['nom_site'];
		$connect_url_site = $row['url_site'];
		$connect_login = $row['login'];
		$connect_pass = $row['pass'];
		$connect_statut = $row['statut'];
		$connect_activer_messagerie = "oui"; //$row["messagerie"];
		$connect_activer_imessage = "oui "; //$row["imessage"];

		// Special : si dans la fiche auteur on modifie les valeurs
		// de messagerie, utiliser ces valeurs plutot que celle de la base.
		// D'ou leger bug si on modifie la fiche de quelqu'un d'autre.

		// regler les preferences de l'auteur
		$prefs = unserialize($row['prefs']);

		// vieux ! on pourra supprimer post 1.6 finale...
		if (! isset($prefs['display'])) { // recuperer les cookies ou creer defaut
			if (!$GLOBALS['set_disp'] = $GLOBALS['_COOKIE']['spip_display'])
				$GLOBALS['set_disp'] = 2;
			if (!$GLOBALS['set_couleur'] = $GLOBALS['_COOKIE']['spip_couleur'])
				$GLOBALS['set_couleur'] = 6;
			if (!$GLOBALS['set_options'] = $GLOBALS['_COOKIE']['spip_options'])
				$GLOBALS['set_options'] = 'basiques';
		}

		// Indiquer connexion
		if ($connect_activer_messagerie != "non") {
			@spip_query("UPDATE spip_auteurs SET en_ligne=NOW() WHERE id_auteur='$connect_id_auteur'");
		}

		// Si administrateur, recuperer les rubriques gerees par l'admin
		if ($connect_statut == '0minirezo') {
			$query_admin = "SELECT id_rubrique FROM spip_auteurs_rubriques WHERE id_auteur=$connect_id_auteur AND id_rubrique!='0'";
			$result_admin = spip_query($query_admin);

			$connect_toutes_rubriques = (@spip_num_rows($result_admin) == 0);
			if ($connect_toutes_rubriques) {
				$connect_id_rubrique = array();
			}
			else {
				for (;;) {
					$r = '';
					while ($row_admin = spip_fetch_array($result_admin)) {
						$id_rubrique = $row_admin['id_rubrique'];
						$r[] = $id_rubrique;
						$connect_id_rubrique[$id_rubrique] = $id_rubrique;
					}
					if (!$r) break;
					$r = join(',', $r);
					$query_admin = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent IN ($r) AND id_rubrique NOT IN ($r)";
				 	$result_admin = spip_query($query_admin);
				 }
			}
		}
		// Si pas admin, acces egal a toutes rubriques
		else {
			$connect_toutes_rubriques = false;
			$connect_id_rubrique = array();
		}
	}
	else {
		// ici on est dans un cas limite : l'auteur a ete identifie OK
		// mais il n'existe pas dans la table auteur. Cause possible,
		// notamment, une restauration de base de donnees dans laquelle
		// il n'existe pas.
		include_ecrire('inc_presentation.php3');
		include_ecrire('inc_texte.php3');
		install_debut_html(_T('avis_erreur_connexion'));
		echo "<br><br><p>".
		  _T('texte_inc_auth_1', array('auth_login' => $auth_login)).
		  " <a href='" .
		  _DIR_LOGIN . "spip_cookie.php3?logout=$auth_login'>".
                _T('texte_inc_auth_2')."</A>"._T('texte_inc_auth_3');
		install_fin_html();
		exit;
	}

	if (!$auth_pass_ok) {
		redirige_par_entete(_DIR_LOGIN . "spip_login.php3?var_erreur=pass");
	}

	// Si c'est un nouvel inscrit, le passer de 'nouveau' a '1comite'
	// (code presque mort, utilise peut-etre encore sous .htpasswd ?)
	if ($connect_statut == 'nouveau') {
		$connect_statut =
		(lire_meta('accepter_inscriptions') == 'oui') ? '1comite' : '6forum';
		spip_query("UPDATE spip_auteurs SET statut='$connect_statut'
			WHERE id_auteur=$connect_id_auteur");
	}

	return true;
}


if (!auth()) exit;

?>