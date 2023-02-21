<?php

$statOpen = false;
$stopLoop = false;
// Transformer la function en selection de pkmn ?
function startFight(&$pkmnTeamJoueur, &$pnj){
    displaySkeletonHUD();
    $pkmnTeamEnemy = &$pnj['Team'];

    // animation entrer dresseurs
    include 'visuals/sprites.php';
    // displaySprite($sprites['trainerBack'], getPosSpritePkmn(true));
    // displaySprite($sprites[$pnj['Sprite']], getPosSpritePkmn(false));
    
    // messageBoiteDialogue($pnj['Dialogues']['entrance']); // message trainer 
    // sleep(1);
    // messageBoiteDialogue($pnj['Nom'].' wants to fight!'); // message trainer 
    // sleep(1);

    // // animation pokeball
    // pkmnAppearinBattle(true, $pkmnTeamJoueur[0]);// faire apparaitre pkmn j
    // sleep(1);
    // pkmnAppearinBattle(false, $pkmnTeamEnemy[0]);// faire apparaitre pkmn E
    // sleep(1);
    gameplayLoop($pkmnTeamJoueur, $pnj);
}

// Start the game loop
function gameplayLoop(&$pkmnTeamJoueur, &$pnj){
    $pkmnTeamEnemy = &$pnj['Team'];
    // while dun combat tant que les equipes sont pleines
    while(isTeamPkmnAlive($pkmnTeamJoueur) && isTeamPkmnAlive($pkmnTeamEnemy)){
        // selectionne un pkmn si currentPkmn = vide (enemy ou joueur)
        if(isPkmnDead_simple($pkmnTeamEnemy[0])){
            choosePkmn($pkmnTeamEnemy);
            pkmnAppearinBattle(false, $pkmnTeamEnemy[0]);// faire apparaitre pkmn j
        }
        if(isPkmnDead_simple($pkmnTeamJoueur[0])){
            $choicesPkmnTeam = displayPkmnTeam($pkmnTeamJoueur);
            $choice2 = selectPkmn($choicesPkmnTeam, $pkmnTeamJoueur, $pkmnTeamEnemy[0]);
            
            switchPkmn($pkmnTeamJoueur ,$choice2);
            displayGameHUD($pkmnTeamJoueur, $pkmnTeamEnemy);
            interfaceMenu();
            pkmnAppearinBattle(true, $pkmnTeamJoueur[0]);// faire apparaitre pkmn j
        }
    
        // lance le combat quand les pkmns sont en combat
        loopFight($pkmnTeamJoueur, $pkmnTeamEnemy);
    }

    // fct after battle
    endBattle($pkmnTeamJoueur, $pnj);
}

// FIGHT SYSTEM
function loopFight(&$pkmnTeamJoueur, &$pkmnTeamEnemy){
    while($pkmnTeamJoueur[0]['Stats']['Health'] > 0 && $pkmnTeamEnemy[0]['Stats']['Health'] > 0 ){

        displayGameHUD($pkmnTeamJoueur, $pkmnTeamEnemy);
        interfaceMenu();

        // init var choice of Player
        $choice = waitForInput(getPosChoice(),[1,2/*,4*/]);
        $choice2;

        $actionJoueur = null;
        if($choice == 1){
            interfaceCapacities($pkmnTeamJoueur[0]['Capacites']);
            $arrayChoise2 = ['c'];
            for($i=0;$i<4;++$i){
                if(isset($pkmnTeamJoueur[0]['Capacites'][$i]['Name']) && $pkmnTeamJoueur[0]['Capacites'][$i]['PP'] > 0){
                    array_push($arrayChoise2, ($i));
                }
            }
            // -- VERIFIER SI PP SUP A 0 --
            $choice2 = waitForInput(getPosChoice(), $arrayChoise2);

        }
        elseif($choice == 2){
            $a = $pkmnTeamJoueur[0];
            $choicesPkmnTeam = displayPkmnTeam($pkmnTeamJoueur);
            $choice2 = selectPkmn($choicesPkmnTeam, $pkmnTeamJoueur, $pkmnTeamEnemy[0]);
            if($choice2 != 'c'){           
                displayGameHUD($pkmnTeamJoueur, $pkmnTeamEnemy);
            }
        }
        // elseif($choice == 4){
            //     exitGame();
            // }
        $actionJoueur = "$choice $choice2";

        // Si aucune action choisie, retour au début
        if($actionJoueur == null || $choice2 == 'c'){            
            continue;
        }      

        //  CHOIX DE IA SUR ATK
        $actionEnemy = iaChoice($pkmnTeamJoueur, $pkmnTeamEnemy);
        
        // COMBAT AVEC ACTION JOUEUR ET ACTION ENEMY
        fight($pkmnTeamJoueur, $pkmnTeamEnemy, 
        $actionJoueur, $actionEnemy); 
    } 
    if($pkmnTeamEnemy[0]['Stats']['Health'] <= 0 ){
        endPkmnDied($pkmnTeamJoueur,$pkmnTeamEnemy[0]);
    }
}

function fight(&$pkmnTeamJoueur,&$pkmnTeamEnemy, $actionJoueur, $actionEnemy){
    clearBoiteDialogue();
    
    $actionsTurn = [];

    // voir quelle action a choisi le joueur 
    $arrayJoueur = explode(" ", $actionJoueur);
    $actionJoueur = ['choice' => $arrayJoueur, 'teamAtk' => &$pkmnTeamJoueur, 'teamDef' => &$pkmnTeamEnemy, 'isjoueur' =>true];
    // voir quelle action a choisi l'ennemi 
    $arrayEnemy = explode(" ", $actionEnemy);
    $actionEnemy = ['choice' => $arrayEnemy, 'teamAtk' => &$pkmnTeamEnemy, 'teamDef' => &$pkmnTeamJoueur, 'isjoueur' =>false];
    
    // array_push($actionsTurn, $actionEnemy,$actionJoueur);
    // array_push($actionsTurn, $actionJoueur); // first
    // print($pkmnTeamJoueur[0]['Capacites'][$arrayJoueur[1]]);
    // sleep(2);
    $priorityJoueur = isActionBePriority($pkmnTeamJoueur[0], $arrayJoueur); 
    // isset($pkmnTeamJoueur[0]['Capacites'][$arrayJoueur[1]]) ? $pkmnTeamJoueur[0]['Capacites'][$arrayJoueur[1]] : 0;
    $priorityEnemy = isActionBePriority($pkmnTeamEnemy[0], $arrayEnemy); 

    $joueurPriority = whichPkmnHasPriority($pkmnTeamJoueur[0],$pkmnTeamEnemy[0], $priorityJoueur, $priorityEnemy);
    
    if($joueurPriority /*|| $actionPriority*/){   
        array_push($actionsTurn, $actionJoueur); // first
        array_push($actionsTurn, $actionEnemy);
    }
    else{
        array_push($actionsTurn, $actionEnemy); // first
        array_push($actionsTurn, $actionJoueur);
    }
    // $actionPriority = false;
    foreach($actionsTurn as $action){

        if($action['choice'][0] == '2' || $action['choice'][0] == '3'){
            // $actionPriority = $action['isjoueur'];
            $a = &$action;
            array_unshift($actionsTurn, $a); // ajoute $a en premier index
            for($i=1;$i<count($actionsTurn);++$i){
                if($actionsTurn[$i] == $a){
                    array_splice($actionsTurn, $i, 1);
                }
            }
        }
    }

    // si switch/ item, priorite sur les actions
    $aPkmnIsDead = false;
    foreach($actionsTurn as &$action){
        // print_r($action['choice']);
        // sleep(5);
        if($action['choice'][0] == '1' && !$aPkmnIsDead){
            $pkmnAtk = &$action['teamAtk'][0]; // first pkmn de l'attaquant
            $pkmnDef = &$action['teamDef'][0]; // first pkmn du défenseur
            $capacite = &$action['teamAtk'][0]['Capacites'][$action['choice'][1]];
            $aPkmnIsDead = attackByJustOnePkmn($pkmnAtk,$pkmnDef, $capacite, !$action['isjoueur']);
        }
        elseif($action['choice'][0] == '2'){
            switchPkmn($action['teamAtk'], $action['choice'][1]);

            clearPkmnHUD($action['teamAtk'], $action['isjoueur']);
            pkmnAppearinBattle($action['isjoueur'], $action['teamAtk'][0]);// faire apparaitre pkmn j
            usleep(500000);
            refreshDisplayOnePkmn($action['teamAtk'], $action['isjoueur']);
        }
    }
    if(!isPkmnDead_simple($pkmnTeamJoueur[0])){
        damageTurn($pkmnTeamJoueur[0], true);
    }
    if(!isPkmnDead_simple($pkmnTeamEnemy[0])){
        damageTurn($pkmnTeamEnemy[0], false);
    }  
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function isActionBePriority($pkmn, $action){
    if($action[0] != '1'){
        return 0;
    }
    $priority = isset($pkmn['Capacites'][$action[1]]) ? $pkmn['Capacites'][$action[1]] : 0;
    return $priority;
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function endPkmnDied(&$pkmnTeamJoueur, &$pkmnE){
    messageBoiteDialogue("You've fainted " . $pkmnE['Name']);
    foreach($pkmnTeamJoueur as &$pkmn){
        getExp($pkmn, expToGive($pkmn, $pkmnE));
        return;
    }   
}   

function endBattle($pkmnTeamJoueur, $pnj){
    resetTeamStatsTemp($pkmnTeamJoueur);
    if(!isTeamPkmnAlive($pkmnTeamJoueur)){
        messageBoiteDialogue("You lost");
    }
    else{
        messageBoiteDialogue("You've fainted " . $pnj['Nom']);
        sleep(2);
        messageBoiteDialogue($pnj['Dialogues']['end']);
        sleep(2);
    }
}
?>