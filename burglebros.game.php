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

        
        $index = 1;
        $values = array();
        foreach ( $this->tile_types as $type => $dice ) {
            foreach ($dice as $die) {
                $values [] = "('$type',$index,'deck',$die)";
                $index++;
            }
        }
        $sql = "INSERT INTO tile (card_type,card_type_arg,card_location,safe_die) VALUES ";
        self::DbQuery($sql.implode($values, ','));

        $this->setupTiles();
        $this->flipTile(1, 6);

        // Guards
        $tokens = array ();
        for ($floor=1; $floor <= 3; $floor++) { 
            $tokens [] = array('type' => 'guard', 'type_arg' => $floor, 'nbr' => 1);
            $tokens [] = array('type' => 'patrol', 'type_arg' => $floor, 'nbr' => 1);
        }
        $this->tokens->createCards( $tokens );
        foreach ($players as $player_id => $player) {
            $player_token = array('type' => 'player', 'type_arg' => $player_id, 'nbr' => 1);
            $this->tokens->createCards(array($player_token), 'hand', $player_id);
        }

        // Activate first player (which is in general a good idea :) )
        $current_player_id = $this->activeNextPlayer();

        $this->cards->pickCard('tools_deck', $current_player_id);
        $this->cards->pickCard('loot_deck', $current_player_id);
        $this->cards->pickCard('events_deck', $current_player_id);

        // Move first player token to entrance
        $flipped = $this->getFlippedTiles(1);
        $entrance = array_keys($flipped)[0];
        self::setGameStateInitialValue( 'entranceTile', $entrance );
        $hand = $this->tokens->getPlayerHand($current_player_id);
        $current_player_token = array_shift($hand);
        $this->tokens->moveCard($current_player_token['id'], 'tile', $entrance);

        // Move guard and patrol
        $guard_token = array_values($this->tokens->getCardsOfType('guard', 1))[0];
        $this->setupPatrol($guard_token, 1);

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
        foreach ($result['players'] as $player_id => &$player) {
            $player['hand'] = $this->cards->getPlayerHand($player_id);
        }
  
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
        $result['walls'] = $this->getWalls();

        $result['guard_tokens'] = $this->tokens->getCardsOfType('guard');
        $result['patrol_tokens'] = $this->tokens->getCardsOfType('patrol');
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
                $cards [] = array('type' => $type, 'type_arg' => $index + 1, 'nbr' => $nbr);
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
                $card_info [] = array('type' => $type, 'index' => $index + 1, 'name' => $value['name']);
            }

            $deck_name = $desc['name'].'_deck';
            $result[$deck_name] = $this->cards->getCardsInLocation( $deck_name );
            $result[$prefix.'_types'][$desc['name']] = array('deck' => $deck_name, 'cards' => $card_info);
            $discard_name = $desc['name'].'_discard';
            $result[$discard_name] = $this->cards->getCardsInLocation( $discard_name );
        }
        return $result;
    }

    function setupTiles() {
        $safes = $this->tiles->getCardsOfType('safe');
        $stairs = $this->tiles->getCardsOfType('stairs');
        
        // Grab a safe and stair for each floor, and move to the floor "deck"
        for ($floor=1; $floor <= 3; $floor++) { 
            $safe = array_shift($safes);
            $stair = array_shift($stairs);
            $card_ids = array($safe['id'], $stair['id']);
            $this->tiles->moveCards($card_ids, "floor$floor");

            $this->setupWalls($floor);
        }
        $this->tiles->shuffle('deck');
        // Grab 14 more tiles per floor "deck" and shuffle
        for ($floor=1; $floor <= 3; $floor++) { 
            $this->tiles->pickCardsForLocation(14, 'deck', "floor$floor");
            $this->tiles->shuffle("floor$floor");
        }
    }

    function setupWalls($floor) {
        foreach ($this->default_walls[$floor] as $dir => $positions) {
            $sql = 'INSERT INTO wall (floor, vertical, position) VALUES ';
            $values = array();
            foreach ($positions as $position) {
                $vertical = $dir == 'vertical' ? 1 : 0;
                $values [] = "($floor,$vertical,$position)";
            }
            $sql .= implode($values, ',');
            self::DbQuery($sql);
        }
    }

    function getWalls() {
        return self::getObjectListFromDB("SELECT * from wall");
    }

    function getFlippedTiles($floor) {
        return self::getCollectionFromDB("SELECT card_id id, safe_die FROM tile WHERE card_location='floor$floor' and flipped=1", true);
    }

    function getTiles($floor) {
        $tiles = $this->tiles->getCardsInLocation("floor$floor", null, 'location_arg');
        $flipped = $this->getFlippedTiles($floor);
        foreach ($tiles as &$tile) {
            if (!isset($flipped[$tile['id']])) {
                $tile['type'] = 'back'; // face-down
                $tile['type_arg'] = 0;
                $tile['safe_die'] = 0;
            } else {
                $tile['safe_die'] = $flipped[$tile['id']];
            }
        }
        return $tiles;
    }

    function nextPatrol($floor) {
        $patrol = "patrol".$floor;
        $count = $this->cards->countCardInLocation($patrol.'_discard');
        $this->cards->pickCardForLocation($patrol.'_deck', $patrol.'_discard', $count + 1);
        $patrol_entrance = $this->cards->getCardOnTop($patrol.'_discard');
        $patrol_token = array_values($this->tokens->getCardsOfType('patrol', $floor))[0];
        $tile = $this->findTileOnFloor($floor, $patrol_entrance['type_arg'] - 1);
        $this->tokens->moveCard($patrol_token['id'], 'tile', $tile['id']);
    }

    function setupPatrol($guard_token, $floor) {
        $patrol = "patrol".$floor;
        $this->cards->pickCardForLocation($patrol.'_deck', $patrol.'_discard');
        $guard_entrance = $this->cards->getCardOnTop($patrol.'_discard');
        $floor_tiles = $this->getTiles($floor);
        foreach ($floor_tiles as $tile) {
            if ($tile['location_arg'] == $guard_entrance['type_arg'] - 1) {
                $this->tokens->moveCard($guard_token['id'], 'tile', $tile['id']);
                break;
            }   
        }
        $this->nextPatrol($floor);
    }

    function findTileOnFloor($floor, $location_arg) {
        return array_values($this->tiles->getCardsInLocation("floor$floor", $location_arg))[0];
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

    function isTileAdjacent($tile, $other_tile, $walls=null, $is_guard=false) {
        if (!isset($walls)) {
            $walls = $this->getWalls();
        }
        
        $tindex = $tile['location_arg'];
        $trow = floor($tindex / 4);
        $tcol = $tindex % 4;

        $pindex = $other_tile['location_arg'];
        $prow = floor($pindex / 4);
        $pcol = $pindex % 4;

        $same_floor = $tile['location'] == $other_tile['location'];
        $adjacent = ($trow == $prow && abs($tcol - $pcol) == 1) || ($tcol == $pcol && abs($trow - $prow) == 1);
        $blocked = false;
        foreach ($walls as $wall) {
            if($wall['floor'] == $tile['location'][5]) {
                $wrow = $wall['vertical'] == 1 ? floor($wall['position'] / 3) : $wall['position'] % 3;
                $wcol = $wall['vertical'] == 0 ? floor($wall['position'] / 3) : $wall['position'] % 3;
                $vertical = ($trow == $prow && $trow == $wrow && abs($tcol - $pcol) == 1) && min($tcol, $pcol) == $wcol;
                $horizontal = ($tcol == $pcol && $tcol == $wcol && abs($trow - $prow) == 1) && min($trow, $prow) == $wrow;
                if (($wall['vertical'] == 1 && $vertical) || ($wall['vertical'] == 0 && $horizontal)) {
                    $blocked = true;
                    break;
                }
            }
        }
        // if ($tile['location_arg'] == 5) {
        //     var_dump(array('other'=>$other_tile['location_arg'],'sf'=>$same_floor, 'adj'=>$adjacent, 'blk'=>$blocked));
        // }
        if ($is_guard) {
            return ($same_floor && $adjacent && !$blocked);
        } else {
            return ($same_floor && $adjacent && !$blocked) ||
                $this->stairsAreAdjacent($tile, $other_tile) ||
                $this->stairsAreAdjacent($other_tile, $tile) ||
                $tile['type'] == 'service-duct' && $other_tile['type'] == 'service-duct';
        }
    }

    function stairsAreAdjacent($tile, $other_tile) {
        return $tile['type'] == 'stairs' &&
            $tile['location'][5] + 1 == $other_tile['location'][5] &&
            $tile['location_arg'] == $other_tile['location_arg'];
    }

    function moveGuardDebug($floor) {
        return $this->moveGuard(intval($floor), intval($floor) + 1);
    }

    function moveGuard($floor, $movement) {
        $guard_token = array_values($this->tokens->getCardsOfType('guard', $floor))[0];
        $guard_tile = $this->tiles->getCard($guard_token['location_arg']);
        $patrol_token = array_values($this->tokens->getCardsOfType('patrol', $floor))[0];
        $patrol_tile = $this->tiles->getCard($patrol_token['location_arg']);

        $path = $this->findShortestPath($floor, $guard_tile['location_arg'], $patrol_tile['location_arg']);
        // var_dump($path);
        foreach ($path as $tile_id) {
            if ($tile_id != $guard_token['location_arg']) {
                $this->tokens->moveCard($guard_token['id'], 'tile', $tile_id);
                $movement--;
                if ($tile_id == $patrol_token['location_arg']) {
                    $this->nextPatrol($floor);
                    if ($movement > 0) {
                        $this->moveGuard($floor, $movement);
                    }
                }
                if ($movement == 0) {
                    break;
                }
            }
        }
    }

    function manhattanDistance($left, $right) {
        $lcol = $left % 4;
        $lrow = floor($left / 4);
        $rcol = $right % 4;
        $rrow = floor($right / 4);
        return abs($lcol - $rcol) + abs($lrow - $rrow);
    }

    function findShortestPathDebug($floor, $start, $end) {
        return $this->findShortestPath(intval($floor),intval($start),intval($end));
    }

    function lowestIn($values, $container) {
        asort($values);
        foreach ($values as $key => $value) {
            if (isset($container[$key])) {
                return $container[$key];
            }
        }
        throw new BgaUserException("Shouldn't get here");
    }

    function reconstructPath($came_from, $current) {
        $path = array($current);
        while(isset($came_from[$current])) {
            $current = $came_from[$current];
            array_unshift($path, $current);
        }
        return $path;
    }

    function findShortestPath($floor, $start, $end) {
        $tiles = array_values($this->tiles->getCardsInLocation("floor$floor", null, 'location_arg'));
        $walls = $this->getWalls();

        // An implementation of https://en.wikipedia.org/wiki/A*_search_algorithm
        // Returned path contains tile ids
        $open_set = array($start=>$start);
        $came_from = array();

        $g_score = array($start=>0);
        $f_score = array($start=>$this->manhattanDistance($start, $end));
        $iterations = 0;
        while (count($open_set) > 0) {
            $current = $this->lowestIn($f_score, $open_set);
            $current_tile = $tiles[$current];
            if ($current == $end) {
                return $this->reconstructPath($came_from, $current_tile['id']);
            }
            // var_dump($current);
            
            unset($open_set[$current]);
            
            $neighbors = array_filter($tiles, function($tile) use ($current_tile,$walls) {
                return $this->isTileAdjacent($tile, $current_tile, $walls, true);
            });
            foreach ($neighbors as $id => $neighbor) {
                $index = intval($neighbor['location_arg']);
                $g = $g_score[$current] + 1;
                if (!isset($g_score[$index]) || $g < $g_score[$index]) {
                    $came_from[$neighbor['id']] = $current_tile['id'];
                    $g_score[$index] = $g;
                    $f_score[$index] = $g + $this->manhattanDistance($current, $index);
                    if (!isset($open_set[$index])) {
                        $open_set[$index] = $index;
                    }
                }
            }
            $iterations++;
            // if ($iterations > 10) {
            //     break;
            // }
            // var_dump(array('os'=>$open_set,'fs'=>$f_score,'gs'=>$g_score,'cf'=>$came_from));
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
        
        $current_player_id = self::getCurrentPlayerId();
        $player_token = array_values($this->tokens->getCardsOfType('player', $current_player_id))[0];
        $player_tile = $this->tiles->getCard($player_token['location_arg']);
        $to_peek = $this->findTileOnFloor($floor, $location_arg);
        $flipped = $this->getFlippedTiles($floor);

        if (isset($flipped[$to_peek['id']])) {
            throw new BgaUserException(self::_("Tile is already visible"));
        }
        if (!$this->isTileAdjacent($to_peek, $player_tile)) {
            throw new BgaUserException(self::_("Tile is not adjacent"));
        }

        $this->flipTile( $floor, $location_arg );
        self::notifyAllPlayers('peek', '', array(
            'floor' => $floor,
            'tiles' => $this->getTiles($floor),
        ));
        $this->nextAction();
    }

    function move( $floor, $location_arg ) {
        self::checkAction('move');
        
        $current_player_id = self::getCurrentPlayerId();
        $player_token = array_values($this->tokens->getCardsOfType('player', $current_player_id))[0];
        $player_tile = $this->tiles->getCard($player_token['location_arg']);
        $to_move = $this->findTileOnFloor($floor, $location_arg);

        if (!$this->isTileAdjacent($to_move, $player_tile)) {
            throw new BgaUserException(self::_("Tile is not adjacent"));
        }

        $this->flipTile( $floor, $location_arg );
        $this->tokens->moveCard($player_token['id'], 'tile', $to_move['id']);
        $guard_token = array_values($this->tokens->getCardsOfType('guard', $to_move['location'][5]))[0];
        if ($guard_token['location'] == 'deck') {
            $this->setupPatrol($guard_token, $to_move['location'][5]);
        }
        self::notifyAllPlayers('move', '', array(
            'floor' => $floor,
            'tiles' => $this->getTiles($floor),
        ));
        $this->nextAction();
    }

    function pass() {
        self::checkAction('pass');
        $actionsRemaining = self::incGameStateValue('actionsRemaining', -1);
        if ($actionsRemaining >= 2) {
            $current_player_id = self::getCurrentPlayerId();
            $this->cards->pickCard('events_deck', $current_player_id);
        }
        $this->gamestate->nextState('moveGuard');
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
        $current_player_id = self::getCurrentPlayerId();
        $player_token = array_values($this->tokens->getCardsOfType('player', $current_player_id))[0];
        $player_tile = $this->tiles->getCard($player_token['location_arg']);
        $floor = $player_tile['location'][5];
        $this->moveGuard($floor, $floor + 1); // TODO: Store in state
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
