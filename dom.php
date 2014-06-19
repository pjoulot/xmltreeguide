<?php

/*
*******************************************
	Copyright Philippe Joulot
*******************************************
*/

$doc = new DOMDocument();
$doc->load("test.xml");
$racine = $doc->documentElement;

$treeguide = new DOMDocument();
$noeudTreeGuide = $treeguide->createElement( $racine->nodeName );
$noeudTreeGuide = $treeguide->appendChild( $noeudTreeGuide );

//On créer la structure des noeuds existants
parcourir($racine, $doc, $treeguide, $noeudTreeGuide, 0, $racine->nodeName);
//On récupère le nombre maximum et minimum des noeuds par niveau
parcourirOccurrence($racine, $treeguide, 0, $racine->nodeName);
//On affiche notre structure du treeguide sous la forme demandée
parcourirAffichage($treeguide, $noeudTreeGuide);
//On sauvegarde le TreeGuide
$treeguide->save('treeGuide.xml');


function afficherInfos($noeud, $occurrence){
  $nom = $noeud->nodeName;
  echo $nom . $occurrence . "<br/>";
}

function parcourir($noeud, $doc, $treeguide, $noeudTreeGuide, $p, $chemin){
  if($noeud->nodeName != "#text" && $noeud->nodeType == XML_ELEMENT_NODE ){
    $p++;

	$enfants = $noeud->childNodes;
	//Cet array sert à retenir le noeud créé dans le tree guide pour parcourir les autres branches
	$arrayNoeudDejaCree = array();
	
	foreach($enfants as $enfant){
	  $cheminNew = $chemin.">".$enfant->nodeName;
	  if(!siNoeudExiste($cheminNew, $treeguide) && $enfant->hasChildNodes()) {
			$newNoeudTreeGuide = $treeguide->createElement( $enfant->nodeName );
			$newNoeudTreeGuide = $noeudTreeGuide->appendChild( $newNoeudTreeGuide );
			//Les noeuds min et max seront utilisés dans un second parcours du document, on les créé juste
			$nbrElementsMax = $doc->getElementsByTagName($enfant->nodeName);
			$cptElement=0;
			foreach ($nbrElementsMax as $nbrEelement) {
				$cptElement++;
			}
			$noeudMin = $treeguide->createElement( "min" , $cptElement );
			$noeudMin = $newNoeudTreeGuide->appendChild( $noeudMin );
			$noeudMax = $treeguide->createElement( "max" , 0 );
			$noeudMax = $newNoeudTreeGuide->appendChild( $noeudMax );
			//On enregsitre la profondeur pour éviter un parcours supplémentaire lors du second parcours
			$noeudProfondeur = $treeguide->createElement( "profondeur" , $p );
			$noeudProfondeur = $newNoeudTreeGuide->appendChild( $noeudProfondeur );
			array_push($arrayNoeudDejaCree, array($enfant->nodeName, $newNoeudTreeGuide));
		    parcourir($enfant, $doc, $treeguide, $newNoeudTreeGuide, $p, $cheminNew);
	  }
	  else {
		//relancer parcourir avec le nouveau noeud créé dans le if et retenu dans $arrayNoeudDejaCree
		$newNoeudTreeGuide = null;
		$array = null;
		foreach ($arrayNoeudDejaCree as $key => $value) {
			if( $value[0] == $enfant->nodeName ) {
				$array = $value;
				$newNoeudTreeGuide = $value[1];
			}
		}
		//$noeudOccurrence->firstChild->nodeValue = $noeudOccurrence->firstChild->nodeValue;
		 parcourir($enfant, $doc, $treeguide, $newNoeudTreeGuide, $p, $cheminNew);
	  }
    }
  }
}

function parcourirOccurrence($noeud, $treeguide, $p, $chemin){
  if($noeud->nodeName != "#text" && $noeud->nodeType == XML_ELEMENT_NODE ){
    $p++;

	$enfants = $noeud->childNodes;
	//Cet array sert à retenir le noeuds parcourus à ce niveau dans le document pour ensuite mettre à jour le min à 0 si le noeud n'existe pas à ce niveau
	$arrayNoeudDejaParcouru = array();
	$passe = 0;
	
	foreach($enfants as $enfant){
	  $cheminNew = $chemin.">".$enfant->nodeName;
	  if($enfant->hasChildNodes()) {
			//On récupère le noeud correspondant dans le treeguide
			$noeudsTreeGuide = $treeguide->getElementsByTagName($enfant->nodeName);
			$noeudTreeGuide = null;
			$passe = 1;
			array_push($arrayNoeudDejaParcouru, $enfant->nodeName);
			foreach ($noeudsTreeGuide as $noeud2) {
				if(getProfondeur($noeud2) == $p) {
					$noeudTreeGuide = $noeud2;
				}
			}
			//On compte son nombre de frères
			$nbrFreres = compterFreres($enfant);
			//On récupère les minimum et maximum enregistrés dans le tree guide
			$min = getMin($noeudTreeGuide);
			$max = getMax($noeudTreeGuide);
			if($min != null && $min > $nbrFreres) {
				//On met à jour le min
				setMin($noeudTreeGuide, $nbrFreres);
			
			}
			if($max != null && $max < $nbrFreres) {
				//On met à jour le max
				setMax($noeudTreeGuide, $nbrFreres);
			
			}
		    parcourirOccurrence($enfant, $treeguide, $p, $cheminNew);
	  }
    }
	//$noeudTreeGuide contiendra un noeud fils à la fin de la boucle, on peut récupérer le parent pour obtenir le noeud correspondant dans le tree guide au noeud courant
	if($passe == 1) {
		$noeudCourantTreeGuide = $noeudTreeGuide->parentNode;
		$enfants = $noeudCourantTreeGuide->childNodes;
		foreach($enfants as $enfant){
			//Si l'enfant n'est pas dans les noeuds parcourus, on met le min à 0 car cela veut dire que le noeud n'existait pas
			if(!in_array($enfant->nodeName, $arrayNoeudDejaParcouru)) {
				setMin($enfant, 0);
			}
		}
	}
  }
}

function parcourirAffichage($doc, $noeud, $p = 0){
	  if($noeud->nodeName != "min" && $noeud->nodeName != "max" && $noeud->nodeName != "profondeur" && $noeud->nodeName != "#text" && $noeud->nodeType == XML_ELEMENT_NODE){
		$enfants = $noeud->childNodes;
		
		$max = getMax($noeud);
		$min = getMin($noeud);
		
		$occurrence = "";
		//Si ce n'est pas la racine
		if($p != 0) {
			if(! ($max == 1 && $min == 1)) {
				if($min == 0) {
					if($max == 1) {
						$occurrence = " ?";
					}
					else {
						$occurrence = " *";
					}
				}
				else {
					$occurrence = " +";
				}
			}
		}
		
		indenter($p);
		$p++;
		afficherInfos($noeud, $occurrence);
		
		foreach($enfants as $enfant){
		  parcourirAffichage($doc, $enfant, $p);
		}
	  }
}

/* Le chemin est sous la forme NOEUD1>NOEUD2>...>NOEUDn */
function siNoeudExiste($cheminNoeud, $noeudDepart) {
	$chemin = explode(">", $cheminNoeud);
	
	if($cheminNoeud == "") {
		return true;
	}
	else {
		//Si c'est la racine
		if($chemin[0] == $noeudDepart->nodeName) {
			//On dit que le noeud existe, et on reste sur la racine comme noeud de départ
			$trouve = true;
			$newNoeudDepart = $noeudDepart;
			
			//On met à jour le chemin en supprimant la racine
			$newChemin = "";
			$i = 0;
			foreach ($chemin as $value) {
				// On ne récupère pas le premier élément
				if($i == 1) {
					$newChemin .= $value;
				}
				elseif($i != 0) {
					$newChemin .= ">".$value;
				}
				$i++;
			}
		}
		//Sinon on est dans un autre noeud
		else {
			//On parcourt les noeuds fils
			$enfants = $noeudDepart->childNodes;
			$trouve = false;
			foreach($enfants as $enfant){
				//Si le noeud cherché existe!
				if($chemin[0] == $enfant->nodeName) {
					$trouve = true;
					//On descend sur le noeud pour le prochain appel
					$newNoeudDepart = $enfant;
					//On met à jour le chemin en supprimant le noeud trouvé
					$newChemin = "";
					$i = 0;
					foreach ($chemin as $value) {
						// On ne récupère pas le premier élément
						if($i == 1) {
							$newChemin .= $value;
						}
						elseif($i != 0) {
							$newChemin .= ">".$value;
						}
						$i++;
					}
				}
			}
		}
		
		if($trouve) {
			$retour = siNoeudExiste($newChemin, $newNoeudDepart);
		}
		else {
			$retour = false;
		}
		return $retour;
	}

}

function compterFreres($noeud){
	$cpt = 0;
	$parent = $noeud->parentNode;
	if( $parent != NULL) {
		$enfants = $parent->childNodes;
		foreach($enfants as $enfant){
			if($enfant->nodeName == $noeud->nodeName ) {
				$cpt++;
			}
		}
	}
	return $cpt;
}

function getMin($noeud){
	$min = null;
	if( $noeud->hasChildNodes()) {
		$enfants = $noeud->childNodes;
		foreach($enfants as $enfant){
			if($enfant->nodeName == "min" ) {
				$min = $enfant->nodeValue;
			}
		}
	}
	return $min;
}

function getMax($noeud){
	$max = null;
	if( $noeud->hasChildNodes()) {
		$enfants = $noeud->childNodes;
		foreach($enfants as $enfant){
			if($enfant->nodeName == "max" ) {
				$max = $enfant->nodeValue;
			}
		}
	}
	return $max;
}

function setMin($noeud, $value){
	if( $noeud->hasChildNodes()) {
		$enfants = $noeud->childNodes;
		foreach($enfants as $enfant){
			if($enfant->nodeName == "min" ) {
				$enfant->nodeValue = $value;
			}
		}
	}
}

function setMax($noeud, $value){
	if( $noeud->hasChildNodes()) {
		$enfants = $noeud->childNodes;
		foreach($enfants as $enfant){
			if($enfant->nodeName == "max" ) {
				$enfant->nodeValue = $value;
			}
		}
	}
}

function getProfondeur($noeud){
	$prof = null;
	if( $noeud->hasChildNodes()) {
		$enfants = $noeud->childNodes;
		foreach($enfants as $enfant){
			if($enfant->nodeName == "profondeur" ) {
				$prof = $enfant->nodeValue;
			}
		}
	}
	return $prof;
}

function indenter($n){
  $tab = '&nbsp;&nbsp;&nbsp;&nbsp;';
  for($i = 0; $i < $n; $i++){
    echo $tab;
  }
}
?>
