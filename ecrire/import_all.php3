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


include ("inc_version.php3");

include_ecrire ("inc_auth.php3");
include_ecrire ("inc_import.php3");
include_ecrire ("inc_admin.php3");
include_ecrire ("inc_meta.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_filtres.php3");


//
// Preferences de presentation
//

include_ecrire ("inc_lang.php3");
utiliser_langue_visiteur();

/* pourquoi rajouter une session ? Je remplace par les 2 lignes ci-dessus 

if ($spip_lang_ecrire = $GLOBALS['_COOKIE']['spip_lang_ecrire']
AND $spip_lang_ecrire <> $auteur_session['lang']
AND changer_langue($spip_lang_ecrire)) {
	spip_query ("UPDATE spip_auteurs SET lang = '".addslashes($spip_lang_ecrire)
	."' WHERE id_auteur = $connect_id_auteur");
	$auteur_session['lang'] = $spip_lang_ecrire;
	ajouter_session($auteur_session, $spip_session);
}
*/

function verifier_version_sauvegarde ($archive) {
	global $spip_version;
	global $flag_gz;

	$ok = @file_exists(_DIR_SESSIONS . $archive);
	$gz = $flag_gz;
	$_fopen = ($gz) ? gzopen : fopen;
	$_fread = ($gz) ? gzread : fread;
	$buf_len = 1024; // la version doit etre dans le premier ko

	if ($ok) {
		$f = $_fopen(_DIR_SESSIONS . $archive, "rb");
		$buf = $_fread($f, $buf_len);

		if (ereg("<SPIP [^>]* version_base=\"([0-9\.]+)\" ", $buf, $regs)
			AND $regs[1] == $spip_version)
			return false; // c'est bon
		else
			return _T('avis_erreur_version_archive', array('archive' => $archive));
	} else
		return _T('avis_probleme_archive', array('archive' => $archive));
}

if ($archive) {
	$action = _T('info_restauration_sauvegarde', array('archive' => $archive));
	$commentaire = verifier_version_sauvegarde ($archive);
}

debut_admin($action, $commentaire);


$archive = _DIR_SESSIONS . $archive;

ecrire_meta("debut_restauration", "debut");
ecrire_meta("fichier_restauration", $archive);
ecrire_meta("status_restauration", "0");
ecrire_metas();

fin_admin($action);

redirige_par_entete("index.php3");
?>