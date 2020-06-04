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
            'entranceTile' => 11,
            'safeDieCount1' => 12,
            'safeDieCount2' => 13,
            'safeDieCount3' => 14,
            'motionTileEntered' => 15,
            'patrolDieCount1' => 16,
            'patrolDieCount2' => 17,
            'patrolDieCount3' => 18,
            'laboratoryTileEntered' => 19,
            'invisibleSuitActive' => 20,
            'empPlayer' => 21
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
        self::setGameStateInitialValue( 'safeDieCount1', 0 );
        self::setGameStateInitialValue( 'safeDieCount2', 0 );
        self::setGameStateInitialValue( 'safeDieCount3', 0 );
        self::setGameStateInitialValue( 'motionTileEntered', 0x000 ); // Bit vector
        self::setGameStateInitialValue( 'patrolDieCount1', 2 );
        self::setGameStateInitialValue( 'patrolDieCount2', 3 );
        self::setGameStateInitialValue( 'patrolDieCount3', 4 );
        self::setGameStateInitialValue( 'laboratoryTileEntered', 0x000 ); // Bit vector
        self::setGameStateInitialValue( 'invisibleSuitActive', 0 );
        self::setGameStateInitialValue( 'empPlayer', 0 );
        
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
            $tokens [] = array('type' => 'crack', 'type_arg' => $floor, 'nbr' => 1);
        }
        $tokens [] = array('type' => 'hack', 'type_arg' => 0, 'nbr' => 18);
        $tokens [] = array('type' => 'safe', 'type_arg' => 0, 'nbr' => 22);
        $tokens [] = array('type' => 'stealth', 'type_arg' => 0, 'nbr' => 18);
        $tokens [] = array('type' => 'alarm', 'type_arg' => 0, 'nbr' => 9);
        $tokens [] = array('type' => 'open', 'type_arg' => 0, 'nbr' => 6);
        $tokens [] = array('type' => 'keypad', 'type_arg' => 0, 'nbr' => 3);
        $tokens [] = array('type' => 'stairs', 'type_arg' => 0, 'nbr' => 3);
        $tokens [] = array('type' => 'thermal', 'type_arg' => 0, 'nbr' => 2);
        $this->tokens->createCards( $tokens );
        foreach ($players as $player_id => $player) {
            $player_token = array('type' => 'player', 'type_arg' => $player_id, 'nbr' => 1);
            $this->tokens->createCards(array($player_token), 'hand', $player_id);
            $this->cards->pickCard('characters_deck', $player_id);
        }

        // Activate first player (which is in general a good idea :) )
        $current_player_id = $this->activeNextPlayer();

        // TODO: REMOVE!!!
        $this->cards->pickCardsForLocation(13, 'tools_deck', 'hand', $current_player_id);

        // Move first player token to entrance
        $flipped = $this->getFlippedTiles(1);
        $entrance = array_keys($flipped)[0];
        $this->handleTilePeek($this->tiles->getCard($entrance));
        self::setGameStateInitialValue( 'entranceTile', $entrance );
        $hand = $this->tokens->getPlayerHand($current_player_id);
        $current_player_token = array_shift($hand);
        $this->tokens->moveCard($current_player_token['id'], 'tile', $entrance);
        $this->pickTokensForTile('stairs', $entrance);

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
        $sql = "SELECT player_id id, player_score score, player_stealth_tokens stealth_tokens FROM player ";
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
        $result['player_tokens'] = $this->tokens->getCardsOfType('player');
        $result['generic_tokens'] = $this->getGenericTokens();
        
        $safe_tokens = $this->tokens->getCardsOfType('crack');
        foreach ($safe_tokens as $id => &$value) {
            $floor = $value['type_arg'];
            $value['die_num'] = self::getGameStateValue("safeDieCount$floor");
        }
        $result['crack_tokens'] = $safe_tokens;

        $patrol_tokens = $this->tokens->getCardsOfType('patrol');
        foreach ($patrol_tokens as $id => &$value) {
            $floor = $value['type_arg'];
            $value['die_num'] = self::getGameStateValue("patrolDieCount$floor");
        }
        $result['patrol_tokens'] = $patrol_tokens;

        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        $result['current'] = array(
            'player_token' => $player_token,
            'tile' => $player_tile,
            'floor' => $player_tile['location'][5],
            'actions_remaining' => self::getGameStateValue('actionsRemaining')
        );
  
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
            $result[$prefix.'_types'][$type] = array('name' => $desc['name'], 'deck' => $deck_name, 'cards' => $card_info);
            $discard_name = $desc['name'].'_discard';
            $result[$discard_name] = $this->cards->getCardsInLocation( $discard_name );
        }
        return $result;
    }

    function getCardType($card) {
        $info = $this->card_info[$card['type']];
        return $info[$card['type_arg'] - 1]['name'];
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

    function getFloorAlarmTiles($floor) {
        $tile_location = "'floor$floor'";
        $sql = <<<SQL
            SELECT distinct tile.card_id id, tile.card_type type, tile.card_type_arg type_arg, tile.card_location location, tile.card_location_arg location_arg
            FROM token
            INNER JOIN tile ON token.card_location = 'tile' AND tile.card_id = token.card_location_arg
            WHERE token.card_type = 'alarm' AND tile.card_location = $tile_location
SQL;
        return self::getObjectListFromDB($sql);
    }

    function pickPatrolCard($floor) {

    }

    function nextPatrol($floor) {
        $alarm_tiles = $this->getFloorAlarmTiles($floor);
        if (count($alarm_tiles) > 0) {
            $guard_token = array_values($this->tokens->getCardsOfType('guard', $floor))[0];
            $guard_tile = $this->tiles->getCard($guard_token['location_arg']);

            $min_count = 100; // longest is 6, but I'm paranoid
            $tile_id = null;
            foreach ($alarm_tiles as $tile) {
                $path = $this->findShortestPath($floor, $guard_tile['location_arg'], $tile['location_arg']);
                if (count($path) < $min_count) {
                    // TODO: Allow players to choose guard's path
                    $min_count = count($path); 
                    $tile_id = $tile['id'];
                }
            }
        } else {
            $patrol = "patrol".$floor;
            $count = $this->cards->countCardInLocation($patrol.'_discard');
            if ($count == 16) {
                $this->cards->moveAllCardsInLocation($patrol.'_discard', $patrol.'_deck');
                $this->cards->shuffle($patrol.'_deck');
                $count = 0;
                $die_count = self::getGameStateValue("patrolDieCount$floor");
                if ($die_count < 6) {
                    self::setGameStateValue("patrolDieCount$floor", $die_count + 1);
                }
            }
            $this->cards->pickCardForLocation($patrol.'_deck', $patrol.'_discard', $count + 1);
            $patrol_entrance = $this->cards->getCardOnTop($patrol.'_discard');
            self::notifyAllPlayers('nextPatrol', '', array(
                'floor' => $floor,
                'cards' => $this->cards->getCardsInLocation($patrol.'_discard')
            ));
            $tile_id = $this->findTileOnFloor($floor, $patrol_entrance['type_arg'] - 1)['id'];
        }
        $patrol_token = array_values($this->tokens->getCardsOfType('patrol', $floor))[0];
        $this->moveToken($patrol_token['id'], 'tile', $tile_id);
    }

    function setupPatrol($guard_token, $floor) {
        $patrol = "patrol".$floor;
        $this->cards->pickCardForLocation($patrol.'_deck', $patrol.'_discard');
        $guard_entrance = $this->cards->getCardOnTop($patrol.'_discard');
        $floor_tiles = $this->getTiles($floor);
        foreach ($floor_tiles as $tile) {
            if ($tile['location_arg'] == $guard_entrance['type_arg'] - 1) {
                $this->moveToken($guard_token['id'], 'tile', $tile['id']);
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
        self::notifyAllPlayers('tileFlipped', '', array(
            'tile' => $this->findTileOnFloor($floor, $location_arg)
        ));
    }

    function nextAction($action_cost = 1) {
        $actionsRemaining = self::incGameStateValue('actionsRemaining', -$action_cost);
        if ($actionsRemaining == 0) {
            $this->gamestate->nextState('endTurn');
        } else {
            $this->gamestate->nextState('nextAction');
        }
    }

    function isTileAdjacent($tile, $other_tile, $walls=null, $variant='move') {
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
        if ($variant == 'guard') {
            return ($same_floor && $adjacent && !$blocked);
        } elseif($variant == 'peek') {
            return ($same_floor && $adjacent && !$blocked) ||
                $this->stairsAreAdjacent($tile, $other_tile) ||
                $this->stairsAreAdjacent($other_tile, $tile) ||
                $this->atriumIsAdjacent($tile, $other_tile) ||
                $this->thermalBombStairsAreAdjacent($tile, $other_tile);
        } else {
            return ($same_floor && $adjacent && !$blocked) ||
                ($same_floor && $adjacent && $tile['type'] == 'secret-door') ||
                $this->stairsAreAdjacent($tile, $other_tile) ||
                $this->stairsAreAdjacent($other_tile, $tile) ||
                $this->thermalBombStairsAreAdjacent($tile, $other_tile) ||
                ($tile['type'] == 'service-duct' && $other_tile['type'] == 'service-duct');
        }
    }

    function stairsAreAdjacent($tile, $other_tile) {
        return $tile['type'] == 'stairs' &&
            $tile['location'][5] + 1 == $other_tile['location'][5] &&
            $tile['location_arg'] == $other_tile['location_arg'];
    }

    function atriumIsAdjacent($tile, $other_tile) {
        return $other_tile['type'] == 'atrium' &&
            $tile['location_arg'] == $other_tile['location_arg'] &&
            ($tile['location'][5] + 1 == $other_tile['location'][5] || $tile['location'][5] - 1 == $other_tile['location'][5]);
    }

    function thermalBombStairsAreAdjacent($tile, $other_tile) {
        return $this->tokensInTile('thermal', $tile['id']) &&
            $this->tokensInTile('thermal', $other_tile['id']);
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
                $this->moveToken($guard_token['id'], 'tile', $tile_id);
                $movement--;
                $this->checkCameras(array('guard_id'=>$guard_token['id']));
                $tile = $this->tiles->getCard($tile_id);
                $this->checkPlayerStealth($tile);
                $this->clearTileTokens('alarm', $tile_id);
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

    function deductStealth($player_id, $amount = 1) {
        self::DbQuery("UPDATE player SET player_stealth_tokens = player_stealth_tokens - $amount WHERE player_id = '$player_id'");
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers('message', clienttranslate( '${player_name} ${action} one stealth' ), array(
            'action' => $amount < 0 ? 'gained' : 'lost',
            'player_name' => $players[$player_id]['player_name']
        ));
    }

    function atriumGuardsDebug($tile_id) {
        var_dump($this->atriumGuards($this->tiles->getCard($tile_id)));
    }

    function atriumGuards($tile) {
        $player_floor = $tile['location'];
        $player_location_arg = $tile['location_arg'];
        $sql = <<<SQL
            SELECT count(*) > 0 as seen
            FROM tile
            INNER JOIN token ON token.card_location_arg = tile.card_id
            WHERE tile.card_location != '$player_floor'
                AND tile.card_location_arg = '$player_location_arg'
                AND token.card_location = 'tile'
                AND token.card_type = 'guard'
SQL;
        return self::getUniqueValueFromDB($sql);
    }

    function checkPlayerStealth($tile) {
        $guard_token = array_values($this->tokens->getCardsOfType('guard', $tile['location'][5]))[0];
        $guard_tile = $this->tiles->getCard($guard_token['location_arg']);

        $current_player_id = self::getCurrentPlayerId();
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        
        $is_guard_tile = $tile['id'] == $guard_token['location_arg'];
        $is_player_tile = $tile['id'] == $player_token['location_arg'];
        
        if ($is_guard_tile && $is_player_tile) {
            $this->deductStealth($current_player_id);
            return;
        }

        // TODO: This doesn't seem to handle player moving into foyer
        $is_foyer = $is_guard_tile && $player_tile['type'] == 'foyer' && $this->isTileAdjacent($player_tile, $guard_tile, null, 'guard');
        if ($is_foyer) {
            $this->deductStealth($current_player_id);
            return;
        }

        if ($player_tile['type'] == 'atrium') {
            if (($is_player_tile && $this->atriumGuards($player_tile)) ||
                    ($is_guard_tile && $guard_tile['location_arg'] == $player_tile['location_arg']))
            $this->deductStealth($current_player_id);
            return;
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
                return $this->isTileAdjacent($tile, $current_tile, $walls, 'guard');
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

    function getGenericTokens() {
        $types = implode(array_keys($this->token_colors), "','");
        $tokens = self::getCollectionFromDB("SELECT card_id id, card_type type, card_location location, card_location_arg location_arg FROM token WHERE card_location != 'deck' and card_type in ('$types')");
        foreach ($tokens as &$token) {
            $token['letter'] = strtoupper($token['type'][0]);
            $token['color'] = $this->token_colors[$token['type']];
        }
        return $tokens;
    }

    function getPlacedTokens($types) {
        $types_arg = "('".implode($types,"','")."')";
        $rows = self::getObjectListFromDB("SELECT card_location_arg id, card_id token_id FROM token WHERE card_type in $types_arg AND card_location = 'tile'");
        $result = array();
        foreach ($rows as $row) {
            if (!isset($result[$row['id']])) {
                $result[$row['id']] = array();
            }
            $result[$row['id']] [] = $row['token_id'];
        }
        return $result;
    }

    function notifyPlayerHand($player_id) {
        self::notifyAllPlayers('playerHand', '', array(
            $player_id => $this->cards->getPlayerHand($player_id)
        ));
    }

    function performSafeDiceRollDebug($floor,$dice_count) {
        $safe_tile = array_values($this->tiles->getCardsOfTypeInLocation('safe', null, "floor$floor"))[0];
        $this->performSafeDiceRoll($safe_tile,intval($dice_count));
    }

    function performSafeDiceRoll($safe_tile, $dice_count) {
        $floor = $safe_tile['location'][5];
        $tiles = $this->getTiles($floor);
        $placed_tokens = $this->getPlacedTokens(array('safe'));
        $rolls = $this->rollDice($dice_count);
        $this->notifyRoll($rolls, 'safe');

        $safe_row = floor($safe_tile['location_arg'] / 4);
        $safe_col = $safe_tile['location_arg'] % 4;
        $cracked_count = 0;
        foreach ($tiles as $tile) {
            $row = floor($tile['location_arg'] / 4);
            $col = $tile['location_arg'] % 4;
            if (($row == $safe_row || $col == $safe_col)) {
                if (!isset($placed_tokens[$tile['id']])) {
                    if(isset($rolls[intval($tile['safe_die'])])) {
                        $this->pickTokensForTile('safe', $tile['id']);
                        $cracked_count++;
                    }
                } else {
                    $cracked_count++;
                }
            }
        }
        // Safe is open
        if ($cracked_count == 6) {
            $current_player_id = self::getCurrentPlayerId();
            $this->cards->pickCard('tools_deck', $current_player_id);
            $this->cards->pickCard('loot_deck', $current_player_id);
            $this->notifyPlayerHand($current_player_id);

            $safe_token = array_values($this->tokens->getCardsOfType('crack', $floor))[0];
            if ($safe_token['location'] == 'tile') {
                $this->moveToken($safe_token['id'], 'deck');
            }
            for ($lower_floor=$floor; $lower_floor >= 1; $lower_floor--) { 
                $die_count = self::getGameStateValue("patrolDieCount$lower_floor");
                if ($die_count < 6) {
                    self::setGameStateValue("patrolDieCount$lower_floor", $die_count + 1);
                }
            }
        }
    }

    function getTokens($ids) {
        $tokens = $this->tokens->getCards($ids);
        foreach ($tokens as $token_id => &$token) {
            if (isset($this->token_colors[$token['type']])) {
                $token['letter'] = strtoupper($token['type'][0]);
                $token['color'] = $this->token_colors[$token['type']];
            }
        }
        return $tokens;
    }

    function moveTokens($ids, $location, $location_arg=0) {
        $this->tokens->moveCards($ids, $location, $location_arg);
        self::notifyAllPlayers('tokensPicked', '', array(
            'tokens' => $this->getTokens($ids)
        ));
    }

    function moveToken($id, $location, $location_arg=0) {
        $this->moveTokens(array($id), $location, $location_arg);
    }

    function pickTokensForTile($type, $tile_id, $nbr = 1) {
        $token_ids = array_keys($this->tokens->getCardsOfTypeInLocation($type, null, 'deck'));
        $ids = array();
        for ($i=0; $i < $nbr; $i++) { 
            $ids [] = $token_ids[$i];
        }
        $this->moveTokens($ids, 'tile', $tile_id);
    }

    function clearTileTokens($type, $tile_id=null) {
        $tokens = $this->tokens->getCardsOfTypeInLocation($type, null, 'tile', $tile_id);
        $ids = array();
        foreach ($tokens as $token) {
            $ids [] = $token['id'];
        }
        $this->moveTokens($ids, 'deck');
    }

    function rollDice($dice_count) {
        $rolls = array();
        for ($i=0; $i < $dice_count; $i++) { 
            $result = bga_rand(1, 6);
            $rolls[$result] = isset($rolls[$result]) ? $rolls[$result] + 1 : 1;
        }
        return $rolls;
    }

    function notifyRoll($rolls, $for) {
        $roll_list = array();
        for ($i=1; $i <= 6; $i++) { 
            if (isset($rolls[$i])) {
                $count = $rolls[$i];
                while ($count > 0) {
                    $roll_list [] = $i;
                    $count--;
                }
            }
        }
        self::notifyAllPlayers('message', clienttranslate( '${player_name} rolled ${roll} for ${for}' ), array(
            'player_name' => self::getActivePlayerName(),
            'roll' => implode($roll_list, ','),
            'for' => $for
        ));
    }

    function rollDebug($dice_count) {
        $this->notifyRoll($this->rollDice(intval($dice_count)), 'debug');
    }

    function attemptKeypadRoll($tile) {
        $open = $this->getPlacedTokens(array('open'));
        if (isset($open[$tile['id']])) {
            return TRUE; // Skip
        }

        $previous = $this->getPlacedTokens(array('keypad'));
        $count = isset($previous[$tile['id']]) ? count($previous[$tile['id']]) + 1 : 1;
        $rolls = $this->rollDice($count);
        $this->notifyRoll($rolls, 'keypad');
        if (isset($rolls[6])) {
            $this->pickTokensForTile('open', $tile['id']);
            if (isset($previous[$tile['id']])) {
                foreach ($previous[$tile['id']] as $token_id) {
                    $this->moveToken($token_id, 'deck');
                }
            }
            return TRUE;
        }

        if(self::getGameStateValue('actionsRemaining') > 1) {
            $this->pickTokensForTile('keypad', $tile['id']);
        }
        return FALSE;
    }

    function tokensInTile($type, $tile_id) {
        $tokens = $this->tokens->getCardsOfTypeInLocation($type, null, 'tile', $tile_id);
        return count($tokens) > 0;
    }

    function hackOrTrigger($tile) {
        if ($this->tokensInTile('guard', $tile['id']) || $this->tokensInTile('alarm', $tile['id']) || self::getGameStateValue('empPlayer') != 0) {
            return;
        }

        $type = $tile['type'];
        $tokens = $this->getPlacedTokens(array('hack'));
        $computer_tile = array_values($this->tiles->getCardsOfType("$type-computer"))[0];
        if (isset($tokens[$computer_tile['id']])) {
            $to_move = $tokens[$computer_tile['id']][0];
            $this->moveToken($to_move, 'deck');
        } else {
            $this->triggerAlarm($tile, TRUE);
        }
    }

    function handleTilePeek($tile) {
        $type = $tile['type'];
        if ($type == 'stairs') {
            $floor = $tile['location'][5];
            if ($floor < 3) {
                $upper_tile = $this->findTileOnFloor($floor + 1, $tile['location_arg']);
                $this->pickTokensForTile('stairs', $upper_tile['id']);
            }
        } elseif ($type == 'lavatory') {
            $this->pickTokensForTile('stealth', $tile['id'], 3);
        }
    }

    function setTileBit($state_name, $tile_id) {
        $tile_bit = 1 << self::getUniqueValueFromDB("SELECT safe_die FROM tile WHERE card_id = '$tile_id'");
        $tile_entered = self::getGameStateValue($state_name);
        self::setGameStateValue($state_name, $tile_entered | $tile_bit);
        return ($tile_entered & $tile_bit) != 0x0;
    }

    function handleTileMovement($tile, $player_tile, $player_token, $flipped_this_turn) {
        $id = $tile['id'];
        $type = $tile['type'];
        $actionsRemaining = self::getGameStateValue('actionsRemaining');
        $cancel_move = false;
        if ($type == 'deadbolt') {
            $people = $this->getPlacedTokens(array('player', 'guard'));
            if (!isset($people[$id]) || count($people[$id]) == 0) {
                if ($actionsRemaining < 3) {
                    $cancel_move = true;
                } else {
                    self::incGameStateValue('actionsRemaining', -2); // One is deducted already
                }
            }
        } elseif ($type == 'keypad') {
            $cancel_move = !$this->attemptKeypadRoll($tile);
        } elseif ($type == 'fingerprint') {
            $this->hackOrTrigger($tile);
        } elseif ($type == 'laser') {
            // TODO: How do I make them choose?
            if ($actionsRemaining < 2) {
                $this->hackOrTrigger($tile);
            } else {
                self::incGameStateValue('actionsRemaining', -1);
            }
        } elseif($type == 'motion') {
            $this->setTileBit('motionTileEntered', $id);
        } elseif($type == 'laboratory') {
            $prev_value = $this->setTileBit('laboratoryTileEntered', $id);
            if (!$prev_value) {
                $current_player_id = $player_token['type_arg'];
                $this->cards->pickCard('tools_deck', $current_player_id);
                $this->notifyPlayerHand($current_player_id);
            }
        } elseif($type == 'detector') {
            $hand = $this->cards->getPlayerHand($player_token['type_arg']);
            foreach ($hand as $card_id => $card) {
                if ($card['type'] == 1 || $card['type'] == 2) {
                    $this->triggerAlarm($tile);
                    break;
                }
            }
        } elseif ($type == 'walkway' && $flipped_this_turn) {
            // Fall down
            $floor = $tile['location'][5];
            if ($floor > 1) {
                $lower_tile = $this->findTileOnFloor($floor - 1, $tile['location_arg']);
                $cancel_move = true;
                $this->moveToken($player_token['id'], 'tile', $lower_tile['id']);
                $this->flipTile($floor - 1, $lower_tile['location_arg']);
            }
        }

        if (!$cancel_move) {
            // Handle exit
            $exit_type = $player_tile['type'];
            if ($exit_type == 'motion') {
                $exit_id = $player_tile['id'];
                $motion_bit = 1 << self::getUniqueValueFromDB("SELECT safe_die FROM tile WHERE card_id = '$exit_id'");
                $motion_entered = self::getGameStateValue('motionTileEntered');
                if ($motion_entered & $motion_bit) {
                    $this->hackOrTrigger($player_tile);
                }
            }
        
            $this->moveToken($player_token['id'], 'tile', $id);
        }
        return !$cancel_move;
    }

    function checkCameras($params) {
        $player_clause = '';
        $guard_clause = '';
        if (isset($params['guard_id'])) {
            $guard_id = $params['guard_id'];
            $guard_clause = "AND token.card_id = $guard_id";
        } else {
            $player_id = $params['player_id'];
            $player_clause = "AND token.card_id = $player_id";
        }
        $sql = <<<SQL
            SELECT distinct tile.card_id id, tile.card_type type, tile.card_location location, tile.card_location_arg location_arg
            FROM tile
            INNER JOIN token ON token.card_location = 'tile' AND token.card_location_arg = tile.card_id
            WHERE tile.card_type = 'camera' AND token.card_type = 'player' $player_clause AND EXISTS (
                SELECT token.card_id
                FROM tile
                INNER JOIN token ON token.card_location = 'tile' AND token.card_location_arg = tile.card_id
                WHERE tile.card_type = 'camera' AND token.card_type = 'guard' $guard_clause and tile.flipped=1)
SQL;
        $camera_tiles = self::getObjectListFromDB($sql);
        foreach ($camera_tiles as $tile) {
            $this->triggerAlarm($tile);
        }
    }

    function triggerAlarm($tile, $skip_token_checks=FALSE) {
        if (!$skip_token_checks) {
            if($this->tokensInTile('guard', $tile['id']) || $this->tokensInTile('alarm', $tile['id']) || self::getGameStateValue('empPlayer') != 0) {
                return;
            }
        }

        $floor = $tile['location'][5];
        $patrol = "patrol".$floor;
        $patrol_token = array_values($this->tokens->getCardsOfType('patrol', $floor))[0];
        $this->moveToken($patrol_token['id'], 'tile', $tile['id']);
        $this->pickTokensForTile('alarm', $tile['id']);
        self::notifyAllPlayers('message', clienttranslate( 'An alarm was triggered' ), array());
    }

    function handleToolEffect($player_id, $card) {
        $type = $this->getCardType($card);
        if ($type == 'emp') {
            self::setGameStateValue('empPlayer', $player_id);
            $this->clearTileTokens('alarm');
        } elseif($type == 'invisible-suit') {
            self::setGameStateValue('invisibleSuitActive', 1);
            self::incGameStateValue('actionsRemaining', 1);
        } elseif ($type == 'makeup-kit') {
            $tile = $this->getPlayerTile($player_id);
            $player_tokens = $this->tokens->getCardsOfTypeInLocation('player', null, 'tile', $tile['id']);
            foreach ($player_tokens as $token) {
                $this->deductStealth($token['type_arg'], -1); // Give them back one
            }
        } elseif($type == 'rollerskates') {
            self::incGameStateValue('actionsRemaining', 2);
        } elseif ($type == 'smoke-bomb') {
            $tile = $this->getPlayerTile($player_id);
            $this->pickTokensForTile('stealth', $tile['id'], 3);
        } elseif ($type == 'thermal-bomb') {
            $tile = $this->getPlayerTile($player_id);
            $this->pickTokensForTile('thermal', $tile['id']);
            $floor = $tile['location'][5];
            $other_tile = null;
            // TODO: Let player choose direction
            if ($floor < 3) {
                $other_tile = $this->findTileOnFloor($floor + 1, $tile['location_arg']);
            } else {
                $other_tile = $this->findTileOnFloor($floor - 1, $tile['location_arg']);
            }
            $this->pickTokensForTile('thermal', $other_tile['id']);
            $this->triggerAlarm($tile);
        }
    }

    function handleEventEffectDebug($name) {
        $current_player_id = self::getCurrentPlayerId();
        $type_arg = null;
        foreach ($this->card_info[3] as $index => $value) {
            if ($value['name'] == $name) {
                $type_arg = $index + 1;
            }
        }
        $card = array_values($this->cards->getCardsOfType(3, $type_arg))[0];
        $this->handleEventEffect($current_player_id, $card);
    }

    function handleEventEffect($player_id, $card) {
        $type = $this->getCardType($card);
        if ($type == 'crash') {
            $tile = $this->getPlayerTile($player_id);
            $floor = $tile['location'][5];
            $patrol_token = array_values($this->tokens->getCardsOfType('patrol', $floor))[0];
            $this->moveToken($patrol_token['id'], 'tile', $tile['id']);
        } elseif ($type == 'freight-elevator') {
            $player_token = $this->getPlayerToken($player_id);
            $tile = $this->getPlayerTile($player_id, $player_token);
            $floor = $tile['location'][5];
            if ($floor < 3) {
                $upper_tile = $this->findTileOnFloor($floor + 1, $tile['location_arg']);
                $this->moveToken($player_token['id'], 'tile', $upper_tile['id']);
                $this->flipTile($floor + 1, $tile['location_arg']);
                $guard_token = array_values($this->tokens->getCardsOfType('guard', $floor + 1))[0];
                if ($guard_token['location'] == 'deck') {
                    $this->setupPatrol($guard_token, $floor + 1);
                }
            }
        } elseif ($type == 'jury-rig') {
            $this->cards->pickCard('tools_deck', $player_id);
            $this->notifyPlayerHand($player_id);
        } elseif ($type == 'keycode-change') {
            $this->clearTileTokens('open');
        } elseif ($type == 'lampshade') {
            $player_token = $this->getPlayerToken($player_id);
            $this->deductStealth($player_token['type_arg'], -1); // Give them back one
        } elseif($type == 'lost-grip') {
            $player_token = $this->getPlayerToken($player_id);
            $tile = $this->getPlayerTile($player_id, $player_token);
            $floor = $tile['location'][5];
            if ($floor > 1) {
                $lower_tile = $this->findTileOnFloor($floor - 1, $tile['location_arg']);
                $this->moveToken($player_token['id'], 'tile', $lower_tile['id']);
                $this->flipTile($floor - 1, $tile['location_arg']);
            }
        } elseif ($type == 'reboot') {

        } elseif ($type == 'switch-signs') {
            $player_token = $this->getPlayerToken($player_id);
            $tile = $this->getPlayerTile($player_id, $player_token);
            $floor = $tile['location'][5];

            $guard_token = array_values($this->tokens->getCardsOfType('guard', $floor))[0];
            $patrol_token = array_values($this->tokens->getCardsOfType('patrol', $floor))[0];
            $this->moveToken($guard_token['id'], 'tile', $patrol_token['location_arg']);
            $this->moveToken($patrol_token['id'], 'tile', $guard_token['location_arg']);
        } elseif ($type == 'where-is-he') {
            $player_token = $this->getPlayerToken($player_id);
            $tile = $this->getPlayerTile($player_id, $player_token);
            $floor = $tile['location'][5];
            
            $guard_token = array_values($this->tokens->getCardsOfType('guard', $floor))[0];
            $patrol_token = array_values($this->tokens->getCardsOfType('patrol', $floor))[0];
            
            $this->moveToken($guard_token['id'], 'tile', $patrol_token['location_arg']);
            $this->nextPatrol($floor);
        }
    }

    function handleCardEffect($player_id, $card) {
        if ($card['type'] == 1) {
            $this->handleToolEffect($player_id, $card);
        } elseif ($card['type'] == 3) {
            $this->handleEventEffect($player_id, $card);
        }
    }

    function getPlayerToken($player_id) {
        return array_values($this->tokens->getCardsOfType('player', $player_id))[0];
    }

    function getPlayerTile($player_id, $player_token=null) {
        if (!$player_token) {
            $player_token = $this->getPlayerToken($player_id);
        }
        return $this->tiles->getCard($player_token['location_arg']);
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
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        $to_peek = $this->findTileOnFloor($floor, $location_arg);
        $flipped = $this->getFlippedTiles($floor);

        if (isset($flipped[$to_peek['id']])) {
            throw new BgaUserException(self::_("Tile is already visible"));
        }
        if (!$this->isTileAdjacent($to_peek, $player_tile, null, 'peek')) {
            throw new BgaUserException(self::_("Tile is not adjacent"));
        }

        $this->handleTilePeek($to_peek);
        $this->flipTile( $floor, $location_arg );
        // self::notifyAllPlayers('peek', '', array(
        //     'floor' => $floor,
        //     'tiles' => $this->getTiles($floor),
        // ));
        $this->nextAction();
    }

    function move( $floor, $location_arg ) {
        self::checkAction('move');
        
        $current_player_id = self::getCurrentPlayerId();
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        $to_move = $this->findTileOnFloor($floor, $location_arg);
        $flipped = $this->getFlippedTiles($floor);

        if (!$this->isTileAdjacent($to_move, $player_tile, null, 'move')) {
            throw new BgaUserException(self::_("Tile is not adjacent"));
        }

        $flipped_this_turn = !isset($flipped[$to_move['id']]);
        if ($flipped_this_turn) {
            $this->handleTilePeek($to_move);
        }
        $did_move = $this->handleTileMovement($to_move, $player_tile, $player_token, $flipped_this_turn);
        $this->flipTile( $floor, $location_arg );
        $invisible_suit = self::getGameStateValue('invisibleSuitActive') == 1;
        if ($did_move) {
            $guard_token = array_values($this->tokens->getCardsOfType('guard', $to_move['location'][5]))[0];
            if ($guard_token['location'] == 'deck') {
                $this->setupPatrol($guard_token, $to_move['location'][5]);
            }
            // TODO: Refetch player tile in case token moved by side effect
            if (!$invisible_suit) {
                $this->checkPlayerStealth($to_move);
            }
        }
        if (!$invisible_suit) {
            $this->checkCameras(array('player_id'=>$player_token['id']));
        }
        // Notify no matter what, might have flipped tiles
        self::notifyAllPlayers('move', '', array(
            'floor' => $floor,
            'tiles' => $this->getTiles($floor),
        ));
        $this->nextAction();
    }

    function addSafeDie() {
        self::checkAction('addSafeDie');
        $actionsRemaining = self::getGameStateValue('actionsRemaining');
        if ($actionsRemaining < 2) {
            throw new BgaUserException(self::_("Adding a die requires 2 actions"));
        }
        $current_player_id = self::getCurrentPlayerId();
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        if ($player_tile['type'] != 'safe') {
            throw new BgaUserException(self::_("Tile is not a safe"));
        }
        $floor = $player_tile['location'][5];
        $safe_token = array_values($this->tokens->getCardsOfType('crack', $floor))[0];
        if ($safe_token['location'] != 'tile') {
            $this->moveToken($safe_token['id'], 'tile', $player_tile['id']);
        }
        self::incGameStateValue("safeDieCount$floor", 1);
        $this->nextAction(2);
    }

    function rollSafeDice() {
        self::checkAction('rollSafeDice');
        $current_player_id = self::getCurrentPlayerId();
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        if ($player_tile['type'] != 'safe') {
            throw new BgaUserException(self::_("Tile is not a safe"));
        }
        $floor = $player_tile['location'][5];
        $dice_count = self::getGameStateValue("safeDieCount$floor");
        if ($dice_count == 0) {
            throw new BgaUserException(self::_("You have not added any dice"));
        }
        $this->performSafeDiceRoll($player_tile, $dice_count);
        $this->nextAction();
    }

    function hack() {
        self::checkAction('hack');
        $current_player_id = self::getCurrentPlayerId();
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        if (strpos($player_tile['type'], 'computer') === FALSE) {
            throw new BgaUserException(self::_("Tile is not a computer"));
        }
        $existing = $this->tokens->getCardsOfTypeInLocation('hack', null, 'tile', $player_token['location_arg']);
        if (count($existing) >= 6) {
            throw new BgaUserException(self::_("Only 6 hack tokens can be added to this tile"));
        }
        $this->pickTokensForTile('hack', $player_token['location_arg']);
        $this->nextAction();
    }

    function playCard($card_id) {
        self::checkAction('playCard');

        $current_player_id = self::getCurrentPlayerId();
        $card = $this->cards->getCard($card_id);
        if ($card['location'] != 'hand' || $card['location_arg'] != $current_player_id) {
            throw new BgaUserException(self::_("Card is not in your hand"));
        }

        if ($card['type'] != 1) {
            throw new BgaUserException(self::_("Card is not a tool"));
        }

        $this->handleCardEffect($current_player_id, $card);
        // $this->cards->moveCard($card['id'], 'tools_discard');
        // $this->notifyPlayerHand($current_player_id);

        $this->gamestate->nextState('nextAction');
    }

    function pass() {
        self::checkAction('pass');
        $actionsRemaining = self::getGameStateValue('actionsRemaining');
        if ($actionsRemaining >= 2) {
            $current_player_id = self::getCurrentPlayerId();

            $count = $this->cards->countCardInLocation('events_discard');
            $this->cards->pickCardForLocation('events_deck', 'events_discard', $count + 1);
            $event_card = $this->cards->getCardOnTop('events_discard');
            $this->handleCardEffect($current_player_id, $event_card);
        }
        $this->gamestate->nextState('endTurn');
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

    function stEndTurn() {
        $current_player_id = self::getCurrentPlayerId();
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        $type = $player_tile['type'];
        if ($type == 'thermo') {
            $this->triggerAlarm($player_tile);
        }
        self::setGameStateValue('invisibleSuitActive', 0);
        $this->gamestate->nextState( 'moveGuard' );
    }

    function stMoveGuard() {
        $current_player_id = self::getCurrentPlayerId();
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        $floor = $player_tile['location'][5];
        $movement = self::getGameStateValue("patrolDieCount$floor") + count($this->getFloorAlarmTiles($floor));
        $this->moveGuard($floor, $movement);
        $this->gamestate->nextState( 'nextPlayer' );
    }

    function stNextPlayer() {
        $player_id = self::activeNextPlayer();
        self::giveExtraTime( $player_id );
        self::setGameStateValue('actionsRemaining', 4);
        self::setGameStateValue('motionTileEntered', 0x000);
        $hand = $this->tokens->getPlayerHand($player_id);
        $token = array_shift($hand);
        if ($token) {
            $entrance = self::getGameStateValue('entranceTile');
            $this->moveToken($token['id'], 'tile', $entrance);
        }
        $emp_player = self::getGameStateValue('empPlayer');
        if ($emp_player == $player_id) {
            self::setGameStateValue('empPlayer', 0);
        }
        $this->clearTileTokens('keypad');

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
