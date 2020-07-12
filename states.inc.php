<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * burglebros implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * states.inc.php
 *
 * burglebros game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with 'game' type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by 'st' (ex: 'stMyGameStateName').
   _ possibleactions: array that specify possible player actions on this step. It allows you to use 'checkAction'
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in 'nextState' PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on 'onEnteringState' or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

 
$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        'name' => 'gameSetup',
        'description' => '',
        'type' => 'manager',
        'action' => 'stGameSetup',
        'transitions' => array( '' => 8 )
    ),
    
    // Note: ID=2 => your first state

    8 => array(
        'name' => 'startingTile',
        'description' => clienttranslate('${actplayer} must choose a starting tile'),
        'descriptionmyturn' => clienttranslate('${you} must choose a starting tile'),
        'type' => 'activeplayer',
        'args' => 'argStartingTile',
        'possibleactions' => array( 'chooseStartingTile' ),
        'transitions' => array( '' => 9 )
    ),

    9 => array(
        'name' => 'playerTurn',
        'description' => clienttranslate('${actplayer} may do ${actions_remaining} actions or pass'),
        'descriptionmyturn' => clienttranslate('${you} may do ${actions_remaining} actions or pass'),
        'type' => 'activeplayer',
        'args' => 'argPlayerTurn',
        'possibleactions' => array( 'hack', 'move', 'peek', 'addSafeDie', 'rollSafeDice', 'playCard', 'characterAction', 'pass', 'escape' ),
        'transitions' => array( 'nextAction' => 9, 'endTurn' => 10, 'nextPlayer' => 12, 'cardChoice' => 13, 'tileChoice' => 14, 'gameOver' => 99 )
    ),    

    10 => array(
        'name' => 'endTurn',
        'description' => 'Triggering end of turn effects...',
        'type' => 'game',
        'action' => 'stEndTurn',
        'updateGameProgression' => true,
        'transitions' => array( 'moveGuard' => 11 )
    ), 

    11 => array(
        'name' => 'moveGuard',
        'description' => 'Guard is moving...',
        'type' => 'game',
        'action' => 'stMoveGuard',
        'updateGameProgression' => true,
        'transitions' => array( 'nextPlayer' => 12, 'caught' => 99 )
    ),

    12 => array(
        'name' => 'nextPlayer',
        'description' => '',
        'type' => 'game',
        'action' => 'stNextPlayer',
        'transitions' => array( 'playerTurn' => 9 )
    ),

    13 => array(
        'name' => 'cardChoice',
        'description' => clienttranslate('${card_name}: ${actplayer} must choose ${choice_description}'),
        'descriptionmyturn' => clienttranslate('${card_name}: ${you} must choose ${choice_description}'),
        'type' => 'activeplayer',
        'args' => 'argCardChoice',
        'possibleactions' => array( 'selectCardChoice', 'cancelCardChoice' ),
        'transitions' => array( 'nextAction' => 9, 'endTurn' => 10, 'tileChoice' => 14 )
    ),

    14 => array(
        'name' => 'tileChoice',
        'description' => clienttranslate('${tile_name}: ${actplayer} must choose an option'),
        'descriptionmyturn' => clienttranslate('${tile_name}: ${you} must choose an option'),
        'type' => 'activeplayer',
        'args' => 'argTileChoice',
        'possibleactions' => array( 'selectTileChoice' ),
        'transitions' => array( 'nextAction' => 9, 'endTurn' => 10 )
    ),
    
/*
    Examples:
    
    2 => array(
        'name' => 'nextPlayer',
        'description' => '',
        'type' => 'game',
        'action' => 'stNextPlayer',
        'updateGameProgression' => true,   
        'transitions' => array( 'endGame' => 99, 'nextPlayer' => 10 )
    ),
    
    10 => array(
        'name' => 'playerTurn',
        'description' => clienttranslate('${actplayer} must play a card or pass'),
        'descriptionmyturn' => clienttranslate('${you} must play a card or pass'),
        'type' => 'activeplayer',
        'possibleactions' => array( 'playCard', 'pass' ),
        'transitions' => array( 'playCard' => 2, 'pass' => 2 )
    ), 

*/    
   
    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        'name' => 'gameEnd',
        'description' => clienttranslate('End of game'),
        'type' => 'manager',
        'action' => 'stGameEnd',
        'args' => 'argGameEnd'
    )

);



