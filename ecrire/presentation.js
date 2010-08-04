var init_gauche = true;

function changestyle(id_couche, element, style) {

  if (admin) {
		hide_obj("bandeaudocuments");
		hide_obj("bandeauredacteurs");
		hide_obj("bandeauauteurs");
		if (stat) {  hide_obj("bandeausuivi"); } 
		hide_obj("bandeauadministration"); 
  }
	hide_obj("bandeaudeconnecter");
	hide_obj("bandeautoutsite");
	hide_obj("bandeaunavrapide");
	hide_obj("bandeauagenda");
	hide_obj("bandeaumessagerie");
	hide_obj("bandeausynchro");
	hide_obj("bandeaurecherche");
	hide_obj("bandeauinfoperso");
	hide_obj("bandeaudisplay");
	hide_obj("bandeauecran");
	hide_obj("bandeauinterface");
	
	
	if (init_gauche) {
	   if (admin) {
		decalerCouche('bandeaudocuments');
		decalerCouche('bandeauredacteurs');
		decalerCouche('bandeauauteurs');
		if (stat) decalerCouche('bandeausuivi');
		decalerCouche('bandeauadministration');
		} 
		init_gauche = false;
	}
	
	
	if (!(layer = findObj(id_couche))) return;

	layer.style[element] = style;
}

function decalerCouche(id_couche) {
	if (!(layer = findObj(id_couche))) return;
	if (bug_offsetwidth && ( parseInt(layer.style.left) > 0)) {
		demilargeur = Math.floor( layer.offsetWidth / 2 );
		if (demilargeur == 0) demilargeur = 100; // bug offsetwidth MSIE, on fixe une valeur arbitraire
		gauche = parseInt(layer.style.left)
		  - demilargeur
		  + Math.floor(largeur_icone / 2);
		  		  
		if (gauche < 0) gauche = 0;

		layer.style.left = gauche+"px";
	}

}	

var accepter_change_statut;
	
function selec_statut(id, type, decal, puce, statut) {

	if (!accepter_change_statut) {
	  accepter_change_statut = confirm(confirm_changer_statut)
	}

	if (accepter_change_statut) {
		changestyle ('statutdecal'+type+id, 'marginLeft', decal+'px');
		cacher ('statutdecal'+type+id);

		findObj('imgstatut'+type+id).src = puce;
		frames['iframe_action'].location.href = 
  'iframe_action.php3?action=statut_'+ type +'&id='+id+'&statut='+statut;

	}
}

function changeclass(objet, myClass)
{
		objet.className = myClass;
}
function changesurvol(iddiv, myClass)
{
		document.getElementById(iddiv).className = myClass;
}
function setActiveStyleSheet(title) {
   var i, a, main;
   for(i=0; (a = document.getElementsByTagName("link")[i]); i++) {
	 if(a.getAttribute("rel").indexOf("style") != -1
		&& a.getAttribute("title")) {
	   a.disabled = true;
	   if(a.getAttribute("title") == title) a.disabled = false;
	 }
   }
}

function setvisibility (objet, statut) {
	element = findObj(objet);
	if (element.style.visibility != statut) element.style.visibility = statut;
}

function montrer(objet) {
	setvisibility(objet, 'visible');
}
function cacher(objet) {
	setvisibility(objet, 'hidden');
}



function getHeight(obj) {
	if (obj == "window") {
		return hauteur_fenetre();
	}
	else
	{
		obj = document.getElementById(obj);
		if (obj.offsetHeight) return obj.offsetHeight;
	}
}
function hauteur_fenetre() {
	var myWidth = 0, myHeight = 0;
	if( typeof( window.innerWidth ) == 'number' ) {
		//Non-IE
		myHeight = window.innerHeight;
	} else {
		if( document.documentElement &&
			( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
			//IE 6+ in 'standards compliant mode'
			myHeight = document.documentElement.clientHeight;
		} else {
			if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
				//IE 4 compatible
				myHeight = document.body.clientHeight;
			}
		}
	}
	return myHeight;
}


function hauteurFrame(nbCol) {
	hauteur = hauteur_fenetre() - 40;
	hauteur = hauteur - getHeight('haut-page');
	
	if (findObj('brouteur_hierarchie')) hauteur = hauteur - getHeight('brouteur_hierarchie');
		
	for (i=0; i<nbCol; i++) {
		source = document.getElementById("iframe" + i);
		source.style.height = hauteur + 'px';
	}
}

function hauteurTextarea() {
	hauteur = hauteur_fenetre() - 80;
	
	source = document.getElementById("text_area");
	source.style.height = hauteur + 'px';
}

function changeVisible(input, id, select, nonselect) {
	if (input) {
		element = findObj(id);
		if (element.style.display != select)  element.style.display = select;
	} else {
		element = findObj(id);
		if (element.style.display != nonselect)  element.style.display = nonselect;
	}
	
}





// pour MOzilla >= 1.7
function verifForm() {
	/* if (pluginlist.indexOf("SVG")!=-1)
		document.cookie = "spip_svg_plugin=oui";
	else
		document.cookie = "spip_svg_plugin=non";
	*/

	//convert2math();

	retrait = 16;
	var obj=document.getElementsByTagName("input");
	for(i=0;i<obj.length;i++) {
		if(obj[i].className=="forml" || obj[i].className=="formo") {
			element = obj[i];
			if (element.offsetWidth) {
				obj[i]["nouvelle-largeur"] = (element.offsetWidth - retrait) + "px";
			} else {
				obj[i]["nouvelle-largeur"] = "95%";
			}
		}
	}
	
	var objx=document.getElementsByTagName("textarea");
	for(i=0;i<objx.length;i++) {
		if(objx[i].className=="forml" || objx[i].className=="formo") {
			element = objx[i];
			if (element.offsetWidth) {
				objx[i]["nouvelle-largeur"] = (element.offsetWidth - retrait) + "px";
			} else {
				objx[i]["nouvelle-largeur"] = "95%";
			}
		}
	}
	
	// Appliquer les modifs apres les calculs, sinon des decalages peuvent apparaitre
	for(i=0;i<obj.length;i++) {
		if (obj[i]["nouvelle-largeur"]) obj[i].style.width = obj[i]["nouvelle-largeur"];
	}

	for(i=0;i<objx.length;i++) {
		if (objx[i]["nouvelle-largeur"]) objx[i].style.width = objx[i]["nouvelle-largeur"];
	}
}


// effacement titre quand new=oui
var antifocus=false;
// effacement titre des groupes de mots-cles de plus de 50 mots
var antifocus_mots = new Array();