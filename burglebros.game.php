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
  * burglebros.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class burglebros extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels( array( 
            'actionsRemaining' => 10,
            'entranceTile' => 11
        ) ); 

        $this->cards = self::getNew( "module.common.deck" );
        $this->cards->init( "card" );
        $this->tiles = self::getNew( "module.common.deck" );
        $this->tiles->init( "tile" );
        $this->tokens = self::getNew( "module.common.deck" );
        $this->tokens->init( "token" );     
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "burglebros";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue( 'actionsRemaining', 4 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        $this->createDecks($this->card_types, $this->card_info);
        $this->createDecks($this->patrol_types, $this->patrol_info);

        $tiles = array ();
        $index = 0;
        foreach ( $this->tile_types as $type => $nbr ) {
            $tiles [] = array('type' => $type, 'type_arg' => $index, 'nbr' => $nbr);
            $index++;
        }
        $this->tiles->createCards( $tiles, 'tile_deck' );

        $this->setupTiles();
        $this->flipTile(1, 5);

        // Guards
        $tokens = array ();
        for ($floor=1; $floor <= 3; $floor++) { 
            $tokens [] = array('type' => 'guard', 'type_arg' => $floor, 'nbr' => 1);
        }
        $this->tokens->createCards( $tokens );
        foreach ($players as $player_id => $player) {
            $player_token = array('type' => 'player', 'type_arg' => $player_id, 'nbr' => 1);
            $this->tokens->createCards(array($player_token), 'hand', $player_id);
        }

        // Activate first player (which is in general a good idea :) )
        $current_player_id = $this->activeNextPlayer();

        // Move first player token to entrance
        $flipped = $this->getFlippedTiles(1);
        $entrance = array_shift($flipped)['id'];
        self::setGameStateInitialValue( 'entranceTile', $entrance );
        $hand = $this->tokens->getPlayerHand($current_player_id);
        $current_player_token = array_shift($hand);
        $this->tokens->moveCard($current_player_token['id'], 'tile', $entrance);

        // Move guard
        $this->cards->pickCardForLocation('patrol1_deck', 'patrol1_discard');
        $guard_entrance = $this->cards->getCardOnTop('patrol1_discard');
        $floor1_tiles = $this->getTiles(1);
        $guard_tokens = $this->tokens->getCardsOfType('guard', 1);
        $guard_token1 = array_shift($guard_tokens);
        foreach ($floor1_tiles as $tile_id => $tile) {
            if ($tile['location_arg'] == $guard_entrance['type_arg']) {
                $this->tokens->moveCard($guard_token1['id'], 'tile', $tile_id);
            }   
        }
        $this->cards->pickCardForLocation('patrol1_deck', 'patrol1_discard');

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        $result = array_merge($result, $this->gatherCardData('card', $this->card_types, $this->card_info));
        $result = array_merge($result, $this->gatherCardData('patrol', $this->patrol_types, $this->patrol_info));

        $tiles = array();
        $index = 0;
        foreach ( $this->tile_types as $type => $nbr ) {
            $tiles [] = array('id'=> $index, 'type' => $type);
            $index++;
        }
        $result['tile_types'] = $tiles;

        $result['floor1'] = $this->getTiles(1);
        $result['floor2'] = $this->getTiles(2);
        $result['floor3'] = $this->getTiles(3);

        $result['guard_tokens'] = $this->tokens->getCardsOfType('guard');
        $result['player_tokens'] = $this->tokens->getCardsOfType('player');
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */
    function createDecks($types, $info) {
        // Create cards
        foreach ( $types as $type => $desc ) {
            $cards = array ();
            foreach ( $info[$type] as $index => $value ) {
                $nbr = isset($value['nbr']) ? $value['nbr'] : 1;
                $cards [] = array('type' => $type, 'type_arg' => $index, 'nbr' => $nbr);
            }
            $deck_name = $desc['name'].'_deck';
            $this->cards->createCards( $cards, $deck_name );

            // Shuffle deck
            $this->cards->shuffle($deck_name);
        }
    }

    function gatherCardData($prefix, $types, $info) {
        $result = array();
        $result[$prefix.'_types'] = array();
        foreach ( $types as $type => $desc ) {
            $card_info = array();
            foreach ($info[$type] as $index => $value) {
                $card_info [] = array('type' => $type, 'index' => $index, 'name' => $value['name']);
            }

            $deck_name = $desc['name'].'_deck';
            $result[$deck_name] = $this->cards->getCardsInLocation( $deck_name );
            $result[$prefix.'_types'][$desc['name']] = array('deck' => $deck_name, 'cards' => $card_info);
        }
        return $result;
    }

    function setupTiles() {
        $safes = $this->tiles->getCardsOfType('safe');
        $stairs = $this->tiles->getCardsOfType('stairs');
        
        // Grab a safe and stair for each floor, and move to the floor "deck"
        for ($floor=0; $floor < 3; $floor++) { 
            $safe = array_shift($safes);
            $stair = array_shift($stairs);
            $card_ids = array($safe['id'], $stair['id']);
            $this->tiles->moveCards($card_ids, 'floor'.($floor + 1));
        }
        $this->tiles->shuffle('tile_deck');
        // Grab 14 more tiles per floor "deck" and shuffle
        for ($floor=0; $floor < 3; $floor++) { 
            $this->tiles->pickCardsForLocation(14, 'tile_deck', 'floor'.($floor + 1));
            $this->tiles->shuffle('floor'.($floor + 1));
        }
    }

    function getFlippedTiles($floor) {
        return self::getCollectionFromDB("SELECT card_id id FROM tile WHERE card_location='floor$floor' and flipped=1");
    }

    function getTiles($floor) {
        $tiles = $this->tiles->getCardsInLocation("floor$floor", null, 'location_arg');
        $flipped = $this->getFlippedTiles($floor);
        foreach ($tiles as &$tile) {
            if (!isset($flipped[$tile['id']])) {
                $tile['type'] = ''; // face-down
                $tile['type_arg'] = -1;
            }
        }
        return $tiles;
    }

    function flipTile($floor, $location_arg) {
        self::DbQuery("UPDATE tile SET flipped=1 WHERE card_location='floor$floor' and card_location_arg=$location_arg");
    }

    function nextAction() {
        $actionsRemaining = self::incGameStateValue('actionsRemaining', -1);
        if ($actionsRemaining == 0) {
            $this->gamestate->nextState('moveGuard');
        } else {
            $this->gamestate->nextState('nextAction');
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in burglebros.action.php)
    */

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    function peek( $floor, $location_arg ) {
        self::checkAction('peek');
        $this->flipTile( $floor, $location_arg );
        self::notifyAllPlayers('peek', '', array(
            'floor' => $floor,
            'tiles' => $this->getTiles($floor),
        ));
        $this->nextAction();
    }

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */
    function argPlayerTurn() {
        return array(
            'actionsRemaining' => self::getGameStateValue('actionsRemaining')
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

    function stMoveGuard() {
        $this->gamestate->nextState( 'nextPlayer' );
    }

    function stNextPlayer() {
        $player_id = self::activeNextPlayer();
        self::giveExtraTime( $player_id );
        self::setGameStateValue('actionsRemaining', 4);
        $hand = $this->tokens->getPlayerHand($player_id);
        $token = array_shift($hand);
        if ($token) {
            $entrance = self::getGameStateValue('entranceTile');
            $this->tokens->moveCard($token['id'], 'tile', $entrance);
        }
        

        $this->gamestate->nextState( 'playerTurn' );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
