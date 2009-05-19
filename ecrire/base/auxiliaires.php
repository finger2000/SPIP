<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@base_auxiliaires
function base_auxiliaires(&$tables_auxiliaires){
$spip_petitions = array(
		"id_article"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"email_unique"	=> "CHAR (3) DEFAULT '' NOT NULL",
		"site_obli"	=> "CHAR (3) DEFAULT '' NOT NULL",
		"site_unique"	=> "CHAR (3) DEFAULT '' NOT NULL",
		"message"	=> "CHAR (3) DEFAULT '' NOT NULL",
		"texte"	=> "LONGTEXT DEFAULT '' NOT NULL",
		"maj"	=> "TIMESTAMP");

$spip_petitions_key = array(
		"PRIMARY KEY"	=> "id_article");


$spip_resultats = array(
 		"recherche"	=> "char(16) not null default ''",
		"id"	=> "INT UNSIGNED NOT NULL",
 		"points"	=> "INT UNSIGNED DEFAULT '0' NOT NULL",
		"maj"	=> "TIMESTAMP" );

$spip_resultats_key = array(
// pas de cle ni index, ca fait des insertions plus rapides et les requetes jointes utilisees en recheche ne sont pas plus lentes ...
);


$spip_auteurs_articles = array(
		"id_auteur"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_article"	=> "bigint(21) DEFAULT '0' NOT NULL");

$spip_auteurs_articles_key = array(
		"PRIMARY KEY"	=> "id_auteur, id_article",
		"KEY id_article"	=> "id_article");

$spip_auteurs_rubriques = array(
		"id_auteur"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_rubrique"	=> "bigint(21) DEFAULT '0' NOT NULL");

$spip_auteurs_rubriques_key = array(
		"PRIMARY KEY"	=> "id_auteur, id_rubrique",
		"KEY id_rubrique"	=> "id_rubrique");

$spip_auteurs_messages = array(
		"id_auteur"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_message"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"vu"		=> "CHAR (3)");

$spip_auteurs_messages_key = array(
		"PRIMARY KEY"	=> "id_auteur, id_message",
		"KEY id_message"	=> "id_message");

$spip_documents_liens = array(
		"id_document"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_objet"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"objet"	=> "VARCHAR (25) DEFAULT '' NOT NULL",
		"vu"	=> "ENUM('non', 'oui') DEFAULT 'non' NOT NULL");

$spip_documents_liens_key = array(
		"PRIMARY KEY"		=> "id_document,id_objet,objet",
		"KEY id_document"	=> "id_document");


$spip_mots_articles = array(
		"id_mot"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_article"	=> "bigint(21) DEFAULT '0' NOT NULL");

$spip_mots_articles_key = array(
		"PRIMARY KEY"	=> "id_article, id_mot",
		"KEY id_mot"	=> "id_mot");

$spip_mots_breves = array(
		"id_mot"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_breve"	=> "bigint(21) DEFAULT '0' NOT NULL");

$spip_mots_breves_key = array(
		"PRIMARY KEY"	=> "id_breve, id_mot",
		"KEY id_mot"	=> "id_mot");

$spip_mots_rubriques = array(
		"id_mot"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_rubrique"	=> "bigint(21) DEFAULT '0' NOT NULL");

$spip_mots_rubriques_key = array(
		"PRIMARY KEY"	=> "id_rubrique, id_mot",
		"KEY id_mot"	=> "id_mot");

$spip_mots_syndic = array(
		"id_mot"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_syndic"	=> "bigint(21) DEFAULT '0' NOT NULL");

$spip_mots_syndic_key = array(
		"PRIMARY KEY"	=> "id_syndic, id_mot",
		"KEY id_mot"	=> "id_mot");

$spip_mots_documents = array(
		"id_mot"	=> "bigint(21) DEFAULT '0' NOT NULL",
		"id_document"	=> "bigint(21) DEFAULT '0' NOT NULL");

$spip_mots_documents_key = array(
		"PRIMARY KEY"	=> "id_document, id_mot",
		"KEY id_mot"	=> "id_mot");

$spip_meta = array(
		"nom"	=> "VARCHAR (255) NOT NULL",
		"valeur"	=> "text DEFAULT ''",
		"impt"	=> "ENUM('non', 'oui') DEFAULT 'oui' NOT NULL",
		"maj"	=> "TIMESTAMP");

$spip_meta_key = array(
		"PRIMARY KEY"	=> "nom");

$spip_versions = array (
		"id_article"	=> "bigint(21) NOT NULL",
		"id_version"	=> "bigint(21) DEFAULT 0 NOT NULL",
		"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		"id_auteur"	=> "VARCHAR(23) DEFAULT '' NOT NULL", # stocke aussi IP(v6)
		"titre_version"	=> "text DEFAULT '' NOT NULL",
		"permanent"	=> "char(3)",
		"champs"	=> "text");

$spip_versions_key = array (
		"PRIMARY KEY"	=> "id_article, id_version");

$spip_versions_fragments = array(
		"id_fragment"	=> "int unsigned DEFAULT '0' NOT NULL",
		"version_min"	=> "int unsigned DEFAULT '0' NOT NULL",
		"version_max"	=> "int unsigned DEFAULT '0' NOT NULL",
		"id_article"	=> "bigint(21) NOT NULL",
		"compress"	=> "tinyint NOT NULL",
		"fragment"	=> "longblob"  # ici c'est VRAIMENT un blob (on y stocke du gzip)
	);

$spip_versions_fragments_key = array(
	     "PRIMARY KEY"	=> "id_article, id_fragment, version_min");


$tables_auxiliaires['spip_petitions'] = array(
	'field' => &$spip_petitions,
	'key' => &$spip_petitions_key
);

$tables_auxiliaires['spip_auteurs_articles'] = array(
	'field' => &$spip_auteurs_articles,
	'key' => &$spip_auteurs_articles_key);
$tables_auxiliaires['spip_auteurs_rubriques'] = array(
	'field' => &$spip_auteurs_rubriques,
	'key' => &$spip_auteurs_rubriques_key);
$tables_auxiliaires['spip_auteurs_messages'] = array(
	'field' => &$spip_auteurs_messages,
	'key' => &$spip_auteurs_messages_key);
$tables_auxiliaires['spip_documents_liens'] = array(
	'field' => &$spip_documents_liens,
	'key' => &$spip_documents_liens_key);

$tables_auxiliaires['spip_mots_articles'] = array(
	'field' => &$spip_mots_articles,
	'key' => &$spip_mots_articles_key);
$tables_auxiliaires['spip_mots_breves'] = array(
	'field' => &$spip_mots_breves,
	'key' => &$spip_mots_breves_key);
$tables_auxiliaires['spip_mots_rubriques'] = array(
	'field' => &$spip_mots_rubriques,
	'key' => &$spip_mots_rubriques_key);
$tables_auxiliaires['spip_mots_syndic'] = array(
	'field' => &$spip_mots_syndic,
	'key' => &$spip_mots_syndic_key);
$tables_auxiliaires['spip_mots_documents'] = array(
	'field' => &$spip_mots_documents,
	'key' => &$spip_mots_documents_key);
$tables_auxiliaires['spip_meta'] = array(
	'field' => &$spip_meta,
	'key' => &$spip_meta_key);
$tables_auxiliaires['spip_resultats'] = array(
	'field' => &$spip_resultats,
	'key' => &$spip_resultats_key);
$tables_auxiliaires['spip_versions'] = array(
	'field' => &$spip_versions,
	'key' => &$spip_versions_key);
$tables_auxiliaires['spip_versions_fragments'] = array(
	'field' => &$spip_versions_fragments,
	'key' => &$spip_versions_fragments_key);
	
	$tables_auxiliaires = pipeline('declarer_tables_auxiliaires',$tables_auxiliaires);
}

global $tables_auxiliaires;
base_auxiliaires($tables_auxiliaires);
?>
