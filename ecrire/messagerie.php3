<?php

include ("inc.php3");
include_ecrire ("inc_acces.php3");
include_ecrire ("inc_agenda.php3");

if ($supp_dest) {
	spip_query("DELETE FROM spip_auteurs_messages WHERE id_message=$id_message AND id_auteur=$supp_dest");
}

if ($detruire_message) {
	spip_query("DELETE FROM spip_messages WHERE id_message=$detruire_message");
	spip_query("DELETE FROM spip_auteurs_messages WHERE id_message=$detruire_message");
	spip_query("DELETE FROM spip_forum WHERE id_message=$detruire_message");
}


debut_page(_T('titre_page_messagerie'), "redacteurs", "messagerie");

//barre_onglets("calendrier", "messagerie");


debut_gauche("messagerie");


debut_boite_info();

echo _T('info_gauche_messagerie');

echo "<p>"."<IMG SRC='img_pack/m_envoi$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T('info_symbole_vert');

echo aide ("messut");

echo "<p>"."<IMG SRC='img_pack/m_envoi_bleu$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T('info_symbole_bleu');

echo aide ("messpense");

echo "<p>"."<IMG SRC='img_pack/m_envoi_jaune$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T('info_symbole_jaune');



fin_boite_info();


creer_colonne_droite();

debut_cadre_relief("messagerie-24.gif");
	echo "<a href='message_edit.php3?new=oui&type=normal'><img src='img_pack/m_envoi$spip_lang_rtl.gif' alt='' width='14' height='7' border='0'>";
	echo "<font color='#169249' face='Verdana,Arial,Sans,sans-serif' size=1><b>&nbsp;"._T('lien_nouveau_message')."</b></font></a>\n";
	echo "<br><a href='message_edit.php3?new=oui&type=pb'><img src='img_pack/m_envoi_bleu$spip_lang_rtl.gif' alt='' width='14' height='7' border='0'>";
	echo "<font color='#044476' face='Verdana,Arial,Sans,sans-serif' size=1><b>&nbsp;"._T('lien_nouvea_pense_bete')."</b></font></a>\n";
	if ($connect_statut == "0minirezo") {
		echo "<br><a href='message_edit.php3?new=oui&type=affich'><img src='img_pack/m_envoi_jaune$spip_lang_rtl.gif' alt='' width='14' height='7' border='0'>";
		echo "<font color='#ff9900' face='Verdana,Arial,Sans,sans-serif' size=1><b>&nbsp;"._T('lien_nouvelle_annonce')."</b></font></a>\n";
	}
fin_cadre_relief();


afficher_taches();


afficher_ical($connect_id_auteur);


debut_droite("messagerie");


function afficher_messages($titre_table, $query_message, $afficher_auteurs = true, $important = false, $boite_importante = true, $obligatoire = false) {
	global $messages_vus;
	global $connect_id_auteur;
	global $couleur_claire;
	global $spip_lang_rtl;

	// Interdire l'affichage de message en double
	if ($messages_vus) {
		$query_message .= ' AND messages.id_message NOT IN ('.join(',', $messages_vus).')';
	}


	if ($afficher_auteurs) $cols = 3;
	else $cols = 2;
	$query_message .= ' ORDER BY date_heure DESC';
	$tranches = afficher_tranches_requete($query_message, $cols);

	if ($tranches OR $obligatoire) {
		if ($important) debut_cadre_relief();

		echo "<div>&nbsp;</div>";
		echo "<TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0><TR><TD WIDTH=100% BACKGROUND=''>";
		echo "<TABLE WIDTH=100% CELLPADDING=3 CELLSPACING=0 BORDER=0>";

		bandeau_titre_boite($titre_table, $afficher_auteurs, $boite_importante);

		echo $tranches;

		$result_message = spip_query($query_message);
		$num_rows = spip_num_rows($result_message);

		while($row = spip_fetch_array($result_message)) {
			$vals = '';

			$id_message = $row['id_message'];
			$date = $row["date_heure"];
			$titre = $row["titre"];
			$type = $row["type"];
			$statut = $row["statut"];
			$page = $row["page"];
			$rv = $row["rv"];
			$vu = $row["vu"];
			$messages_vus[$id_message] = $id_message;

			//
			// Titre
			//

			$s = "<A HREF='message.php3?id_message=$id_message'>";

			switch ($type) {
			case 'pb' :
				$puce = "m_envoi_bleu$spip_lang_rtl.gif";
				break;
			case 'memo' :
				$puce = "m_envoi_jaune$spip_lang_rtl.gif";
				break;
			case 'affich' :
				$puce = "m_envoi_jaune$spip_lang_rtl.gif";
				break;
			case 'normal':
			default:
				$puce = "m_envoi$spip_lang_rtl.gif";
				break;
			}
				
			$s .= "<img src='img_pack/$puce' width='14' height='7' border='0'>";
			$s .= "&nbsp;&nbsp;".typo($titre)."</A>";
			$vals[] = $s;

			//
			// Auteurs

			if ($afficher_auteurs) {
				$query_auteurs = "SELECT auteurs.nom FROM spip_auteurs AS auteurs, spip_auteurs_messages AS lien WHERE lien.id_message=$id_message AND lien.id_auteur!=$connect_id_auteur AND lien.id_auteur=auteurs.id_auteur";
				$result_auteurs = spip_query($query_auteurs);
				$auteurs = '';
				while ($row_auteurs = spip_fetch_array($result_auteurs)) {
					$auteurs[] = typo($row_auteurs['nom']);
				}

				if ($auteurs AND $type == 'normal') {
					$s = "<FONT FACE='Arial,Sans,sans-serif' SIZE=1>";
					$s .= join(', ', $auteurs);
					$s .= "</FONT>";
				}
				else $s = "&nbsp;";
				$vals[] = $s;
			}
			
			//
			// Date
			//
			
			$s = affdate($date);
			if ($rv == 'oui') {
				$jour=journum($date);
				$mois=mois($date);
				$annee=annee($date);

				$s = "<a href='calendrier_jour.php3?jour=$jour&mois=$mois&annee=$annee'>$s</a>";
			} else {
				$s = "<font color='#999999'>$s</font>";
			}
			
			$vals[] = $s;

			$table[] = $vals;
		}

		if ($afficher_auteurs) {
			$largeurs = array('', 130, 90);
			$styles = array('arial2', 'arial1', 'arial1');
		}
		else {
			$largeurs = array('', 90);
			$styles = array('arial2', 'arial1');
		}
		afficher_liste($largeurs, $table, $styles);

		echo "</TABLE></TD></TR></TABLE>";
		spip_free_result($result_message);
		if ($important) fin_cadre_relief();
	}
}




$messages_vus = '';


$query_message = "SELECT * FROM spip_messages AS messages WHERE id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND rv!='oui' AND (date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY) OR rv != 'oui')";
afficher_messages(_T('infos_vos_pense_bete'), $query_message, false, true);


$query_message = "SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien ".
	"WHERE lien.id_auteur=$connect_id_auteur AND vu='non' ".
	"AND statut='publie' AND lien.id_message=messages.id_message";
afficher_messages(_T('info_nouveaux_message'), $query_message, true, true);


$query_message = "SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien ".
	"WHERE lien.id_auteur=$connect_id_auteur AND statut='publie' AND type='normal' AND rv!='oui' AND lien.id_message=messages.id_message";
afficher_messages(_T('info_discussion_cours'), $query_message, true, false);


$query_message = "SELECT * FROM spip_messages AS messages WHERE id_auteur=$connect_id_auteur AND statut='redac'";
afficher_messages(_T('info_message_en_redaction'), $query_message, true, false, false);



$query = "SELECT auteurs.id_auteur, auteurs.nom, COUNT(*) AS total FROM spip_auteurs AS auteurs,  spip_auteurs_messages AS lien2, spip_messages AS messages, spip_auteurs_messages AS lien ".
	"WHERE (lien.id_auteur = $connect_id_auteur AND lien.id_message = messages.id_message) ".
	"AND (lien2.id_auteur = lien2.id_auteur AND lien2.id_message = messages.id_message AND lien2.id_auteur != $connect_id_auteur AND auteurs.id_auteur = lien2.id_auteur) GROUP BY auteurs.id_auteur ORDER BY total DESC LIMIT 0,20";

$result = spip_query($query);
if (spip_num_rows($result) > 0) {
	echo "<div>&nbsp;</div>";
	echo "<div style='padding: 3px; background-color: $couleur_foncee; color: white;'><b class='verdana2'>"._T('info_principaux_correspondants')."</b></div>";
	echo "<table width='100%' cellpadding='0' cellspacing='0'>";
	echo "<tr><td valign='top' width='50%'>";
	while($row = spip_fetch_array($result)) {
		$count ++;
		if ($i == 1) {
			$bgcolor = "white";
			$i = 0;
		} else {
			$bgcolor = $couleur_claire;
			$i = 1;
		}
		$id_auteur = $row['id_auteur'];
		$nom = typo($row["nom"]);
		$total = $row["total"];
		echo "<div class='arial1' style=' padding: 2px; padding-left: 10px; background-color: $bgcolor;'>".bouton_imessage($id_auteur, $row)." $nom ($total)</div>";
		if ($count == 10) echo "</td><td valign='top' width='50%'>";
	}
	echo "</td></tr></table>";
}




$query_message = "SELECT * FROM spip_messages AS messages WHERE id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND rv!='oui'";
afficher_messages(_T('info_pense_bete_ancien'), $query_message, false, false, false);


$query_message = "SELECT * FROM spip_messages AS messages WHERE statut='publie' AND type='affich' AND rv = 'oui'";
afficher_messages(_T('info_tous_redacteurs'), $query_message, false, false, false);

fin_page();

?>
