<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * burglebros implementation : © Brian Gregg baritonehands@gmail.com
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
            'empPlayer' => 21,
            'cardChoice' => 22,
            'characterAbilityUsed' => 23,
            'acrobatEnteredGuardTile' => 24,
            'tileChoice' => 25,
            'motionTileExitChoice' => 26,
            'playerChoice' => 27,
            'playerChoiceArg' => 36,
            'specialChoice' => 28,
            'specialChoiceArg' => 29,
            'firstAction' => 30,
            'drawToolsPlayer' => 31,
            'drawToolsNextPlayer' => 32,
            'stealthDepleted' => 33,
            'playerPass' => 34,
            'dropLoot' => 35,

            // Options
            'characterAssignment' => 100
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
        self::setGameStateInitialValue( 'cardChoice', 0 );
        self::setGameStateInitialValue( 'characterAbilityUsed', 0 );
        self::setGameStateInitialValue( 'acrobatEnteredGuardTile', 0 );
        self::setGameStateInitialValue( 'tileChoice', 0 );
        self::setGameStateInitialValue( 'motionTileExitChoice', 0 );
        self::setGameStateInitialValue( 'playerChoice', 0 );
        self::setGameStateInitialValue( 'playerChoiceArg', 0 );
        self::setGameStateInitialValue( 'firstAction', 1 );
        self::setGameStateInitialValue( 'drawToolsPlayer', 0 );
        self::setGameStateInitialValue( 'drawToolsNextPlayer', 0 );
        self::setGameStateInitialValue( 'stealthDepleted', 0 );
        self::setGameStateInitialValue( 'playerPass', 0 );
        self::setGameStateInitialValue( 'dropLoot', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        self::initStat( "table", "turns_number", 0 );
        self::initStat( "table", "tiles_unflipped", 0 );
        self::initStat( "table", "event_cards", 0 );
        self::initStat( "table", "alarm_triggered", 0 );

        self::initStat( "player", "turns_number", 0 );
        self::initStat( "player", "tools_drawn", 0 );
        self::initStat( "player", "tools_used", 0 );
        self::initStat( "player", "stealth_remaining", 0 );
        self::initStat( "player", "trade_confirmed", 0 );
        self::initStat( "player", "special_ability_use", 0 );

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
        shuffle($values);
        $sql = "INSERT INTO tile (card_type,card_type_arg,card_location,safe_die) VALUES ";
        self::DbQuery($sql.implode($values, ','));

        $this->setupTiles();

        // Guards
        $tokens = array ();
        for ($floor=1; $floor <= 3; $floor++) { 
            $tokens [] = array('type' => 'guard', 'type_arg' => $floor, 'nbr' => 1);
            $tokens [] = array('type' => 'patrol', 'type_arg' => $floor, 'nbr' => 1);
            $tokens [] = array('type' => 'crack', 'type_arg' => $floor, 'nbr' => 1);    # when a first die is added on the safe
        }
        $tokens [] = array('type' => 'hack', 'type_arg' => 0, 'nbr' => 19);
        $tokens [] = array('type' => 'safe', 'type_arg' => 0, 'nbr' => 22); # when a tile is validated by a safe roll
        // $tokens [] = array('type' => 'die', 'type_arg' => 0, 'nbr' => 21);  # store die values when rolling (handle stethoscope)
        $tokens [] = array('type' => 'stealth', 'type_arg' => 0, 'nbr' => 22);
        $tokens [] = array('type' => 'alarm', 'type_arg' => 0, 'nbr' => 9);
        $tokens [] = array('type' => 'open', 'type_arg' => 0, 'nbr' => 6);  # when a safe or keypad tile is opened
        $tokens [] = array('type' => 'keypad', 'type_arg' => 0, 'nbr' => 3);
        $tokens [] = array('type' => 'stairs', 'type_arg' => 0, 'nbr' => 3);
        $tokens [] = array('type' => 'thermal', 'type_arg' => 0, 'nbr' => 2);
        $tokens [] = array('type' => 'crowbar', 'type_arg' => 0, 'nbr' => 1);
        $tokens [] = array('type' => 'crow', 'type_arg' => 0, 'nbr' => 1);
        $tokens [] = array('type' => 'cat', 'type_arg' => 0, 'nbr' => 1);
        $this->tokens->createCards( $tokens );

        // Remove cards that don't make sense for the number of players
        if (count($players) == 1) {
            $this->moveCardsOutOfPlay('loot', 'gold-bar');
            $this->moveCardsOutOfPlay('characters', 'rook1');
            $this->moveCardsOutOfPlay('characters', 'rook2');
            $this->moveCardsOutOfPlay('events', 'freight-elevator');
            $this->moveCardsOutOfPlay('events', 'buddy-system');
            $this->moveCardsOutOfPlay('events', 'dead-drop');
            $this->moveCardsOutOfPlay('events', 'jump-the-gun');
        }
        // TODO: Add back cards once implemented/fixed
        // $this->moveCardsOutOfPlay('tools', 'crystal-ball');
        // $this->moveCardsOutOfPlay('tools', 'stethoscope');
        // $this->moveCardsOutOfPlay('events', 'squeak');
        // $this->moveCardsOutOfPlay('events', 'jury-rig');
        if ($options[100] == 1) {
            $this->moveCardsOutOfPlay('characters', 'acrobat2');
            $this->moveCardsOutOfPlay('characters', 'hacker2');
            $this->moveCardsOutOfPlay('characters', 'hawk2');
            $this->moveCardsOutOfPlay('characters', 'juicer2');
            $this->moveCardsOutOfPlay('characters', 'peterman2');
            $this->moveCardsOutOfPlay('characters', 'raven2');
            $this->moveCardsOutOfPlay('characters', 'rigger2');
            $this->moveCardsOutOfPlay('characters', 'rook2');
            $this->moveCardsOutOfPlay('characters', 'spotter2'); 
        }

        foreach ($players as $player_id => $player) {
            $player_token = array('type' => 'player', 'type_arg' => $player_id, 'nbr' => 1);
            $this->tokens->createCards(array($player_token), 'hand', $player_id);
            $character = $this->cards->pickCard('characters_deck', $player_id);
            $type = $this->getCardType($character);
            $name = substr($type, 0, -1);
            $nbr = substr($type, -1);
            $this->moveCardsOutOfPlay('characters', $name.($nbr == 1 ? 2 : 1));
            if ($this->getCardType($character) == 'rigger1') {
                $type_arg = $this->getCardTypeForName(1, 'dynamite');
                $dynamite = array_values($this->cards->getCardsOfType(1, $type_arg))[0];
                $this->cards->moveCard($dynamite['id'], 'hand', $player_id);
            }

            $this->pickTokens('stealth', 'player', $player_id, 3);
        }

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        // Move starting guard
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
            $player['character'] = $this->getPlayerCharacter($player_id);
        }
  
        $result = array_merge($result, $this->gatherCardData('card', $this->card_types, $this->card_info));
        $result = array_merge($result, $this->gatherCardData('patrol', $this->patrol_types, $this->patrol_info));

        $tiles = array();
        $index = 0;
        foreach ( $this->tile_types as $type => $nbr ) {
            $tiles [] = array('id'=> $index, 'type' => $type);
            $index++;
        }
        $result['tile_types'] = $tiles;

        $tokens = array();
        foreach ( $this->token_types as $index => $desc ) {
            $tokens[$desc['name']] = array('id'=> $index, 'color' => $desc['color']);
        }
        $result['token_types'] = $tokens;

        $result['floor1'] = $this->getTiles(1);
        $result['floor2'] = $this->getTiles(2);
        $result['floor3'] = $this->getTiles(3);
        $result['walls'] = $this->getWalls();

        $result['guard_tokens'] = $this->tokens->getCardsOfType('guard');
        $result['player_tokens'] = $this->tokens->getCardsOfType('player');
        $result['generic_tokens'] = $this->getGenericTokens();
        $result['card_tokens'] = $this->getCardTokens();
        
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
        if (self::getGameStateValue('stealthDepleted') || $this->allPlayersEscaped()) {
            return 100;
        } else {
            return $this->openSafes() * 25;
        }
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */
    function moveCardsOutOfPlay($deck, $name) {
        $type_id = $this->getDeckTypeForName($deck);
        $type_arg = $this->getCardTypeForName($type_id, $name);
        $oop = $this->cards->getCardsOfType($type_id, $type_arg);
        $this->cards->moveCards(array_keys($oop), "${deck}_oop");
    }

    function chooseStartingTile($tile_id) {
        $entrance = $this->tiles->getCard($tile_id);
        $floor = $entrance['location'][5];
        if ($floor != 1) {
            throw new BgaException(self::_("Starting tile must be on the first floor"));
        }
        $this->performPeek($entrance['id'], 'effect');

        // Move first player token to entrance
        self::setGameStateInitialValue( 'entranceTile', $tile_id );
        $hand = $this->tokens->getPlayerHand(self::getCurrentPlayerId());
        $current_player_token = array_shift($hand);
        $this->moveToken($current_player_token['id'], 'tile', $tile_id);
        $this->pickTokensForTile('stairs', $tile_id);

        $this->nextPatrol(1);

        $this->gamestate->nextState();
    }

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
            $result[$discard_name.'_top'] = $this->cards->getCardOnTop( $discard_name );
        }
        return $result;
    }

    function getPeekableTiles($player_tile, $variant='peek') {
        $peekable = array();
        $walls = $this->getWalls();
        for ($floor=1; $floor <= 3; $floor++) { 
            $tiles = $this->getTiles($floor);
            foreach ($tiles as $tile) {
                if($tile['id'] != $player_tile['id'] && $tile['type'] == 'back' && $this->isTileAdjacent($tile, $player_tile, $walls, $variant)) {
                    $peekable [] = $tile;
                }
            }
        }
        return $peekable;
    }

    function openSafes() {
        $safes = $this->tiles->getCardsOfType('safe');
        $open = 0;
        foreach ($safes as $tile_id => $tile) {
            if ($this->tokensInTile('open', $tile_id) == 0) {
                break;
            } else {
                $open++;
            }
        }
        return $open;
    }

    function canEscape($player_tile) {
        return $player_tile['type'] == 'stairs' && $player_tile['location'][5] == '3' && $this->openSafes() == 3;
    }

    function gatherCurrentData($current_player_id) {
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        $character = $this->getPlayerCharacter($current_player_id);
        $character['name'] = $this->getCardType($character);
        $actions_remaining = self::getGameStateValue('actionsRemaining'); 
        $actions_description = $actions_remaining > 0 ?
            self::_("$actions_remaining actions, free actions,") :
            self::_('free actions');
        return array(
            'escape' => $this->canEscape($player_tile),
            'peekable' => $this->getPeekableTiles($player_tile),
            'player_token' => $player_token,
            'other_players' => count($this->tokens->getCardsOfTypeInLocation('player', null, 'tile', $player_tile['id'])) - 1,
            'character' => $character,
            'character_action_enabled' => $this->characterActionEnabled($current_player_id, $character),
            'tile' => $player_tile,
            'tile_tokens' => $this->tokens->getCardsInLocation('tile', $player_tile['id']),
            'tile_cards' => $this->cards->getCardsInLocation('tile', $player_tile['id']),
            'floor' => $player_tile['location'][5],
            'actions_remaining' => $actions_remaining,
            'actions_description' => $actions_description,
        );
    }

    function getCardType($card) {
        $info = $this->card_info[$card['type']];
        return $info[$card['type_arg'] - 1]['name'];
    }
    function getDisplayedCardName($card_name) {
        // Remove last character if 1, replace last char if 2 by Advanced and replace '-'' by space
        if (substr($card_name, -1) == '1') {
            $card_name_displayed = substr($card_name, 0, -1);
        } elseif (substr($card_name, -1) == '2') {
            $card_name_displayed = substr($card_name, 0, -1).clienttranslate(" Advanced");
        } else {
            $card_name_displayed = str_replace('-', ' ', $card_name);
        }
        return ucfirst($card_name_displayed);
    }
    function getCardChoiceDescription($card) {
        $info = $this->card_info[$card['type']];
        return $info[$card['type_arg'] - 1]['choice_description'];
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

    function nextPatrol($floor, $force=FALSE) {
        $guard_token = array_values($this->tokens->getCardsOfType('guard', $floor))[0];
        $alarm_tiles = $this->getFloorAlarmTiles($floor);
        $has_alarms = count($alarm_tiles) > 0;
        $draw_patrol = !$has_alarms || $force;
        
        if ($draw_patrol) {
            $patrol = "patrol".$floor;
            do {
                $count = $this->cards->countCardInLocation($patrol.'_deck');
                if ($count == 0) {
                    // Out of play
                    $this->cards->moveAllCardsInLocation($patrol.'_oop', $patrol.'_deck');
                    $this->cards->moveAllCardsInLocation($patrol.'_discard', $patrol.'_deck');
                    $this->cards->shuffle($patrol.'_deck');
                    $count = 16;
                    $to_remove = $this->removePatrolPerPlayerCount($patrol);
                    $count -= $to_remove;
                    $die_count = self::getGameStateValue("patrolDieCount$floor");
                    if ($die_count < 6) {
                        $next_count = $die_count + 1;
                        self::setGameStateValue("patrolDieCount$floor", $die_count + 1);
                        self::notifyAllPlayers('message', clienttranslate("Guard on floor $floor now moves $next_count spaces"), []);
                        self::notifyAllPlayers('patrolDieIncreased', '', array(
                            'die_num' => $die_count + 1,
                            'token' => array_values($this->tokens->getCardsOfType('patrol', $floor))[0],
                            'floor' => $floor
                        ));
                    }
                }
                $patrol_entrance = $this->cards->pickCardForLocation($patrol.'_deck', $patrol.'_discard', 16 - $count);
                self::notifyAllPlayers('nextPatrol', '', array(
                    'floor' => $floor,
                    'cards' => $this->cards->getCardsInLocation($patrol.'_discard'),
                    'top' => $patrol_entrance
                ));
                $tile_id = $this->findTileOnFloor($floor, $patrol_entrance['type_arg'] - 1)['id'];
            } while($tile_id == $guard_token['location_arg']);
        }

        if ($has_alarms) {
            $guard_tile = $this->tiles->getCard($guard_token['location_arg']);

            $min_count = 100; // longest is 6, but I'm paranoid
            $tile_id = null;
            foreach ($alarm_tiles as $tile) {
                $path = $this->findShortestPathClockwise($floor, $guard_tile['location_arg'], $tile['location_arg']);
                if (count($path) < $min_count) {
                    // TODO: Allow players to choose guard's path
                    $min_count = count($path); 
                    $tile_id = $tile['id'];
                }
            }
        }

        $patrol_token = array_values($this->tokens->getCardsOfType('patrol', $floor))[0];
        $this->moveToken($patrol_token['id'], 'tile', $tile_id, TRUE);
    }

    function removePatrolPerPlayerCount($patrol) {
        $num_players = self::getPlayersNumber();
        $to_remove = 0;
        if ($num_players == 1) {
            $to_remove = 9;
        } elseif ($num_players == 2) {
            $to_remove = 6;
        } elseif ($num_players == 3) {
            $to_remove = 3;
        }
        $this->cards->pickCardsForLocation($to_remove, $patrol.'_deck', $patrol.'_oop');
        return $to_remove;
    }

    function setupPatrol($guard_token, $floor) {
        $patrol = "patrol".$floor;
        $this->removePatrolPerPlayerCount($patrol);
        $guard_entrance = $this->cards->pickCardForLocation($patrol.'_deck', $patrol.'_discard');
        $floor_tiles = $this->getTiles($floor);
        foreach ($floor_tiles as $tile) {
            if ($tile['location_arg'] == $guard_entrance['type_arg'] - 1) {
                $this->moveToken($guard_token['id'], 'tile', $tile['id'], TRUE);
                break;
            }   
        }
    }

    function findTileOnFloor($floor, $location_arg) {
        return array_values($this->tiles->getCardsInLocation("floor$floor", $location_arg))[0];
    }

    function flipTile($floor, $location_arg) {
        self::DbQuery("UPDATE tile SET flipped=1 WHERE card_location='floor$floor' and card_location_arg=$location_arg");
        self::notifyAllPlayers('tileFlipped', '', array(
            'tile' => $this->findTileOnFloor($floor, $location_arg),
            'floor' => $floor
        ));
    }

    function endAction($action_cost = 1) {
        $actions_remaining = self::incGameStateValue('actionsRemaining', -$action_cost);
        self::setGameStateValue('firstAction', 0);
        $this->gamestate->nextState('endAction');
    }

    function tileAdjacencyDetail($tile, $other_tile, $walls=null) {
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
        return array(
            'same_floor' => $same_floor,
            'adjacent' => $adjacent,
            'blocked' => $blocked
        );
    }

    function isTileAdjacent($tile, $other_tile, $walls=null, $variant='move') {
        $detail = $this->tileAdjacencyDetail($tile, $other_tile, $walls);

        $same_floor = $detail['same_floor'];
        $adjacent = $detail['adjacent'];
        $blocked = $detail['blocked'];
        
        if ($variant == 'guard') {
            return ($same_floor && $adjacent && !$blocked);
        } elseif($variant == 'peek') {
            return ($same_floor && $adjacent && !$blocked) ||
                $this->stairsAreAdjacent($tile, $other_tile) ||
                $this->stairsAreAdjacent($other_tile, $tile) ||
                $this->atriumIsAdjacent($tile, $other_tile) ||
                $this->thermalBombStairsAreAdjacent($tile, $other_tile) ||
                $this->walkwayIsAdjacent($tile, $other_tile);
        } elseif($variant == 'peekhole') {
            return ($same_floor && $adjacent) || $this->peekholeIsAdjacent($tile, $other_tile);
        } elseif($variant == 'hawk1') {
            return $this->hawk1IsAdjacent($detail);
        } elseif($variant == 'acrobat2') {
            return $this->acrobat2IsAdjacent($tile, $other_tile) ||
                $this->acrobat2IsAdjacent($other_tile, $tile);
        } else {
            $current_player_id = self::getCurrentPlayerId();
            $painting = $this->getPlayerLoot('painting', $current_player_id);
            $secret_door = $same_floor && $adjacent && $tile['type'] == 'secret-door';
            // TODO: This allows you to guess and reveal the other
            $service_duct = $tile['type'] == 'service-duct' && $other_tile['type'] == 'service-duct';
            if ($painting && (($secret_door && $blocked) || $service_duct)) {
                throw new BgaUserException(self::_('Cannot move this way while holding the Painting'));
            }
            return ($same_floor && $adjacent && !$blocked) ||
                $secret_door || $service_duct ||
                $this->stairsAreAdjacent($tile, $other_tile) ||
                $this->stairsAreAdjacent($other_tile, $tile) ||
                $this->thermalBombStairsAreAdjacent($tile, $other_tile) ||
                $this->walkwayIsAdjacent($tile, $other_tile);
        }
    }

    function stairsAreAdjacent($to, $from) {
        $time_lock = $this->getActiveEvent('time-lock');
        if ($time_lock) {
            return FALSE;
        }
        return $to['type'] == 'stairs' &&
            $to['location'][5] + 1 == $from['location'][5] &&
            $to['location_arg'] == $from['location_arg'];
    }

    function atriumIsAdjacent($to, $from) {
        return $from['type'] == 'atrium' &&
            $to['location_arg'] == $from['location_arg'] &&
            ($to['location'][5] + 1 == $from['location'][5] || $to['location'][5] - 1 == $from['location'][5]);
    }

    function thermalBombStairsAreAdjacent($to, $from) {
        return $this->tokensInTile('thermal', $to['id']) &&
            $this->tokensInTile('thermal', $from['id']);
    }

    function walkwayIsAdjacent($to, $from) {
        $gymnastics_adjacent = FALSE;
        $gymnastics = $this->getActiveEvent('gymnastics');
        if ($gymnastics) {
            $gymnastics_adjacent = 
                ($to['type'] == 'walkway' &&
                    $to['location'][5] + 1 == $from['location'][5] &&
                    $to['location_arg'] == $from['location_arg']) ||
                ($from['type'] == 'walkway' &&
                    $from['location'][5] + 1 == $to['location'][5] &&
                    $from['location_arg'] == $to['location_arg']);
        }
        return $gymnastics_adjacent ||
            ($from['type'] == 'walkway' &&
                $from['location'][5] - 1 == $to['location'][5] &&
                $to['location_arg'] == $from['location_arg']);
    }

    function peekholeIsAdjacent($to, $from) {
        return $to['location_arg'] == $from['location_arg'] &&
            ($to['location'][5] + 1 == $from['location'][5] || $to['location'][5] - 1 == $from['location'][5]);
    }

    function hawk1IsAdjacent($detail) {
        return $detail['same_floor'] && $detail['adjacent'] && $detail['blocked'] &&
            $this->getPlayerCharacter(self::getActivePlayerId(), 'hawk1') && 
            !self::getGameStateValue('characterAbilityUsed');
    }

    function acrobat2IsAdjacent($to, $from) {
        return $to['location'][5] + 1 == $from['location'][5] &&
            $to['location_arg'] == $from['location_arg'];
    }

    function hawk2PeekAllowed($player_tile, $target_tile) {
        $walls = $this->getWalls();
        $adjacent_flipped = array();
        $face_down = false;
        for ($floor=1; $floor <= 3; $floor++) { 
            $tiles = $this->getTiles($floor);
            foreach ($tiles as $tile) {
                if ($tile['id'] == $player_tile['id'] || ($tile['type'] != 'back' && $this->isTileAdjacent($tile, $player_tile, $walls))) {
                    $adjacent_flipped [] = $tile;
                } else if ($tile['id'] == $target_tile['id'] && $tile['type'] == 'back') {
                    $face_down = true;
                }
            }
        }
        if (!$face_down) {
            throw new BgaUserException(self::_('Tile is already visible'));
        }
        foreach ($adjacent_flipped as $tile) {
            if ($this->isTileAdjacent($target_tile, $tile, $walls)) {
                return true;
            }
        }
        throw new BgaUserException(self::_('Tile is not valid for the Enhance ability'));
    }

    function moveGuardDebug($floor) {
        return $this->moveGuard(intval($floor), intval($floor) + 1);
    }

    function performGuardMovementEffects($guard_token, $tile_id) {
        $this->moveToken($guard_token['id'], 'tile', $tile_id, TRUE);
        $this->checkCameras(array('guard_id'=>$guard_token['id']));
        $tile = $this->tiles->getCard($tile_id);
        $this->handleGuardSeesPlayerTile($tile);
        $this->clearTileTokens('alarm', $tile_id);
    }

    function moveGuard($floor, $movement) {
        $guard_token = array_values($this->tokens->getCardsOfType('guard', $floor))[0];
        $guard_tile = $this->tiles->getCard($guard_token['location_arg']);
        $patrol_token = array_values($this->tokens->getCardsOfType('patrol', $floor))[0];
        $patrol_tile = $this->tiles->getCard($patrol_token['location_arg']);

        $donut_type_id = $this->getCardTypeForName(1, 'donuts');
        $donuts = $this->cards->getCardsOfTypeInLocation(1, $donut_type_id, 'tile', $guard_tile['id']);
        if (count($donuts) > 0) {
            $this->cards->moveCard(array_keys($donuts)[0], 'tools_discard');
            $this->notifyTileCards($guard_tile['id']);
            return;
        }

        $path = $this->findShortestPathClockwise($floor, $guard_tile['location_arg'], $patrol_tile['location_arg']);
        // var_dump($path);
        foreach ($path as $tile_id) {
            if ($tile_id != $guard_token['location_arg']) {
                $movement--;
                if ($this->tokensInTile('crow', $tile_id)) {
                    $movement--;
                }
                $this->performGuardMovementEffects($guard_token, $tile_id);
                if (self::getGameStateValue('stealthDepleted')) {
                    break;
                }
                if ($tile_id == $patrol_token['location_arg']) {
                    $this->nextPatrol($floor);
                    if ($movement > 0) {
                        $this->moveGuard($floor, $movement);
                    }
                }
                if ($movement <= 0) {
                    break;
                }
            }
        }
    }

    function decrementPlayerStealth($player_id, $amount = 1) {
        self::DbQuery("UPDATE player SET player_stealth_tokens = player_stealth_tokens - $amount WHERE player_id = '$player_id'");
        $players = self::loadPlayersBasicInfos();
        $player_stealth = $this->tokens->getCardsOfTypeInLocation('stealth', null, 'player', $player_id);
        if ($amount > 0) {
            if (count($player_stealth) > 0) {
                $this->moveToken(array_keys($player_stealth)[0], 'deck', TRUE);
            } else {
                self::setGameStateValue('stealthDepleted', 1);
            }
        } else if($amount < 0) {
            $this->pickTokens('stealth', 'player', $player_id, -$amount);
        }
        self::notifyAllPlayers('message', clienttranslate( '${player_name} ${action} one stealth' ), array(
            'action' => $amount < 0 ? 'gained' : 'lost',
            'player_name' => $players[$player_id]['player_name']
        ));
    }

    function deductTileStealth($tile_id, $context) {
        $player_tokens = $this->tokens->getCardsOfTypeInLocation('player', null, 'tile', $tile_id);
        $tile_stealth = $this->tokens->getCardsOfTypeInLocation('stealth', null, 'tile', $tile_id);
        $current_player_id = self::getActivePlayerId();
        foreach ($player_tokens as $token) {
            if (count($tile_stealth) > 0) {
                // TODO: pick which players
                $stealth_token = array_shift($tile_stealth);
                $this->moveToken($stealth_token['id'], 'deck');
            } else if($context == 'guard' || $token['type_arg'] == $current_player_id) {
                $this->decrementPlayerStealth($token['type_arg']);
            }
        }
    }

    function getPlayerStealth($player_id) {
        return self::getUniqueValueFromDB("SELECT player_stealth_tokens FROM player WHERE player_id = '$player_id'");
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

    function handlePlayerEnteredGuardSight($tile) {
        $guard_token = array_values($this->tokens->getCardsOfType('guard', $tile['location'][5]))[0];
        $guard_tile = $this->tiles->getCard($guard_token['location_arg']);

        $current_player_id = self::getCurrentPlayerId();
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        
        $is_guard_tile = $tile['id'] == $guard_token['location_arg'];
        // TODO: I'm not sure if this variable is needed
        $is_player_tile = $tile['id'] == $player_token['location_arg'];
        
        if ($is_guard_tile && $is_player_tile) {
            $this->deductTileStealth($player_tile['id'], 'player');
            return;
        }

        $tiara = $this->getPlayerLoot('tiara', $current_player_id);
        $is_adjacent = $this->isTileAdjacent($player_tile, $guard_tile, null, 'guard');
        $is_foyer = $is_adjacent &&
            (($is_guard_tile && $player_tile['type'] == 'foyer') ||
                ($is_player_tile && ($player_tile['type'] == 'foyer' || $tiara)));
        if ($is_foyer) {
            $this->deductTileStealth($player_tile['id'], 'player');
            return;
        }

        if ($player_tile['type'] == 'atrium' && $is_player_tile && $this->atriumGuards($player_tile)) {
            $this->deductTileStealth($player_tile['id'], 'player');
            return;
        }
    }

    function handleGuardSeesPlayerTile($tile) {
        $this->deductTileStealth($tile['id'], 'guard');

        $player_tokens = $this->tokens->getCardsOfTypeInLocation('player', null, 'tile');
        foreach ($player_tokens as $token_id => $player_token) {
            $player_tile = $this->getPlayerTile($player_token['type_arg'], $player_token);

            $is_adjacent = $this->isTileAdjacent($tile, $player_tile, null, 'guard');
            $is_foyer = $is_adjacent && $player_tile['type'] == 'foyer';
            if ($is_foyer) {
                $this->deductTileStealth($player_tile['id'], 'guard');
            }

            // TODO: Double check Atrium won't deduct twice if guard is also there
            if ($tile['location_arg'] == $player_tile['location_arg'] && $player_tile['type'] == 'atrium') {
                $this->deductTileStealth($player_tile['id'], 'guard');
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
        var_dump($this->findShortestPathClockwise(intval($floor),intval($start),intval($end)));
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

    function directions($left, $right) {
        $ly = floor($left / 4);
        $lx = $left % 4;
        $ry = floor($right / 4);
        $rx = $right % 4;
        $dx = $lx - $rx;
        $dy = $ry - $ly;

        // Keep alphabetical
        $dirs = "";
        if ($dy > 0) {
            $dirs .= 'D';
        }
        if ($dx > 0) {
            $dirs .= 'L';
        }
        if ($dx < 0) {
            $dirs .= 'R';
        }
        if ($dy < 0) {
            $dirs .= 'U';
        }
        return $dirs;
    }

    function clockwiseDebug($current, $end, $left, $right) {
        var_dump($this->clockwise($current, $end, $left, $right));
    }

    function clockwise($current, $end, $left, $right) {
        $orientation = $this->directions($current, $end);
        $ldir = $this->directions($current, $left);
        $rdir = $this->directions($current, $right);
        $dirs = $ldir < $rdir ? $ldir.$rdir : $rdir.$ldir;
        $mappings = $this->clockwise_mappings[$orientation];
        $result = $mappings[$dirs];
        if ($result[0] == $ldir) {
            return $left;
        } else {
            return $right;
        }
    }

    function neighbors($tiles, $walls, $current_tile, $except=array()) {
        return array_filter($tiles, function($tile) use ($current_tile, $walls, $except) {
            return !in_array($tile['location_arg'], $except) && $this->isTileAdjacent($tile, $current_tile, $walls, 'guard');
        });
    }

    function breakTie($end, $paths) {
        if (count($paths) == 1) {
            return $paths[0];
        }

        sort($paths);

        if (count($paths[0]) != count($paths[1])) {
            return $paths[0];
        } else {
            $path1 = $paths[0];
            $path2 = $paths[1];
            $idx = 0;
            while($path1[$idx] == $path2[$idx]) $idx++;
            $most_cw = $this->clockwise($path1[$idx-1], $end, $path1[$idx], $path2[$idx]);
            return $most_cw == $path1[$idx] ? $path1 : $path2;
        }
    }

    function findShortestPathClockwise($floor, $start, $end) {
        $tiles = array_values($this->tiles->getCardsInLocation("floor$floor", null, 'location_arg'));
        $walls = $this->getWalls();

        $path = array($start);
        $avail = array($start=>$this->neighbors($tiles, $walls, $tiles[$start]));
        $paths = array();
        while (count($path) > 0) {
            $current = $path[count($path) - 1];
            $current_tile = $tiles[$current];
            $opts = $avail[$current];
            if ($current == $end) {
                $paths [] = $path;
                array_pop($path);
                if (count($path) > 0) {
                    $last_avail = &$avail[$path[count($path) - 1]];
                    unset($last_avail[$current]);
                }
            } else if(count($opts) == 0) {
                array_pop($path);
                if (count($path) > 0) {
                    $last_avail = &$avail[$path[count($path) - 1]];
                    unset($last_avail[$current]);
                }
            } else {
                $next = array_keys($opts)[0];
                if (!isset($avail[$next]) || count($avail[$next]) == 0) {
                    $avail[$next] = $this->neighbors($tiles, $walls, $tiles[$next], $path);
                }
                $path [] = $next;
            }
        }

        return array_map(function($idx) use ($tiles) {
            return $tiles[$idx]['id'];
        }, $this->breakTie($end, $paths));
    }

    function getGenericTokens() {
        $types = implode(array_map(function($type) {
            return $type['name'];
        }, $this->token_types), "','");
        $tokens = self::getCollectionFromDB("SELECT card_id id, card_type type, card_location location, card_location_arg location_arg FROM token WHERE card_location != 'deck' and card_type in ('$types')");
        foreach ($tokens as &$token) {
            $token['letter'] = strtoupper($token['type'][0]);
            // $token['color'] = $this->token_colors[$token['type']];
        }
        return $tokens;
    }

    function getPlacedTokens($types, $location='tile') {
        $types_arg = "('".implode($types,"','")."')";
        $rows = self::getObjectListFromDB("SELECT card_location_arg id, card_id token_id FROM token WHERE card_type in $types_arg AND card_location = '$location'");
        $result = array();
        foreach ($rows as $row) {
            if (!isset($result[$row['id']])) {
                $result[$row['id']] = array();
            }
            $result[$row['id']] [] = $row['token_id'];
        }
        return $result;
    }

    function notifyPlayerHand($player_id, $discard_ids=array()) {
        self::notifyAllPlayers('playerHand', '', array(
            'player_id' => $player_id,
            'hand' => $this->cards->getPlayerHand($player_id),
            'discard_ids' => $discard_ids
        ));
    }

    function notifyTileCards($tile_id) {
        self::notifyAllPlayers('tileCards', '', array(
            'tile_id' => $tile_id,
            'tokens' => $this->getCardTokens($tile_id)
        ));
    }

    function performSafeDiceRollDebug($floor,$dice_count) {
        $safe_tile = array_values($this->tiles->getCardsOfTypeInLocation('safe', null, "floor$floor"))[0];
        $this->performSafeDiceRoll($safe_tile,intval($dice_count));
    }

    function performSafeDiceRoll($safe_tile, $drop_loot=FALSE) {
        if ($safe_tile['type'] != 'safe') {
            throw new BgaUserException(self::_("Tile is not a safe"));
        }
        if ($this->tokensInTile('open', $safe_tile['id'])) {
            throw new BgaUserException(self::_("Safe is already open"));
        }

        $floor = $safe_tile['location'][5];
        $dice_count = self::getGameStateValue("safeDieCount$floor");
        $current_player_id = self::getCurrentPlayerId();
        if ($this->getPlayerCharacter($current_player_id, 'peterman1')) {
            $dice_count++;
        }
        if ($dice_count == 0) {
            throw new BgaUserException(self::_("You have not added any dice"));
        }

        $keycard = $this->getPlayerLoot('keycard');
        if ($keycard) {
            $keycard_holder = $this->getPlayerToken($keycard['location_arg']);
            if ($keycard_holder['location_arg'] != $safe_tile['id']) {
                throw new BgaUserException(self::_("The player holding the keycard must be present"));
            }
        }
        $rolls = $this->rollDice($dice_count);
        $this->notifyRoll($rolls, 'safe');

        // If player owns the stethoscope (and not the bust), can choose to reroll a die
        $stethoscope = $this->getPlayerTool('stethoscope', $current_player_id);
        $bust = $this->getPlayerLoot('bust', $current_player_id);
        if ($stethoscope && !$bust) {
            // Store die values to get them back on next state
            $sql_values = [];
            foreach ($rolls as $value => $nbr) {
                for ($i=1; $i <= $nbr; $i++) { 
                    $sql_values[] = "('die', $value, 'stethoscope', 0)";
                }
            }
            $sql = "INSERT INTO token (card_type, card_type_arg, card_location, card_location_arg) VALUES ".implode(', ', $sql_values);
            self::DbQuery($sql);
            self::setGameStateValue('cardChoice', $stethoscope['id']);
            $drop_loot = $drop_loot ? 1 : 0;
            self::setGameStateValue('dropLoot', $drop_loot);
            $this->gamestate->nextState('cardChoice');
            return true;
        } else {
            $this->applyDieRoll($rolls, $safe_tile, $drop_loot);
        }
    }

    function applyDieRoll($rolls=null, $safe_tile=null, $drop_loot=null) {
        $current_player_id = self::getCurrentPlayerId();
        if ($rolls === null) {
            $tokens = $this->tokens->getCardsInLocation('stethoscope');
            $rolls = array();
            foreach ($tokens as $id => $token) {
                $value = $token['type_arg'];
                $rolls[$value] = isset($rolls[$value]) ? $rolls[$value] + 1 : 1;
            }
            self::DbQuery("DELETE FROM token WHERE card_location='stethoscope'");
        }
        if ($safe_tile === null) {
            $player_token = $this->getPlayerToken($current_player_id);
            $safe_tile = $this->getPlayerTile($current_player_id, $player_token);
        }
        if ($drop_loot === null) {
            $drop_loot = self::getGameStateValue('dropLoot');
            self::setGameStateValue('dropLoot', 0);
        }
        $floor = $safe_tile['location'][5];
        $tiles = $this->getTiles($floor);
        $placed_tokens = $this->getPlacedTokens(array('safe'));
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
            $this->pickTokensForTile('open', $safe_tile['id']);
            if ($drop_loot) {
                $this->cards->pickCardForLocation('tools_deck', 'tile', $safe_tile['id']);
                $loot = $this->cards->pickCardForLocation('loot_deck', 'tile', $safe_tile['id']);
                $this->notifyTileCards($safe_tile['id']);
            } else {
                self::setGameStateValue('drawToolsPlayer', $current_player_id);
                $loot = $this->cards->pickCard('loot_deck', $current_player_id);
            }
            $type = $this->getCardType($loot);
            if ($type == 'cursed-goblet' && !$drop_loot) {
                $stealth = $this->getPlayerStealth($current_player_id);
                if ($stealth > 0) {
                    $this->decrementPlayerStealth($current_player_id);
                }
            } else if($type == 'gold-bar') {
                $gold_type = $this->getCardTypeForName(2, 'gold-bar');
                $other_gold = array_values($this->cards->getCardsOfTypeInLocation(2, $gold_type, 'loot_deck'))[0];
                $this->cards->moveCard($other_gold['id'], 'tile', $safe_tile['id']);
                $this->notifyTileCards($safe_tile['id']);
            }
            $this->notifyPlayerHand($current_player_id);
            
            $msg = '${player_name} '.clienttranslate("cracked the safe on floor $floor");
            self::notifyAllPlayers('message', $msg, [
                'player_name' => self::getCurrentPlayerName()
            ]);

            $safe_token = array_values($this->tokens->getCardsOfType('crack', $floor))[0];
            if ($safe_token['location'] == 'tile') {
                $this->moveToken($safe_token['id'], 'deck');
            }
            for ($lower_floor=$floor; $lower_floor >= 1; $lower_floor--) { 
                $die_count = self::getGameStateValue("patrolDieCount$lower_floor");
                if ($die_count < 6) {
                    $next_count = $die_count + 1;
                    self::setGameStateValue("patrolDieCount$lower_floor", $next_count);
                    self::notifyAllPlayers('message', clienttranslate("Guard on floor $lower_floor now moves $next_count spaces"), []);
                    self::notifyAllPlayers('patrolDieIncreased', '', array(
                        'die_num' => $die_count + 1,
                        'token' => array_values($this->tokens->getCardsOfType('patrol', $lower_floor))[0],
                        'floor' => $lower_floor
                    ));
                }
            }
        }
    }

    function getTokens($ids) {
        $tokens = $this->tokens->getCards($ids);
        foreach ($tokens as $token_id => &$token) {
            if (isset($this->token_types[$token['type']])) {
                $token['letter'] = strtoupper($token['type'][0]);
                if ($token['location'] == 'tile') {
                    $token['floor'] = $this->tiles->getCard($token['location_arg'])['location'][5];
                }
            }
        }
        return $tokens;
    }

    function getTokensOnFloor($type, $floor) {
        $sql = <<<SQL
            SELECT token.card_id as id
            FROM token
            INNER JOIN tile ON tile.card_id = token.card_location_arg
            WHERE token.card_location = 'tile' AND tile.card_location = 'floor$floor' AND token.card_type = '$type'
SQL;
        return self::getObjectListFromDB($sql, TRUE);
    }

    function moveTokens($ids, $location, $location_arg=0, $synchronous=FALSE) {
        $this->tokens->moveCards($ids, $location, $location_arg);
        $name = $synchronous ? 'tokensPickedSync' : 'tokensPicked';
        self::notifyAllPlayers($name, '', array(
            'tokens' => $this->getTokens($ids)
        ));
    }

    function moveToken($id, $location, $location_arg=0, $synchronous=FALSE) {
        $this->moveTokens(array($id), $location, $location_arg, $synchronous);
    }

    function pickTokens($type, $to_location='tile', $to_location_arg=null, $nbr = 1) {
        $token_ids = array_keys($this->tokens->getCardsOfTypeInLocation($type, null, 'deck'));
        $ids = array();
        for ($i=0; $i < $nbr; $i++) { 
            $ids [] = $token_ids[$i];
        }
        $this->moveTokens($ids, $to_location, $to_location_arg);
    }

    function pickTokensForTile($type, $tile_id, $nbr = 1) {
        $this->pickTokens($type, 'tile', $tile_id, $nbr);
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
        if ($this->getPlayerCharacter(self::getCurrentPlayerId(), 'peterman1')) {
            $count++;
        }
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
        return count($tokens);
    }

    function canHack($tile) {
        $hacker2 = $this->getPlacedTokens(array('hack'),'card');
        if (count($hacker2) > 0) {
            return TRUE;
        }

        $type = $tile['type'];
        $tokens = $this->getPlacedTokens(array('hack'));
        $tiles = $this->tiles->getCardsOfType("$type-computer");
        if (count($tokens) == 0) {
            return FALSE;
        }
        $computer_tile = array_values($tiles)[0];
        return isset($tokens[$computer_tile['id']]);
    }

    function getGemstonePenalty($player_id, $player_tile, $is_moved=FALSE) {
        $gemstone = $this->getPlayerLoot('gemstone', $player_id);
        // Token is already moved, so there was another player there
        $more_than = $is_moved ? 1 : 0;
        if ($gemstone && $this->tokensInTile('player', $player_tile['id']) > $more_than) {
            return 1;
        }
        return 0;
    }

    function canUseExtraAction($player_id, $player_tile) {
        $action_penalty = $this->getGemstonePenalty($player_id, $player_tile, TRUE);
        return $player_tile['type'] == 'laser' && self::getGameStateValue('actionsRemaining') >= (2 + $action_penalty);
    }

    function hackerDoesNotTrigger($tile) {
        if (!in_array($tile['type'], array('fingerprint', 'motion', 'laser'))) {
            return FALSE;
        }

        $type_arg = $this->getCardTypeForName(0, 'hacker1');
        $hackers = $this->cards->getCardsOfTypeInLocation(0, $type_arg, 'hand');
        if (count($hackers) > 0) {
            $hacker = array_values($hackers)[0];
            if ($hacker['location_arg'] == self::getCurrentPlayerId()) {
                return TRUE;
            }

            $hacker_token = $this->getPlayerToken($hacker['location_arg']);
            return $hacker_token['location_arg'] == $tile['id'];
        }
        return FALSE;
    }

    function hackOrTrigger($tile) {
        if ($this->tokensInTile('guard', $tile['id']) || $this->tokensInTile('alarm', $tile['id']) || $this->hackerDoesNotTrigger($tile) || self::getGameStateValue('empPlayer') != 0) {
            return FALSE;
        }

        if ($this->canHack($tile)) {
            return TRUE;
        } else {
            $this->triggerAlarm($tile, TRUE);
            return FALSE;
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

    function notifyMovement($player_id, $tile, $context='move') {
        $floor = $tile['location'][5];
        $action = 'moved to';
        $reason = null;
        if ($context == 'deadbolt') {
            $action = 'stayed in';
            $reason = 'didn\'t have enough actions to enter the Deadbolt';
        } else if ($context == 'keypad') {
            $action = 'stayed in';
            $reason = 'didn\'t roll a 6 to enter the Keypad';
        } else if ($context == 'walkway') {
            $action = 'fell to';
            $reason = 'revealed a Walkway';
        }
        $tile_name = $this->patrol_names[$tile['location_arg']]['name'];
        $msg = '${player_name} '.$action." tile $tile_name on floor $floor";
        if (!is_null($reason)) {
            $msg .= ' because they '.$reason;
        }
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers('message', clienttranslate($msg), [
            'player_name' => $players[$player_id]['player_name']
        ]);
    }

    function handleTileMovement($tile, $player_tile, $player_token, $guard_token, $flipped_this_turn, $context) {
        $id = $tile['id'];
        $type = $tile['type'];
        $actions_remaining = !in_array($context, array('action', 'acrobat2')) ? 1 : self::getGameStateValue('actionsRemaining');
        $cancel_move = false;
        $tile_choice = false;
        $tile_choice_id = $id;
        $player_id = $player_token['type_arg'];
        $crowbar = $this->tokensInTile('crowbar', $id);
        $rook1_action = $context == 'rook1';
        $floor = $tile['location'][5];

        $action_penalty = $this->getGemstonePenalty($player_id, $tile);
        if ($action_penalty > 0 && $actions_remaining < 2) {
            throw new BgaUserException(self::_('Entering a tile with another player costs an additional action with the Gemstone'));
        }

        if ($context == 'acrobat2') {
            // No FAQ for this, I'm interpreting it as spend 3 actions to move + additional for the tile
            $action_penalty += 2;
        }
        
        if ($type == 'deadbolt') {
            if (!$crowbar && !$rook1_action) {
                $people = $this->getPlacedTokens(array('player', 'guard'));
                if (!isset($people[$id]) || count($people[$id]) == 0) {
                    if ($actions_remaining < (3 + $action_penalty)) {
                        if ($flipped_this_turn) {
                            $cancel_move = true;
                            $this->notifyMovement($player_id, $player_tile, 'deadbolt');
                        } else {
                            throw new BgaUserException(self::_('You do not have enough actions to enter the Deadbolt'));
                        }
                    } else {
                        self::incGameStateValue('actionsRemaining', -2); // One is deducted already
                    }
                }
            }
        } elseif ($type == 'keypad') {
            if (!$crowbar) {
                $cancel_move = !$this->attemptKeypadRoll($tile);
                if ($cancel_move) {
                    $this->notifyMovement($player_id, $player_tile, 'keypad');
                }
            }
        } elseif ($type == 'fingerprint') {
            if (!$crowbar) {
                $this->setupGuardToken($guard_token, $floor);
                $tile_choice = $this->hackOrTrigger($tile);
            }
        } elseif ($type == 'laser') {
            if (!$crowbar && !$rook1_action && !$this->getPlayerLoot('mirror', $player_id) && !$this->hackerDoesNotTrigger($tile)) {
                $this->setupGuardToken($guard_token, $floor);
                $tile_choice = $actions_remaining >= (2 + $action_penalty) || $this->hackOrTrigger($tile);
            }
        } elseif($type == 'motion') {
            if (!$crowbar) {
                $this->setTileBit('motionTileEntered', $id);
            }
        } elseif($type == 'laboratory') {
            $prev_value = $this->setTileBit('laboratoryTileEntered', $id);
            if (!$prev_value) {
                self::setGameStateValue('drawToolsPlayer', $player_id);
            }
        } elseif($type == 'detector') {
            if (!$crowbar) {
                $hand = $this->cards->getPlayerHand($player_id);
                foreach ($hand as $card_id => $card) {
                    if ($card['type'] == 1 || $card['type'] == 2) {
                        $this->setupGuardToken($guard_token, $floor);
                        $this->triggerAlarm($tile);
                        break;
                    }
                }
            }
        } elseif ($type == 'walkway' && $flipped_this_turn) {
            // Fall down
            if ($floor > 1) {
                $lower_tile = $this->findTileOnFloor($floor - 1, $tile['location_arg']);
                $cancel_move = true;
                $this->performPeek($lower_tile['id'], 'effect');
                $this->moveToken($player_token['id'], 'tile', $lower_tile['id']);
                $this->notifyMovement($player_id, $lower_tile, 'walkway');
            }
        } elseif ($type == 'thermo' && $this->getPlayerLoot('isotope', $player_id)) {
            if (!$crowbar) {
                $this->setupGuardToken($guard_token, $floor);
                $this->triggerAlarm($tile);
            }
        }

        if (!$cancel_move) {
            // Guarantee this is set up if it wasn't already
            $this->setupGuardToken($guard_token, $floor);

            // Handle exit
            $exit_type = $player_tile['type'];
            if ($exit_type == 'motion' && !$rook1_action) {
                $exit_id = $player_tile['id'];
                $motion_bit = 1 << self::getUniqueValueFromDB("SELECT safe_die FROM tile WHERE card_id = '$exit_id'");
                $motion_entered = self::getGameStateValue('motionTileEntered');
                if ($motion_entered & $motion_bit) {
                    $exiting_choice = $this->hackOrTrigger($player_tile);
                    if ($tile_choice && $exiting_choice) {
                        self::setGameStateValue('motionTileExitChoice', $tile_choice_id);
                    }
                    if ($exiting_choice) {
                        $tile_choice = $exiting_choice;
                        $tile_choice_id = $player_tile['id'];
                    }
                }
            }
        
            $this->moveToken($player_token['id'], 'tile', $id);
        }
        if (!$tile_choice && $action_penalty) {
            self::incGameStateValue('actionsRemaining', -$action_penalty);
        }
        if (!$cancel_move) {
            $this->notifyMovement($player_id, $tile);
        }
        return array(
            'perform_move' => !$cancel_move,
            'tile_choice' => $tile_choice ? $tile_choice_id : FALSE
        );
    }

    function checkCameras($params) {
        $video_loop = $this->getActiveEvent('video-loop');
        if ($video_loop) {
            return;
        }
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
            if (!$this->tokensInTile('crowbar', $tile['id'])) {
                $this->triggerAlarm($tile);
            }
        }
    }

    function triggerAlarm($tile, $skip_token_checks=FALSE) {
        if (!$skip_token_checks) {
            if($this->tokensInTile('guard', $tile['id']) || $this->tokensInTile('alarm', $tile['id']) || $this->hackerDoesNotTrigger($tile) || self::getGameStateValue('empPlayer') != 0) {
                return;
            }
        }

        $floor = $tile['location'][5];
        $patrol = "patrol".$floor;
        $patrol_token = array_values($this->tokens->getCardsOfType('patrol', $floor))[0];
        $this->moveToken($patrol_token['id'], 'tile', $tile['id']);
        $this->pickTokensForTile('alarm', $tile['id']);
        self::notifyAllPlayers('message', clienttranslate( 'An alarm was triggered' ), array());
        self::incStat(1, 'alarm_triggered');
    }

    function handleToolEffectDebug($name) {
        $current_player_id = self::getCurrentPlayerId();
        $type_arg = $this->getCardTypeForName(1, $name);
        $card = array_values($this->cards->getCardsOfType(1, $type_arg))[0];
        $choice = $this->handleToolEffect($current_player_id, $card);
        if ($choice) {
            self::setGameStateValue('cardChoice', $card['id']);
            $this->gamestate->nextState('cardChoice');
        }
    }

    function handleToolEffect($player_id, $card) {
        $type = $this->getCardType($card);
        $choice = FALSE;
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
                $this->decrementPlayerStealth($token['type_arg'], -1); // Give them back one
            }
        } elseif($type == 'rollerskates') {
            self::incGameStateValue('actionsRemaining', 2);
        } elseif ($type == 'smoke-bomb') {
            $tile = $this->getPlayerTile($player_id);
            $this->pickTokensForTile('stealth', $tile['id'], 3);
        } elseif ($type == 'stethoscope') {
            throw new BgaUserException(self::_("You must roll dice before using the stethoscope"));
        } else {
            $choice = TRUE;
        }
        return $choice;
    }

    function getDeckTypeForName($name) {
        foreach ($this->card_types as $type_id => $value) {
            if ($value['name'] == $name) {
                return $type_id;
            }
        }
        return null;
    }

    function getCardTypeForName($type_id, $name) {
        $type_arg = null;
        foreach ($this->card_info[$type_id] as $index => $value) {
            if ($value['name'] == $name) {
                $type_arg = $index + 1;
            }
        }
        return $type_arg;
    }

    function drawToolDebug($name = null, $location='hand', $location_arg=null) {
        $current_player_id = self::getCurrentPlayerId();
        if (is_null($location_arg)) {
            $location_arg = $current_player_id;
        }
        
        if ($name != null) {
            $type_arg = $this->getCardTypeForName(1, $name);
            $card = array_values($this->cards->getCardsOfType(1, $type_arg))[0];
            $this->cards->moveCard($card['id'], $location, $location_arg);
            if ($location == 'hand') {
                $this->notifyPlayerHand($current_player_id);
            } else if ($location == 'tile') {
                $this->notifyTileCards($location_arg);
            }
        } else {
            self::setGameStateValue('drawToolsPlayer', $current_player_id);
            $this->gamestate->nextState('endAction');
        }
        
    }

    function drawLootDebug($name) {
        $current_player_id = self::getCurrentPlayerId();
        $type_arg = $this->getCardTypeForName(2, $name);
        $card = array_values($this->cards->getCardsOfType(2, $type_arg))[0];
        $this->cards->moveCard($card['id'], 'hand', $current_player_id);
        $this->notifyPlayerHand($current_player_id);
    }

    function discardLootDebug($name) {
        $current_player_id = self::getCurrentPlayerId();
        $type_arg = $this->getCardTypeForName(2, $name);
        $card = array_values($this->cards->getCardsOfType(2, $type_arg))[0];
        $this->cards->moveCard($card['id'], 'loot_deck');
        $this->notifyPlayerHand($current_player_id, array($card['id']));
    }

    function drawCharacterDebug($name) {
        $current_player_id = self::getCurrentPlayerId();
        $current_char = $this->getPlayerCharacter($current_player_id);
        $this->cards->moveCard($current_char['id'], 'characters_deck');

        $type_arg = $this->getCardTypeForName(0, $name);
        $card = array_values($this->cards->getCardsOfType(0, $type_arg))[0];
        $this->cards->moveCard($card['id'], 'hand', $current_player_id);
        $this->notifyPlayerHand($current_player_id, array($current_char['id']));
    }

    function handleEventEffectDebug($name) {
        $current_player_id = self::getCurrentPlayerId();
        $type_arg = $this->getCardTypeForName(3, $name);
        $card = array_values($this->cards->getCardsOfType(3, $type_arg))[0];
        $event_result = $this->handleEventEffect($current_player_id, $card);
        if ($event_result['card_choice']) {
            self::setGameStateValue('cardChoice', $card['id']);
            $this->gamestate->nextState('cardChoice');
        } elseif ($event_result['tile_choice']) {
            $this->gamestate->nextState('tileChoice');
        }
    }

    function handleEventEffect($player_id, $card) {
        $type = $this->getCardType($card);
        $card_choice = FALSE;
        $tile_choice = FALSE;
        $player_choice = FALSE;
        if ($type == 'brown-out') {
            for ($floor=1; $floor <= 3; $floor++) { 
                $token_ids = $this->getTokensOnFloor('alarm', $floor);
                $this->moveTokens($token_ids, 'deck');
                foreach ($token_ids as $id) {
                    $this->nextPatrol($floor);
                }
            }
        } elseif($type == 'buddy-system') {
            if (self::getPlayersNumber() > 1) {
                $card_choice = TRUE;
            }
        } elseif ($type == 'change-of-plans') {
            $tile = $this->getPlayerTile($player_id);
            $floor = $tile['location'][5];
            $this->nextPatrol($floor, TRUE);
        } elseif ($type == 'crash') {
            $tile = $this->getPlayerTile($player_id);
            $floor = $tile['location'][5];
            $patrol_token = array_values($this->tokens->getCardsOfType('patrol', $floor))[0];
            $this->moveToken($patrol_token['id'], 'tile', $tile['id']);
        } elseif($type == 'dead-drop') {
            $prev_player_id = self::getPlayerBefore($player_id);
            $cards = $this->cards->getCardsOfTypeInLocation(1, null, 'hand', $player_id) +
                $this->cards->getCardsOfTypeInLocation(2, null, 'hand', $player_id);
            $this->cards->moveCards(array_keys($cards), 'hand', $prev_player_id);
            $this->notifyPlayerHand($player_id, array_keys($cards));
            $this->notifyPlayerHand($prev_player_id);
        } elseif ($type == 'freight-elevator') {
            $player_token = $this->getPlayerToken($player_id);
            $tile = $this->getPlayerTile($player_id, $player_token);
            $floor = $tile['location'][5];
            if ($floor < 3) {
                $upper_tile = $this->findTileOnFloor($floor + 1, $tile['location_arg']);
                $this->performPeek($upper_tile['id'], 'effect');
                $this->moveToken($player_token['id'], 'tile', $upper_tile['id']);
                $guard_token = array_values($this->tokens->getCardsOfType('guard', $floor + 1))[0];
                if ($guard_token['location'] == 'deck') {
                    $this->setupPatrol($guard_token, $floor + 1);
                    $this->nextPatrol($floor + 1);
                }
            }
        } elseif($type == 'go-with-your-gut') {
            $player_tile = $this->getPlayerTile($player_id);
            $peekable = $this->getPeekableTiles($player_tile);
            if (count($peekable) > 1) {
                $card_choice = TRUE;
            } elseif(count($peekable) == 1) {
                $tile_choice = $this->performMove($peekable[0]['id'], 'event');
            } 
        } elseif($type == 'heads-up') {
            $next_player = $this->getPlayerAfter($player_id);
            $this->cards->moveCard($card['id'], 'hand', $next_player);
            $this->notifyPlayerHand($next_player);
        } elseif ($type == 'jury-rig') {
            self::setGameStateValue('drawToolsPlayer', $player_id);
        } elseif ($type == 'keycode-change') {
            $safes = $this->tiles->getCardsOfType('keypad');
            foreach ($safes as $tile_id => $safe) {
                $this->clearTileTokens('open', $tile_id);    
            }
        } elseif ($type == 'lampshade') {
            $player_token = $this->getPlayerToken($player_id);
            $this->decrementPlayerStealth($player_id, -1); // Give them back one
        } elseif($type == 'lost-grip') {
            $player_token = $this->getPlayerToken($player_id);
            $tile = $this->getPlayerTile($player_id, $player_token);
            $floor = $tile['location'][5];
            if ($floor > 1) {
                $lower_tile = $this->findTileOnFloor($floor - 1, $tile['location_arg']);
                $this->performPeek($lower_tile['id'], 'effect');
                $this->moveToken($player_token['id'], 'tile', $lower_tile['id']);
            }
        } elseif($type == 'peekhole') {
            $player_tile = $this->getPlayerTile($player_id);
            $peekable = $this->getPeekableTiles($player_tile, 'peekhole');
            if (count($peekable) > 1) {
                $card_choice = TRUE;
            } elseif(count($peekable) == 1) {
                $this->performPeek($peekable[0]['id'], 'peekhole');
            } 
        } elseif ($type == 'reboot') {
            $types = array('fingerprint-computer', 'motion-computer', 'laser-computer');
            for ($floor=1; $floor <= 3; $floor++) { 
                $tiles = $this->getTiles($floor);
                foreach ($tiles as $tile_id => $tile) {
                    if (in_array($tile['type'], $types)) {
                        $hack_tokens = $this->tokens->getCardsOfTypeInLocation('hack', null, 'tile', $tile['id']);
                        if (count($hack_tokens) == 0) {
                            $this->pickTokensForTile('hack', $tile['id']);
                        } else {
                            $count = count($hack_tokens);
                            while ($count > 1) {
                                $token = array_shift($hack_tokens);
                                $this->moveToken($token['id'], 'deck');
                                $count--;
                            }
                        }
                    }
                }
            }
        } elseif($type == 'shoplifting') {
            $laboratories = self::getCollectionFromDB("SELECT card_id id, card_type type, card_type_arg type_arg, card_location location, card_location_arg location_arg, safe_die FROM tile WHERE card_type = 'laboratory'");
            $tile_entered = self::getGameStateValue('laboratoryTileEntered');
            foreach ($laboratories as $tile_id => $tile) {
                $tile_bit = 1 << $tile['safe_die'];
                if (($tile_entered & $tile_bit) != 0x0) {
                    $this->triggerAlarm($tile);
                }
            }
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
            
            $this->performGuardMovementEffects($guard_token, $patrol_token['location_arg']);
            $this->nextPatrol($floor);
        } elseif ($type == 'squeak') {
            // Guard moves 1 tile towards the closest player
            $tile = $this->getPlayerTile($player_id);
            $floor = $tile['location'][5];
            $guard_token = array_values($this->tokens->getCardsOfType('guard', $floor))[0];
            $guard_tile = $this->tiles->getCard($guard_token['location_arg']);
            $paths = [];
            $shortest_path_length = 16;

            $players = self::loadPlayersBasicInfos();
            foreach ($players as $player_id => $player) {
                $player_token = $this->getPlayerToken($player_id);
                $player_tile = $this->getPlayerTile($player_id, $player_token);
                $path = $this->findShortestPathClockwise($floor, $guard_tile['location_arg'], $player_tile['location_arg']);
                $paths[] = $path;
                $shortest_path_length = min($shortest_path_length, count($path));
            }
            // Keep only the shortest paths
            $paths = array_filter($paths, function($path) use($shortest_path_length) { 
                return count($path) == $shortest_path_length;
            });
            if (count($paths) == 1) {
                $tile_id = $paths[1][1];
                $this->performGuardMovementEffects($guard_token, $tile_id);
                $patrol_token = array_values($this->tokens->getCardsOfType('patrol', $floor))[0];
                if ($tile_id == $patrol_token['location_arg'])
                    $this->nextPatrol($floor);
            } else {
                $player_choice = 4;
                self::setGameStateValue('playerChoiceArg', $shortest_path_length);
            }
        } else {
            // It will be handled in the appropriate place
            $this->cards->moveCard($card['id'], 'hand', $player_id);
            $this->notifyPlayerHand($player_id);
        }
        return array(
            'card_choice' => $card_choice,
            'tile_choice' => $tile_choice,
            'player_choice' => $player_choice
        );
    }

    function getActiveEvent($name) {
        $type_arg = $this->getCardTypeForName(3, $name);
        $cards = $this->cards->getCardsOfTypeInLocation(3, $type_arg, 'hand');
        if (count($cards) > 0) {
            return array_values($cards)[0];
        }
        return null;
    }

    function getPlayerLoot($name, $player_id=null) {
        $type_arg = $this->getCardTypeForName(2, $name);
        $cards = $this->cards->getCardsOfTypeInLocation(2, $type_arg, 'hand', $player_id);
        if (count($cards) > 0) {
            return array_values($cards)[0];
        }
        return null;
    }

    function getPlayerTool($name, $player_id=null) {
        $type_arg = $this->getCardTypeForName(1, $name);
        $cards = $this->cards->getCardsOfTypeInLocation(1, $type_arg, 'hand', $player_id);
        if (count($cards) > 0) {
            return array_values($cards)[0];
        }
        return null;
    }

    function getPlayerCharacter($player_id, $name=null) {
        $type_arg = null;
        if($name != null) {
            $type_arg = $this->getCardTypeForName(0, $name);
        }
        $cards = $this->cards->getCardsOfTypeInLocation(0, $type_arg, 'hand', $player_id);
        return $cards ? array_values($cards)[0] : null;
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

    function validateSelection($expected_type, $selected_type) {
        if ($expected_type != $selected_type) {
            if ($expected_type == 'button') {
                throw new BgaUserException(self::_("Finish first the action you started (use buttons in the status bar)"));
            } else {
                throw new BgaUserException(self::_("Invalid selection. Expected: $expected_type."));
            }
        }
    }

    function handleSelectCardChoice($card, $selected_type, $selected_ids) {
        $selected_id = count($selected_ids) == 1 ? $selected_ids[0] : $selected_ids;
        $type = $this->getCardType($card);
        $current_player_id = self::getCurrentPlayerId();
        if ($card['type'] == 0) {
            self::incStat(1, 'special_ability_use', $current_player_id);
        } elseif ($card['type'] == 1) {
            self::incStat(1, 'tools_used', $current_player_id);
        }
        $tile_choice = FALSE;
        $discard = TRUE;
        if($type == 'acrobat1') {
            $this->validateSelection('tile', $selected_type);
            // Don't do tile_choice here, since we'll never trigger an alarm
            $this->performMove($selected_id, 'acrobat1');
        } else if($type == 'acrobat2') {
            $this->validateSelection('button', $selected_type);
            $tile = $this->getPlayerTile(self::getCurrentPlayerId());
            $floor = $selected_id;
            $other_tile = $this->findTileOnFloor($floor, $tile['location_arg']);
            $tile_choice = $this->performMove($other_tile['id'], 'acrobat2');
        } else if ($type == 'blueprints') {
            $this->validateSelection('tile', $selected_type);
            $tile = $this->tiles->getCard($selected_id);
            $flipped = $this->getFlippedTiles($tile['location'][5]);
            if (isset($flipped[$selected_id])) {
                throw new BgaUserException(self::_('Tile is already visible'));
            }
            $this->performPeek($tile['id'], 'effect');
        } elseif($type == 'buddy-system') {
            $this->validateSelection('meeple', $selected_type);
            $other_token = $this->tokens->getCard($selected_id);
            if ($other_token['type'] != 'player') {
                throw new BgaUserException(self::_("Must choose a player token"));
            }
            $player_token = $this->getPlayerToken(self::getCurrentPlayerId());
            $this->moveToken($other_token['id'], 'tile', $player_token['location_arg']);
        } elseif ($type == 'crowbar') {
            $this->validateSelection('tile', $selected_type);
            $tile = $this->tiles->getCard($selected_id);
            $player_tile = $this->getPlayerTile(self::getCurrentPlayerId());
            if (!$this->isTileAdjacent($tile, $player_tile, null, 'guard')) {
                throw new BgaUserException(self::_('Tile is not adjacent'));
            }
            $this->pickTokensForTile('crowbar', $tile['id']);
        } elseif ($type == 'donuts') {
            $this->validateSelection('tile', $selected_type);
            $tile = $this->tiles->getCard($selected_id);
            $guard_token = array_values($this->tokens->getCardsOfTypeInLocation('guard', null, 'tile', $tile['id']));
            if (count($guard_token) == 0) {
                throw new BgaUserException(self::_('Tile does not contain a guard'));
            }
            $this->cards->moveCard($card['id'], 'tile', $tile['id']);
            $this->notifyTileCards($tile['id']);
            $discard = FALSE;
        } elseif($type == 'dynamite') {
            $this->validateSelection('wall', $selected_type);
            $player_tile = $this->getPlayerTile(self::getCurrentPlayerId());
            $walls = $this->getWalls();

            $tindex = $player_tile['location_arg'];
            $trow = floor($tindex / 4);
            $tcol = $tindex % 4;

            $wall = self::getObjectFromDB("SELECT * FROM wall WHERE id = '$selected_id'");
            $exit = FALSE;
            for ($prow=$trow - 1; !$exit && $prow <= $trow + 1; $prow++) { 
                for ($pcol=$tcol - 1; !$exit && $pcol <= $tcol + 1; $pcol++) { 
                    if ($prow >= 0 && $pcol >= 0 && $prow <= 3 && $pcol <= 3 &&
                            ($prow != $trow || $pcol != $tcol)) {
                        $wrow = $wall['vertical'] == 1 ? floor($wall['position'] / 3) : $wall['position'] % 3;
                        $wcol = $wall['vertical'] == 0 ? floor($wall['position'] / 3) : $wall['position'] % 3;
                        $vertical = ($trow == $prow && $trow == $wrow && abs($tcol - $pcol) == 1) && min($tcol, $pcol) == $wcol;
                        $horizontal = ($tcol == $pcol && $tcol == $wcol && abs($trow - $prow) == 1) && min($trow, $prow) == $wrow;
                        if (($wall['vertical'] == 1 && $vertical) || ($wall['vertical'] == 0 && $horizontal)) {
                            self::DbQuery("DELETE FROM wall WHERE id = '$selected_id'");
                            $this->triggerAlarm($player_tile);
                            // Notify players to remove wall
                            self::notifyAllPlayers('removeWall', '', array(
                                'wall_id' => $selected_id,
                            ));
                            $exit = TRUE;
                        }
                    }
                }
            }
            if (!$exit) {
                throw new BgaUserException(self::_('Wall is not adjacent'));
            }
        } elseif($type == 'go-with-your-gut') {
            $this->validateSelection('tile', $selected_type);
            $tile = $this->tiles->getCard($selected_id);
            $flipped = $this->getFlippedTiles($tile['location'][5]);
            if (isset($flipped[$tile['id']])) {
                throw new BgaUserException(self::_('Tile is already visible'));
            }
            $tile_choice = $this->performMove($selected_id, 'event');
        } elseif($type == 'hawk1') {
            $this->validateSelection('tile', $selected_type);
            $this->performPeek($selected_id, 'hawk1');
        } elseif($type == 'hawk2') {
            $this->validateSelection('tile', $selected_type);
            $player_tile = $this->getPlayerTile(self::getCurrentPlayerId());
            if ($this->hawk2PeekAllowed($player_tile, $this->tiles->getCard($selected_id))) {
                $this->performPeek($selected_id, 'effect');
            }
        } elseif($type == 'juicer1') {
            $this->validateSelection('tile', $selected_type);
            $tile = $this->tiles->getCard($selected_id);
            $flipped = $this->getFlippedTiles($tile['location'][5]);
            if (!isset($flipped[$tile['id']])) {
                throw new BgaUserException(self::_('Cannot set alarm in hidden tile'));
            }
            $player_tile = $this->getPlayerTile(self::getCurrentPlayerId());
            if (!$this->isTileAdjacent($tile, $player_tile, null, 'guard')) {
                throw new BgaUserException(self::_('Tile is not adjacent'));
            }
            $this->triggerAlarm($tile);
        } elseif($type == 'peekhole') {
            $this->validateSelection('tile', $selected_type);
            $this->performPeek($selected_id, 'peekhole');
        } elseif($type == 'peterman2') {
            $this->validateSelection('button', $selected_type);
            $tile = $this->getPlayerTile(self::getCurrentPlayerId());
            $floor = $selected_id % 10;
            $other_tile = $this->findTileOnFloor($floor, $tile['location_arg']);
            $add_or_roll = floor($selected_id / 10);
            if ($add_or_roll == 0) {
                $this->performAddSafeDie($other_tile);
            } else {
                $this->performSafeDiceRoll($other_tile, TRUE);
            }
        } elseif($type == 'raven1') {
            $this->validateSelection('tile', $selected_type);
            $player_tile = $this->getPlayerTile(self::getCurrentPlayerId());
            $tile = $this->tiles->getCard($selected_id);
            if ($player_tile['location']['5'] != $tile['location'][5]) {
                throw new BgaUserException(self::_('Crow must be placed on your floor'));
            }
            $path = $this->findShortestPathClockwise($player_tile['location']['5'], $player_tile['location_arg'], $tile['location_arg']);
            if (count($path) <= 3) { // Includes starting tile
                $crow = array_values($this->tokens->getCardsOfType('crow'))[0];
                $this->moveToken($crow['id'], 'tile', $selected_id);
            } else {
                throw new BgaUserException(self::_('Crow can be placed up to two tiles away'));
            }
        } elseif($type == 'spotter1') {
            $this->validateSelection('button', $selected_type);
            if ($selected_id == 2) {
                $player_tile = $this->getPlayerTile(self::getCurrentPlayerId());
                $floor = $player_tile['location'][5];
                $deck = "patrol$floor".'_deck';
                $top_patrol = $this->cards->getCardOnTop($deck);
                $this->cards->insertCardOnExtremePosition($top_patrol['id'], $deck, FALSE);
            }
        } elseif($type == 'spotter2') {
            $this->validateSelection('button', $selected_type);
            if ($selected_id == 2) {
                $top_event = $this->cards->getCardOnTop('events_deck');
                $this->cards->insertCardOnExtremePosition($top_event['id'], 'events_deck', FALSE);
            }
        } elseif($type == 'thermal-bomb') {
            $this->validateSelection('button', $selected_type);
            $tile = $this->getPlayerTile(self::getCurrentPlayerId());
            $this->pickTokensForTile('thermal', $tile['id']);
            $other_tile = $this->findTileOnFloor($selected_id, $tile['location_arg']);
            $this->pickTokensForTile('thermal', $other_tile['id']);
            $this->triggerAlarm($tile);
        } elseif ($type == 'virus') {
            $this->validateSelection('tile', $selected_type);
            $tile = $this->tiles->getCard($selected_id);
            if (strpos($tile['type'], 'computer') === FALSE) {
                throw new BgaUserException(self::_("Tile is not a computer"));
            }
            $existing = $this->tokensInTile('hack', $tile['id']);
            $nbr = $existing <= 3 ? 3 : 6 - $existing;
            $this->pickTokensForTile('hack', $tile['id'], $nbr);
        } elseif ($type == 'crystal-ball') {
            $card_names_displayed = [];
            foreach ($selected_id as $card_id) {
                $this->cards->insertCardOnExtremePosition($card_id, 'events_deck', true);
                $card = $this->cards->getCard($card_id);
                $card_names_displayed[] = $this->getDisplayedCardName($this->getCardType($card));
            }
            self::notifyAllPlayers('message', clienttranslate('Crystal Ball: ${player_name} changed order of upcoming events to ${card_names_displayed}'), [
                'player_name' => self::getCurrentPlayerName(),
                'card_names_displayed' => implode(', ', $card_names_displayed),
            ]);
        } elseif ($type == 'stethoscope') {
            [$old_value, $new_value] = $selected_id;
            self::DbQuery("UPDATE token SET card_type_arg=$new_value WHERE card_type='die' AND card_type_arg=$old_value LIMIT 1");
            self::notifyAllPlayers('message', clienttranslate('Stethoscope: ${player_name} changed one die from ${old_value} to ${new_value}'), [
                'player_name' => self::getCurrentPlayerName(),
                'old_value' => $old_value,
                'new_value' => $new_value
            ]);
            $this->applyDieRoll();
        }
        if ($card['type'] != 0) {
            if ($discard) {
                $this->cards->moveCard($card['id'], $card['type'] == 1 ? 'tools_discard' : 'events_discard');
            }
            if ($card['type'] == 1) {
                $this->notifyPlayerHand(self::getCurrentPlayerId(), array($card['id']));
            }
        }
        return $tile_choice;
    }

    function handleSelectTileChoice($selected) {
        $player_id = self::getCurrentPlayerId();
        $tile = $this->tiles->getCard(self::getGameStateValue('tileChoice'));
        $type = $tile['type'];
        if ($selected == 0) { // trigger
            $this->triggerAlarm($tile);
        } elseif($selected == 1) { // hack
            if (!$this->canHack($tile)) {
                throw new BgaUserException(self::_('Cannot hack this tile'));
            }
            $tokens = $this->getPlacedTokens(array('hack'));
            $computer_tile = array_values($this->tiles->getCardsOfType("$type-computer"))[0];
            if (isset($tokens[$computer_tile['id']])) {
                // Use computer first
                $to_move = $tokens[$computer_tile['id']][0];
            } else {
                // Otherwise use hacker2's token
                $to_move = array_values($this->getPlacedTokens(array('hack'), 'card'))[0][0];
            }
            $this->moveToken($to_move, 'deck');
            self::notifyAllPlayers('message', clienttranslate('${player_name} used a hack token'), [
                'player_name' => self::getCurrentPlayerName()
            ]);
            
        } elseif($selected == 2) { // Extra action
            if (!$this->canUseExtraAction($player_id, $tile)) {
                throw new BgaUserException(self::_('Cannot use an extra action to enter this tile'));
            }
            $gemstone_penalty = $this->getGemstonePenalty($player_id, $tile, TRUE);
            // Take an extra 1 (or 2 for gemstone). Another 1 is always taken
            self::incGameStateValue('actionsRemaining', -1 - $gemstone_penalty);
            self::notifyAllPlayers('message', clienttranslate('${player_name} used an extra action'), [
                'player_name' => self::getCurrentPlayerName()
            ]);
        }
        return $tile;
    }

    function performPeek($tile_id, $variant='peek') {
        $current_player_id = self::getCurrentPlayerId();
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        $to_peek = $this->tiles->getCard($tile_id);
        $floor = $to_peek['location'][5];
        $flipped = $this->getFlippedTiles($floor);

        if (isset($flipped[$to_peek['id']])) {
            if ($variant == 'effect') {
                // Tile is already flipped, do nothing
                return;
            } else {
                throw new BgaUserException(self::_("Tile is already visible"));
            }
        }
        $walls = $this->getWalls();
        if ($variant != 'effect' && !$this->isTileAdjacent($to_peek, $player_tile, $walls, $variant)) {
            throw new BgaUserException(self::_("Tile is not adjacent"));
        }

        $this->handleTilePeek($to_peek);
        $tile_name = $this->patrol_names[$to_peek['location_arg']]['name'];
        // TODO: Add tile type?
        $msg = '${player_name} '."peeked tile $tile_name on floor $floor";
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers('message', clienttranslate($msg), [
            'player_name' => $players[$current_player_id]['player_name']
        ]);
        $this->flipTile( $floor, $to_peek['location_arg'] );
    }

    function setupGuardToken($guard_token, $floor) {
        if ($guard_token['location'] == 'deck') {
            $this->setupPatrol($guard_token, $floor);
            $this->nextPatrol($floor, TRUE);
        }
    }

    function performMove($tile_id, $context='action', $player_id = null) {
        $current_player_id = $player_id != null ? $player_id : self::getCurrentPlayerId();
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        $to_move = $this->tiles->getCard($tile_id);
        $floor = $to_move['location'][5];
        $flipped = $this->getFlippedTiles($floor);
        $guard_token = array_values($this->tokens->getCardsOfType('guard', $floor))[0];
        $acrobat_entered = 0;

        if (!$this->isTileAdjacent($to_move, $player_tile, null, $context)) {
            throw new BgaUserException(self::_("Tile is not adjacent"));
        }

        if($context == 'acrobat1') {
            if ($guard_token['location'] != 'tile' || $guard_token['location_arg'] != $tile_id) {
                throw new BgaUserException(self::_("Tile does not contain a guard"));
            }
            $acrobat_entered = 1;
        }

        $flipped_this_turn = !isset($flipped[$to_move['id']]);
        if ($flipped_this_turn) {
            $this->handleTilePeek($to_move);
        }
        $move_result = $this->handleTileMovement($to_move, $player_tile, $player_token, $guard_token, $flipped_this_turn, $context);
        $this->flipTile( $floor, $to_move['location_arg'] );
        $invisible_suit = self::getGameStateValue('invisibleSuitActive') == 1;
        if ($move_result['perform_move']) {
            self::setGameStateValue('acrobatEnteredGuardTile', $acrobat_entered);
            if (!$invisible_suit && !$acrobat_entered) {
                $this->handlePlayerEnteredGuardSight($to_move);
            }
        }
        if (!$invisible_suit) {
            $this->checkCameras(array('player_id'=>$player_token['id']));
        }
        return $move_result['tile_choice'];
    }

    function allPlayersEscaped() {
        return count($this->tokens->getCardsOfTypeInLocation('player', null, 'roof')) == self::getPlayersNumber();
    }

    function checkWin() {
        $all_safes_opened = $this->openSafes() == 3;
        $all_loot_escaped = count($this->cards->getCardsOfTypeInLocation(2, null, 'tile')) == 0 &&
            count($this->tokens->getCardsOfTypeInLocation('cat', null, 'tile')) == 0;
        return $all_safes_opened && $all_loot_escaped;
    }

    function performAddSafeDie($tile) {
        if ($tile['type'] != 'safe') {
            throw new BgaUserException(self::_("Tile is not a safe"));
        }
        if ($this->tokensInTile('open', $tile['id'])) {
            throw new BgaUserException(self::_("Safe is already open"));
        }
        $floor = $tile['location'][5];
        $safe_token = array_values($this->tokens->getCardsOfType('crack', $floor))[0];
        if ($safe_token['location'] != 'tile') {
            $this->moveToken($safe_token['id'], 'tile', $tile['id']);
            $safe_token = array_values($this->tokens->getCardsOfType('crack', $floor))[0];
        }
        $die_num = self::incGameStateValue("safeDieCount$floor", 1);
        self::notifyAllPlayers('safeDieIncreased', '', array(
            'die_num' => $die_num,
            'token' => $safe_token,
            'floor' => $floor
        ));
        
        $msg = clienttranslate('${player_name} '."added a die to the safe on floor $floor");
        self::notifyAllPlayers('message', $msg, [
            'player_name' => self::getCurrentPlayerName()
        ]);
    }

    function createTrade($player1, $player2) {
        self::DbQuery("INSERT INTO trade(current_player, other_player, deleted) VALUES ($player1, $player2, 0)");
        return self::DbGetLastId();
    }

    function createTradeCards($trade_id, $player_id, $card_ids) {
        if (count($card_ids) > 0) {
            $sql = 'INSERT INTO trade_cards (trade_id, player_id, card_id) VALUES ';
            $values = array();
            foreach ($card_ids as $card_id) {
                $values [] = "($trade_id,$player_id,$card_id)";
            }
            $sql .= implode($values, ',');
            self::DbQuery($sql);
        }
    }

    function getTrade() {
        return self::getObjectFromDB("SELECT * FROM trade WHERE deleted = 0");
    }

    function getTradeCards($trade_id, $player_id) {
        $sql = <<<SQL
            SELECT card.card_id id, card.card_type type, card.card_type_arg type_arg, card.card_location location, card.card_location_arg location_arg
            FROM trade_cards
            INNER JOIN card ON card.card_id = trade_cards.card_id
            WHERE trade_cards.trade_id = $trade_id AND trade_cards.player_id = $player_id
SQL;
        return self::getCollectionFromDB($sql);
    }

    function deleteTrade() {
        self::DbQuery("UPDATE trade SET deleted = 1 WHERE deleted = 0");
    }

    function parseIdList($id_arg) {
        // Removing last ';' if exists
        if( substr( $id_arg, -1 ) == ';' )
            $id_arg = substr( $id_arg, 0, -1 );
        if( $id_arg == '' )
            $ids = array();
        else
            $ids = explode( ';', $id_arg );
        return $ids;
    }

    function handleSelectPlayerChoice($current_player_id, $type, $selected) {
        if ($type == 'trade') {
            $current_player_token = $this->getPlayerToken($current_player_id);
            $player_tokens = $this->tokens->getCardsOfTypeInLocation('player', null, 'tile', $current_player_token['location_arg']);
            if (!isset($player_tokens[$selected])) {
                throw new BgaUserException(self::_('Selected player is not in your tile'));
            }
            if ($player_tokens[$selected]['type_arg'] == $current_player_id) {
                throw new BgaUserException(self::_('You cannot trade with yourself'));
            }
            $this->createTrade($current_player_id, $player_tokens[$selected]['type_arg']);
            $this->gamestate->nextState('proposeTrade');
        } else if ($type == 'rook1') {
            $meeple = $this->tokens->getCard($selected);
            if ($meeple['type'] != 'player') {
                throw new BgaUserException(self::_('Must choose a player token'));
            }
            if ($meeple['type_arg'] == $current_player_id) {
                throw new BgaUserException(self::_('You cannot choose yourself'));
            }
            self::setGameStateValue('specialChoice', 1); // Rook 1
            self::setGameStateValue('specialChoiceArg', $meeple['type_arg']); // Rook 1
            $this->gamestate->nextState('specialChoice');
        } else if ($type == 'rook2') {
            $meeple = $this->tokens->getCard($selected);
            if ($meeple['type'] != 'player') {
                throw new BgaUserException(self::_('Must choose a player token'));
            }
            if ($meeple['type_arg'] == $current_player_id) {
                throw new BgaUserException(self::_('You cannot choose yourself'));
            }
            $player_token = $this->getPlayerToken($current_player_id);
            $tmp_location = $meeple['location_arg'];
            $this->moveToken($meeple['id'], 'tile', $player_token['location_arg']);
            $this->moveToken($player_token['id'], 'tile', $tmp_location);
            $this->endAction();
        } else if ($type == 'squeak') {
            $meeple = $this->tokens->getCard($selected);
            if ($meeple['type'] != 'player') {
                throw new BgaUserException(self::_('Must choose a player token'));
            }
            $tile = $this->getPlayerTile($current_player_id);
            $floor = $tile['location'][5];
            $selected_player_id = $meeple['type_arg'];
            $selected_player_tile = $this->getPlayerTile($selected_player_id);
            if (count($this->tokens->getCardsOfTypeInLocation('player', null, 'tile', $selected_player_tile['id'])) == 0)
                throw new BgaUserException(self::_("You must choose a tile with a player"));
            if ($selected_player_tile['location'] != "floor$floor")
                throw new BgaUserException(self::_("You must choose a player tile on the same floor"));
            $guard_token = array_values($this->tokens->getCardsOfType('guard', $floor))[0];
            $guard_tile = $this->tiles->getCard($guard_token['location_arg']);
            $path = $this->findShortestPathClockwise($floor, $guard_tile['location_arg'], $selected_player_tile['location_arg']);
            if (count($path) > self::getGameStateValue("playerChoiceArg"))
                throw new BgaUserException(self::_("You must choose one of the closest players"));
            $this->performGuardMovementEffects($guard_token, $path[1]);
            $patrol_token = array_values($this->tokens->getCardsOfType('patrol', $floor))[0];
            if ($path[1] == $patrol_token['location_arg'])
                $this->nextPatrol($floor);
            $this->endAction();
        }
    }

    function handleSelectSpecialChoice($type, $choice_arg, $selected) {
        if ($type == 'rook1') {
            $player_token = $this->getPlayerToken($choice_arg);
            $tile_choice = $this->performMove($selected, 'rook1', $choice_arg);
            if (self::getGameStateValue('stealthDepleted')) {
                $this->gamestate->nextState('gameOver');
            } else if ($tile_choice) {
                self::setGameStateValue('tileChoice', $tile_choice);
                $this->gamestate->nextState('tileChoice');
            } else {
                $this->endAction();
            }
            self::setGameStateValue('characterAbilityUsed', 1);
        } else if ($type == 'rigger') {
            $tool = $this->cards->getCard($choice_arg);
        }
    }

    function moveCatToken($player_id) {
        $player_tile = $this->getPlayerTile($player_id);
        $floor = $player_tile['location'][5];
        $tiles = $this->getTiles($floor);
        $shortest_path = null;
        foreach ($tiles as $tile_id => $tile) {
            if (in_array($tile['type'], array('camera', 'detector', 'fingerprint', 'laser', 'motion', 'thermo'))) {
                $path = $this->findShortestPathClockwise($floor, $player_tile['location_arg'], $tile['location_arg']);
                if (count($path) > 1 && ($shortest_path == null || count($shortest_path) > count($path))) {
                    $shortest_path = $path;
                }
            }
        }
        if ($shortest_path != null) {
            $dest_id = array_values($shortest_path)[1];
            $this->pickTokensForTile('cat', $dest_id);
        }
    }

    function showRiggerToolSelection() {
        return $this->getPlayerCharacter(null, 'rigger1') || $this->getPlayerCharacter(null, 'rigger2');
    }

    function skipEscapedPlayers($player_id) {
        $player_token = $this->getPlayerToken($player_id);
        while ($player_token['location'] == 'roof') {
            $player_id = self::activeNextPlayer();
            $player_token = $this->getPlayerToken($player_id);
        }
        return $player_id;
    }

    function reshuffleDeckIfEmpty($deck) {
        $count = $this->cards->countCardInLocation($deck.'_deck');
        if ($count == 0) {
            $this->cards->moveAllCardsInLocation($deck.'_discard', $deck.'_deck');
            $this->cards->shuffle($deck.'_deck');
        }
    }

    function characterActionEnabled($current_player_id, $character) {
        $type = $character['name'];
        $used = self::getGameStateValue('characterAbilityUsed');
        $actions_remaining = self::getGameStateValue('actionsRemaining');
        if ($actions_remaining < 1 && in_array($type, array('hacker2', 'peterman1', 'peterman2', 'rook1', 'rook2', 'spotter1', 'spotter2'))) {
            return FALSE;
        } else if ($used && in_array($type, array('hawk1', 'hawk2', 'juicer2', 'rook1', 'spotter1', 'spotter2'))) {
            return FALSE;
        } else if($type == 'acrobat1') {
            $player_tile = $this->getPlayerTile($current_player_id);
            $floor = $player_tile['location'][5];
            $guard_token = array_values($this->tokens->getCardsOfType('guard', $floor))[0];
            $guard_tile = $this->tiles->getCard($guard_token['location_arg']);

            if (!$this->isTileAdjacent($guard_tile, $player_tile, null, 'guard')) {
                return FALSE;
            }
        } else if($type == 'acrobat2') {
            $player_tile = $this->getPlayerTile($current_player_id);
            if (in_array($player_tile['location_arg'], array(5, 6, 9, 10))) {
                return FALSE;
            }
            if(self::getGameStateValue('actionsRemaining') < 3) {
                return FALSE;
            }
        } else if($type == 'hacker2') {
            if (count($this->tokens->getCardsOfTypeInLocation('hack', null, 'card', $character['id'])) > 0) {
                return FALSE;
            }
        } else if($type == 'hawk1') {
            $player_tile = $this->getPlayerTile($current_player_id);
            if (count($this->getPeekableTiles($player_tile, 'hawk1')) == 0) {
                return FALSE;
            }
        } else if($type == 'juicer2') {
            $player_tile = $this->getPlayerTile($current_player_id);
            $tile_alarms = $this->tokens->getCardsOfTypeInLocation('alarm', null, 'tile', $player_tile['id']);
            $character_alarms = $this->tokens->getCardsOfTypeInLocation('alarm', null, 'card', $character['id']);
            
            if (count($character_alarms) > 0) {
                if (count($tile_alarms) > 0) {
                    return FALSE;
                }
            } else if (count($tile_alarms) == 0) {
                return FALSE;
            }
        } else if($type == 'peterman2') {
            $player_tile = $this->getPlayerTile($current_player_id);
            $found = FALSE;
            for ($floor=1; $floor <= 3; $floor++) {
                if (abs($floor - $player_tile['location'][5]) == 1) {   
                    $tiles = $this->getTiles($floor);
                    foreach ($tiles as $tile) {
                        if ($tile['type'] == 'safe' && $tile['location_arg'] == $player_tile['location_arg'] && !$this->tokensInTile('open', $tile['id'])) {
                            $found = TRUE;
                            break;
                        }
                    }
                }
                if ($found) {
                    break;
                }
            }
            return $found;
        } else if($type == 'raven2') {
            $crow = array_values($this->tokens->getCardsOfType('crow'))[0];
            $player_tile = $this->getPlayerTile($current_player_id);
            if ($crow['location'] == 'tile' && $crow['location_arg'] == $player_tile['id']) {
                return FALSE;
            }
        } else if($type == 'rigger2') {
            $stealth = $this->getPlayerStealth($current_player_id);
            if ($stealth <= 0) {
                return FALSE;
            }
        } else if($type == 'rook2') {
            if (self::getGameStateValue('firstAction') != 1) {
                return FALSE;
            }
        }
        // I purposely am not checking spotter1/spotter2. I want those to show the error message.

        return TRUE;
    }

    function getCardTokens($tile_id=null) {
        $cards = $this->cards->getCardsInLocation('tile', $tile_id, 'card_location_arg');
        $tokens = [];
        foreach ($cards as $card_id => $card) {
            if (!isset($tokens[$card['location_arg']])) {
                $tokens[$card['location_arg']] = ['type'=>$card['type'],'count'=>0];
            }
            $token = &$tokens[$card['location_arg']];
            if ($token['type'] == 1) {
                // Overwrite if previous was a tool
                $token['type'] = $card['type'];
            }
            $token['count']++;
        }
        return $tokens;
    }

    function notifyGuardMovement($floor, $movement, $has_alarms, $has_event=FALSE) {
        $msg = "Guard on floor $floor is moving $movement spaces";
        if ($has_alarms || $has_event) {
            $msg .= ' including ';
            if ($has_alarms && $has_event) {
                $msg .= 'alarms and an event card';
            } else if ($has_alarms) {
                $msg .= 'alarms';
            } else if ($has_event) {
                $msg .= 'an event card';
            }
        }
        self::notifyAllPlayers('message', self::_($msg), []);
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

    function peek( $tile_id ) {
        self::checkAction('peek');
        $actions_remaining = self::getGameStateValue('actionsRemaining');
        if ($actions_remaining < 1) {
            throw new BgaUserException(self::_("You have no actions remaining"));
        }
        $this->performPeek($tile_id);
        $this->endAction();
    }

    function move( $tile_id ) {
        self::checkAction('move');
        $actions_remaining = self::getGameStateValue('actionsRemaining');
        if ($actions_remaining < 1) {
            throw new BgaUserException(self::_("You have no actions remaining"));
        }
        $tile_choice = $this->performMove($tile_id);
        if (self::getGameStateValue('stealthDepleted')) {
            $this->gamestate->nextState('gameOver');
        } else if ($tile_choice) {
            self::setGameStateValue('tileChoice', $tile_choice);
            $this->gamestate->nextState('tileChoice');
        } else {
            $this->endAction();
        }
    }

    function addSafeDie() {
        self::checkAction('addSafeDie');
        $actions_remaining = self::getGameStateValue('actionsRemaining');
        if ($actions_remaining < 2) {
            throw new BgaUserException(self::_("Adding a die requires 2 actions"));
        }
        $current_player_id = self::getCurrentPlayerId();
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        
        $this->performAddSafeDie($player_tile);
        $this->endAction(2);
    }

    function rollSafeDice() {
        self::checkAction('rollSafeDice');
        $actions_remaining = self::getGameStateValue('actionsRemaining');
        if ($actions_remaining < 1) {
            throw new BgaUserException(self::_("You have no actions remaining"));
        }
        $current_player_id = self::getCurrentPlayerId();
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);

        if (!$this->performSafeDiceRoll($player_tile))
            $this->endAction();
    }

    function hack() {
        self::checkAction('hack');
        $actions_remaining = self::getGameStateValue('actionsRemaining');
        if ($actions_remaining < 1) {
            throw new BgaUserException(self::_("You have no actions remaining"));
        }
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
        self::notifyAllPlayers('message', clienttranslate('${player_name} added a hack token'), [
            'player_name' => self::getCurrentPlayerName()
        ]);
        $this->endAction();
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

        $bust = $this->getPlayerLoot('bust', $current_player_id);
        if ($bust) {
            throw new BgaUserException(self::_("You may not use tools while holding the Bust"));
        }

        $choice = $this->handleToolEffect($current_player_id, $card);
        if ($choice) {
            self::setGameStateValue('cardChoice', $card['id']);
            $this->gamestate->nextState('cardChoice');
        } else {
            $this->cards->moveCard($card['id'], 'tools_discard');
            $this->notifyPlayerHand($current_player_id, array($card['id']));
            $type = $this->getCardType($card);
            self::notifyAllPlayers('message', clienttranslate('${player_name} played the ${card_type} card'), [
                'player_name' => self::getCurrentPlayerName(),
                'card_type' => $this->getDisplayedCardName($type)
            ]);
            
            $this->gamestate->nextState('endAction');
        }
    }

    function selectCardChoice($type, $id) {
        self::checkAction('selectCardChoice');
        $card = $this->cards->getCard(self::getGameStateValue('cardChoice'));
        $tile_choice = $this->handleSelectCardChoice($card, $type, $id);
        if (self::getGameStateValue('stealthDepleted')) {
            $this->gamestate->nextState('gameOver');
        } else if ($tile_choice) {
            self::setGameStateValue('tileChoice', $tile_choice);
            $this->gamestate->nextState('tileChoice');
        } else {
            if ($card['type'] == 3) {
                $this->gamestate->nextState('endTurn');    
            } elseif($card['type'] == 0) {
                $type = $this->getCardType($card);
                self::notifyAllPlayers('message', clienttranslate('${player_name} used their character action'), [
                    'player_name' => self::getCurrentPlayerName()
                ]);
                self::setGameStateValue('characterAbilityUsed', 1);
                if (in_array($type, array('hacker2', 'spotter1', 'spotter2'))) {
                    $this->endAction(); // Spent action
                } else if($type == 'peterman2') {
                    $this->endAction($id < 10 ? 2 : 1); // Spent 1 or 2 actions
                } else if($type == 'acrobat2') {
                    self::setGameStateValue('actionsRemaining', 0);
                    $this->endAction(0);
                } else {
                    $this->endAction(0); // Free action
                }
            } else {
                $card_type = $this->getCardType($card);
                $this->cards->moveCard($card['id'], 'tools_discard');
                $current_player_id = self::getCurrentPlayerId();
                $this->notifyPlayerHand($current_player_id, array($card['id']));
                self::notifyAllPlayers('message', clienttranslate('${player_name} played the ${card_type} card'), [
                    'player_name' => self::getCurrentPlayerName(),
                    'card_type' => $this->getDisplayedCardName($card_type)
                ]);
                $this->endAction(0);
            }
        }
    }

    function cancelCardChoice() {
        self::checkAction('cancelCardChoice');
        $card = $this->cards->getCard(self::getGameStateValue('cardChoice'));
        if ($card['type'] == 2) {
            throw new BgaUserException(self::_('You may not cancel event effects'));
        } elseif ($this->getCardType($card) == 'stethoscope') {
            $this->applyDieRoll();
            $this->endAction();
        } else {
            // Don't run normal action decrease logic
            $this->gamestate->nextState('nextAction');
        }
    }

    function selectTileChoice($selected) {
        self::checkAction('selectTileChoice');
        $tile = $this->handleSelectTileChoice($selected);
        $motion_exit = self::getGameStateValue('motionTileExitChoice');
        if ($tile['type'] == 'motion' && $motion_exit > 0) {
            self::setGameStateValue('tileChoice', $motion_exit);
            $this->gamestate->nextState('tileChoice');
        } else {
            self::setGameStateValue('tileChoice', 0);
            $this->endAction();
        }
        self::setGameStateValue('motionTileExitChoice', 0);
    }

    function characterAction() {
        self::checkAction('characterAction');
        $current_player_id = self::getCurrentPlayerId();
        $character = $this->getPlayerCharacter($current_player_id);
        $type = $this->getCardType($character);
        $used = self::getGameStateValue('characterAbilityUsed');

        $actions_remaining = self::getGameStateValue('actionsRemaining');
        if ($actions_remaining < 1 && in_array($type, array('hacker2', 'rook1', 'spotter1', 'spotter2'))) {
            throw new BgaUserException(self::_("You have no actions remaining"));
        } else if ($used && in_array($type, array('hawk1', 'hawk2', 'juicer2', 'rook1', 'spotter1', 'spotter2'))) {
            throw new BgaUserException(self::_('Character action can be used once per turn'));
        } else if($type == 'acrobat2') {
            $player_tile = $this->getPlayerTile($current_player_id);
            if (in_array($player_tile['location_arg'], array(5, 6, 9, 10))) {
                throw new BgaUserException(self::_('Must be on an outer tile'));
            }
            if(self::getGameStateValue('actionsRemaining') < 3) {
                throw new BgaUserException(self::_('Must have at least 3 actions'));
            }
            self::setGameStateValue('cardChoice', $character['id']);
            $this->gamestate->nextState('cardChoice');
        } else if($type == 'hacker2') {   
            if (count($this->tokens->getCardsOfTypeInLocation('hack', null, 'card', $character['id'])) > 0) {
                throw new BgaUserException(self::_('You already have a hack token'));
            }
            $this->pickTokens('hack', 'card', $character['id']);
            self::notifyAllPlayers('message', clienttranslate('${player_name} used their character action'), [
                'player_name' => self::getCurrentPlayerName()
            ]);
            $this->endAction();
        } else if($type == 'juicer2') {
            $player_tile = $this->getPlayerTile($current_player_id);
            $tile_alarms = $this->tokens->getCardsOfTypeInLocation('alarm', null, 'tile', $player_tile['id']);
            $character_alarms = $this->tokens->getCardsOfTypeInLocation('alarm', null, 'card', $character['id']);
            
            if (count($character_alarms) > 0) {
                if (count($tile_alarms) > 0) {
                    throw new BgaUserException(self::_('Tile already has an alarm token'));
                }
                $this->moveToken(array_values($character_alarms)[0]['id'], 'deck');
                $this->triggerAlarm($player_tile);
            } else {
                if (count($tile_alarms) == 0) {
                    throw new BgaUserException(self::_('Tile does not have an alarm token'));
                }
                $this->moveToken(array_values($tile_alarms)[0]['id'], 'card', $character['id']);
                $this->nextPatrol($player_tile['location'][5]);
            }
            self::setGameStateValue('characterAbilityUsed', 1);
            self::notifyAllPlayers('message', clienttranslate('${player_name} used their character action'), [
                'player_name' => self::getCurrentPlayerName()
            ]);
        } else if($type == 'raven2') {
            $crow = array_values($this->tokens->getCardsOfType('crow'))[0];
            $player_tile = $this->getPlayerTile($current_player_id);
            $this->moveToken($crow['id'], 'tile', $player_tile['id']);
            self::notifyAllPlayers('message', clienttranslate('${player_name} used their character action'), [
                'player_name' => self::getCurrentPlayerName()
            ]);
        } else if($type == 'rigger2') {
            $stealth = $this->getPlayerStealth($current_player_id);
            if ($stealth <= 0) {
                throw new BgaUserException(self::_('You cannot lose any more stealth'));
            }
            $this->decrementPlayerStealth($current_player_id);
            self::setGameStateValue('drawToolsPlayer', $current_player_id);
            self::notifyAllPlayers('message', clienttranslate('${player_name} used their character action'), [
                'player_name' => self::getCurrentPlayerName()
            ]);
            $this->endAction(0);
        } else if($type == 'rook1') {
            self::setGameStateValue('playerChoice', 2); // Rook 1
            $this->gamestate->nextState('playerChoice');
        } else if($type == 'rook2') {
            if (self::getGameStateValue('firstAction') != 1) {
                throw new BgaUserException(self::_('You may only use this ability as your first action'));
            }
            self::setGameStateValue('playerChoice', 3); // Rook 2
            $this->gamestate->nextState('playerChoice');
        } else if($type == 'spotter1') {
            $player_tile = $this->getPlayerTile($current_player_id);
            $floor = $player_tile['location'][5];
            $top_card = $this->cards->getCardOnTop("patrol$floor".'_deck');
            if (!$top_card) {
                throw new BgaUserException(self::_('Patrol deck is empty'));
            }
            self::setGameStateValue('cardChoice', $character['id']);
            $this->gamestate->nextState('cardChoice');
        } else if($type == 'spotter2') {
            $top_card = $this->cards->getCardOnTop("events_deck");
            if (!$top_card) {
                throw new BgaUserException(self::_('Event deck is empty'));
            }
            self::setGameStateValue('cardChoice', $character['id']);
            $this->gamestate->nextState('cardChoice');
        } else if (in_array($type, array('acrobat1', 'hawk1', 'hawk2', 'juicer1', 'peterman2', 'raven1', 'spotter1', 'spotter2'))) {
            self::setGameStateValue('cardChoice', $character['id']);
            $this->gamestate->nextState('cardChoice');
        } else {
            throw new BgaUserException(self::_('Character does not have a special action'));
        }
    }

    function trade() {
        self::checkAction('trade');
        $current_player_id = self::getCurrentPlayerId();
        $current_player_token = $this->getPlayerToken($current_player_id);
        $player_tokens = $this->tokens->getCardsOfTypeInLocation('player', null, 'tile', $current_player_token['location_arg']);
        if (count($player_tokens) < 2) {
            throw new BgaUserException(self::_('There are no other players in your tile'));
        }

        if (count($player_tokens) == 2) {
            foreach ($player_tokens as $token) {
                if ($token['type_arg'] != $current_player_id) {
                    $this->createTrade($current_player_id, $token['type_arg']);
                    break;
                }
            }
            $this->gamestate->nextState('proposeTrade');
        } else {
            self::setGameStateValue('playerChoice', 1); // Trade
            $this->gamestate->nextState('playerChoice');
        }
    }

    function selectPlayerChoice($selected) {
        self::checkAction('selectPlayerChoice');
        $current_player_id = self::getCurrentPlayerId();
        $player_choice = self::getGameStateValue('playerChoice');
        $player_choice_type = $this->player_choices[$player_choice];
        $this->handleSelectPlayerChoice($current_player_id, $player_choice_type, $selected);
    }

    function proposeTrade($p1_ids, $p2_ids) {
        self::checkAction('proposeTrade');
        $trade = $this->getTrade();
        
        $card_ids = array();
        array_merge($card_ids, $p1_ids, $p2_ids);
        foreach ($this->cards->getCards($card_ids) as $id => $card) {
            if (in_array($card['type'], array(0, 3))) {
                throw new BgaUserException(self::_('Card must be a tool or loot'));
            } else if($card['location'] != 'hand' || !in_array($card['location'], array($trade['current_player'], $trade['other_player']))) {
                throw new BgaUserException(self::_('Card does not belong to trading player'));
            }
        }
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers('message', clienttranslate('${player_name} proposed a trade to ${other_name}'), [
            'player_name' => $players[$trade['current_player']]['player_name'],
            'other_name' => $players[$trade['other_player']]['player_name'],
        ]);

        $this->createTradeCards($trade['id'], $trade['current_player'], $p1_ids);
        $this->createTradeCards($trade['id'], $trade['other_player'], $p2_ids);
        $this->gamestate->nextState('nextTradePlayer');
    }

    function confirmTrade() {
        self::checkAction('confirmTrade');
        $trade = $this->getTrade();
        $p1_cards = array_keys($this->getTradeCards($trade['id'], $trade['current_player']));
        $this->cards->moveCards($p1_cards, 'hand', $trade['current_player']);
        $p2_cards = array_keys($this->getTradeCards($trade['id'], $trade['other_player']));
        $this->cards->moveCards($p2_cards, 'hand', $trade['other_player']);
        $this->notifyPlayerHand($trade['current_player'], $p2_cards);
        $this->notifyPlayerHand($trade['other_player'], $p1_cards);
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers('message', clienttranslate('${player_name} agreed to ${current_name}\'s trade'), [
            'player_name' => $players[$trade['other_player']]['player_name'],
            'current_name' => $players[$trade['current_player']]['player_name'],
        ]);
        self::incStat(1, 'trade_confirmed', $players[$trade['other_player']]['player_id']);
        self::incStat(1, 'trade_confirmed', $players[$trade['current_player']]['player_id']);
        $this->gamestate->nextState('endTradeOtherPlayer');
    }

    function cancelPlayerChoice() {
        self::checkAction('cancelPlayerChoice');
        self::setGameStateValue('playerChoice', 0);
        $this->gamestate->nextState('nextAction');
    }

    function cancelTrade() {
        self::checkAction('cancelTrade');
        $state = $this->gamestate->state();
        if ($state['name'] == 'confirmTrade') {
            self::notifyAllPlayers('message', clienttranslate('${player_name} cancelled the trade'), [
                'player_name' => self::getActivePlayerName()
            ]);
            $this->gamestate->nextState('endTradeOtherPlayer');
        } else {
            $this->deleteTrade();
            $this->gamestate->nextState('nextAction');
        }
        self::setGameStateValue('playerChoice', 0);
    }

    function selectSpecialChoice($selected) {
        self::checkAction('selectSpecialChoice');
        // $current_player_id = self::getCurrentPlayerId();
        $special_choice = self::getGameStateValue('specialChoice');
        $special_choice_type = $this->special_choices[$special_choice];
        $special_choice_arg = self::getGameStateValue('specialChoiceArg');
        $this->handleSelectSpecialChoice($special_choice_type, $special_choice_arg, $selected);
    }

    function cancelSpecialChoice() {
        self::checkAction('cancelSpecialChoice');
        self::setGameStateValue('specialChoice', 0);
        self::setGameStateValue('specialChoiceArg', 0);
        $this->gamestate->nextState('nextAction');
    }

    function discardTool($selected) {
        self::checkAction('discardTool');
        $current_player_id = self::getCurrentPlayerId();
        $tools = $this->cards->getCardsOfTypeInLocation(1, null, 'choice');
        foreach ($tools as $tool_id => $tool) {
            if ($tool_id == $selected) {
                $this->cards->moveCard($tool_id, 'tools_discard');
            } else {
                $this->cards->moveCard($tool_id, 'hand', $current_player_id);
                $this->notifyPlayerHand($current_player_id);
            }
        }
        $next_player = self::getGameStateValue('drawToolsNextPlayer') || $current_player_id;
        if ($next_player != $current_player_id) {
            $this->gamestate->nextState('drawToolsOtherPlayer');
        } else {
            if (self::getGameStateValue('playerPass') == 0) {
                $this->gamestate->nextState('nextAction');
            } else {
                $this->gamestate->nextState('endTurn');
            }
        }
    }

    function takeCards() {
        self::checkAction('takeCards');
        $current_player_id = self::getCurrentPlayerId();
        $player_tile = $this->getPlayerTile($current_player_id);
        if ($player_tile['type'] != 'safe') {
            throw new BgaUserException(self::_('Cards can only be taken from a safe'));
        }
        $tile_cards = $this->cards->getCardsInLocation('tile', $player_tile['id']);
        if (count($tile_cards) == 0) {
            throw new BgaUserException(self::_('There are no cards in your tile'));
        }

        $this->gamestate->nextState('takeCards');
    }

    function confirmTakeCards($l_ids, $r_ids) {
        self::checkAction('confirmTakeCards');
        $current_player_id = self::getCurrentPlayerId();
        $player_tile = $this->getPlayerTile($current_player_id);
        $this->cards->moveCards($l_ids, 'tile', $player_tile['id']);
        $this->cards->moveCards($r_ids, 'hand', $current_player_id);
        $this->notifyPlayerHand($current_player_id);
        $this->notifyTileCards($player_tile['id']);
        self::notifyAllPlayers('message', clienttranslate('${player_name} picked up ${card_count} card${card_s} in their tile'), [
            'player_name' => self::getActivePlayerName(),
            'card_count' => count($r_ids),
            'card_s' => count($r_ids) == 1 ? '' : 's'
        ]);
        $this->endAction(0);
    }

    function cancelTakeCards() {
        self::checkAction('cancelTakeCards');
        $this->gamestate->nextState('nextAction');
    }

    function pickUpCat() {
        self::checkAction('pickUpCat');
        $current_player_id = self::getCurrentPlayerId();
        $player_tile = $this->getPlayerTile($current_player_id);
        $cat_tokens = $this->tokens->getCardsOfTypeInLocation('cat', null, 'tile', $player_tile['id']);
        if (count($cat_tokens) == 0) {
            throw new BgaUserException(self::_('Tile does not contain the cat token'));
        }
        $this->moveTokens(array_keys($cat_tokens), 'deck');
        self::notifyAllPlayers('message', clienttranslate('${player_name} picked up the cat token'), [
            'player_name' => self::getActivePlayerName()
        ]);
        $this->endAction(0);
    }

    function pass() {
        self::checkAction('pass');
        self::setGameStateValue('playerPass', 1);
        $current_player_id = self::getCurrentPlayerId();
        $actions_remaining = self::getGameStateValue('actionsRemaining');
        $trigger_action_count = $this->getPlayerLoot( 'stamp', $current_player_id) ? 1 : 2;
        if ($actions_remaining >= $trigger_action_count) {
            $count = $this->cards->countCardInLocation('events_discard');
            $event_card = $this->cards->pickCardForLocation('events_deck', 'events_discard', $count + 1);
            self::incStat(1, 'event_cards');
            if ($event_card) {
                $type = $this->getCardType($event_card);
                self::notifyAllPlayers('eventCard', self::_("Event Card: $type"), array(
                    'card' => $event_card
                ));
                $event_result = $this->handleEventEffect($current_player_id, $event_card);
            } else {
                $event_result = array('card_choice'=>FALSE,'tile_choice'=>FALSE);
            }
            
            if (self::getGameStateValue('stealthDepleted')) {
                $this->gamestate->nextState('gameOver');
            } elseif ($event_result['card_choice']) {
                self::setGameStateValue('cardChoice', $event_card['id']);
                $this->gamestate->nextState('cardChoice');
            } elseif ($event_result['tile_choice']) {
                self::setGameStateValue('tileChoice', $event_result['tile_choice']);
                $this->gamestate->nextState('tileChoice');
            } elseif ($event_result['player_choice']) {
                self::setGameStateValue('playerChoice', $event_result['player_choice']);
                $this->gamestate->nextState('playerChoice');
            } elseif (self::getGameStateValue('drawToolsPlayer') > 0) {
                $this->gamestate->nextState('endAction');
            } else {
                $this->gamestate->nextState('endTurn');
            }
        } else {
            $this->gamestate->nextState('endTurn');
        }
    }

    function escape() {
        self::checkAction('escape');
        $actions_remaining = self::getGameStateValue('actionsRemaining');
        if ($actions_remaining < 1) {
            throw new BgaUserException(self::_("You have no actions remaining"));
        }

        $current_player_id = self::getCurrentPlayerId();
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        if (!$this->canEscape($player_tile)) {
            throw new BgaUserException(self::_('All safes have not been opened yet'));
        }
        $this->moveToken($player_token['id'], 'roof');
        self::notifyAllPlayers('message', clienttranslate('${player_name} escaped to the roof'), [
            'player_name' => self::getActivePlayerName()
        ]);

        if ($this->allPlayersEscaped()) {
            if ($this->checkWin()) {
                $this->DbQuery("UPDATE player SET player_score='1'");
            }
            $this->gamestate->nextState('gameOver');
        } else {
            $this->gamestate->nextState('nextPlayer');
        }
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
    function argStartingTile() {
        return array(
            'floor' => 1
        );
    }

    function argPlayerTurn() {
        // Can't get current player here for some reason
        return $this->gatherCurrentData(self::getActivePlayerId());
    }

    function argCardChoice() {
        $current_player_id = self::getActivePlayerId();
        $args = $this->gatherCurrentData($current_player_id);
        $card = $this->cards->getCard(self::getGameStateValue('cardChoice'));
        $card_name = $this->getCardType($card);
        $args['card'] = $card;
        $args['card_name'] = $card_name;
        $args['card_name_displayed'] = $this->getDisplayedCardName($card_name);
        $args['choice_description'] = $this->getCardChoiceDescription($card);
        if ($card_name == 'peterman2') {
            $player_tile = $this->getPlayerTile($current_player_id);
            $peterman2_detail = [];
            for ($floor=1; $floor <= 3; $floor++) {
                if (abs($floor - $player_tile['location'][5]) == 1) {   
                    $peterman2_detail[$floor] = FALSE;
                    $tiles = $this->getTiles($floor);
                    foreach ($tiles as $tile) {
                        if ($tile['type'] == 'safe' && $tile['location_arg'] == $player_tile['location_arg'] && !$this->tokensInTile('open', $tile['id'])) {
                            $peterman2_detail[$floor] = TRUE;
                        }
                    }
                }                
            }
            $args['peterman2_detail'] = $peterman2_detail;
        } else if ($card_name == 'spotter1') {
            $player_tile = $this->getPlayerTile($current_player_id);
            $floor = $player_tile['location'][5];
            $args['spotter_card'] = $this->cards->getCardOnTop("patrol$floor".'_deck');
        } else if ($card_name == 'spotter2') {
            $args['spotter_card'] = $this->cards->getCardOnTop("events_deck");
        } else if ($card_name == 'crystal-ball') {
            $args['event_cards'] = $this->cards->getCardsOnTop(3, "events_deck");
        } else if ($card_name == 'stethoscope') {
            $args['rolls'] = $this->tokens->getCardsInLocation("stethoscope");
        }
        return $args;
    }

    function argTileChoice() {
        $player_id = self::getActivePlayerId();
        $player_token = $this->getPlayerToken($player_id);
        $tile = $this->tiles->getCard(self::getGameStateValue('tileChoice'));
        $args = array(
            'escape' => false,
            'peekable' => array(),
            'player_token' => $player_token,
            'tile' => $tile,
            'floor' => $tile['location'][5],
            'actions_remaining' => self::getGameStateValue('actionsRemaining')
        );
        $args['tile_name'] = $tile['type'];
        $args['can_hack'] = $this->canHack($tile);
        $args['can_use_extra_action'] = $this->canUseExtraAction($player_id, $tile);

        return $args;
    }

    function argPlayerChoice() {
        $args = $this->gatherCurrentData(self::getActivePlayerId());
        $player_choice = self::getGameStateValue('playerChoice');
        $args['context'] = $this->player_choices[$player_choice];
        return $args;
    }

    function argProposeTrade() {
        $args = $this->gatherCurrentData(self::getActivePlayerId());
        $args['trade'] = $this->getTrade();
        return $args;
    }

    function argConfirmTrade() {
        $args = $this->gatherCurrentData(self::getActivePlayerId());
        $trade = $this->getTrade();
        $args['trade'] = $trade;
        $args['p1_cards'] = $this->getTradeCards($trade['id'], $trade['other_player']);
        $args['p2_cards'] = $this->getTradeCards($trade['id'], $trade['current_player']);
        return $args;
    }

    function argSpecialChoice() {
        $args = $this->gatherCurrentData(self::getActivePlayerId());
        $special_choice = self::getGameStateValue('specialChoice');
        $choice_arg = self::getGameStateValue('specialChoiceArg');
        $card = $this->cards->getCard(self::getGameStateValue('cardChoice'));
        $type = $this->special_choices[$special_choice];
        if ($type == 'rook1') {
            $args['choice_name'] = clienttranslate('Orders');
            $args['choice_description'] = clienttranslate('an adjacent tile to move the player');
        }
        return $args;
    }

    function argDrawToolsAndDiscard() {
        $args = $this->gatherCurrentData(self::getActivePlayerId());
        $args['tools'] = $this->cards->getCardsOfTypeInLocation(1, null, 'choice');
        return $args;
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
    function stEndAction() {
        $current_player_id = self::getCurrentPlayerId();
        $draw_tools_player_id = self::getGameStateValue('drawToolsPlayer');
        $draw_two = $this->showRiggerToolSelection();
        $next_state = self::getGameStateValue('playerPass') == 1 ? 'endTurn' : 'nextAction';
        if ($draw_tools_player_id == 0) {
            $this->gamestate->nextState($next_state);
        } else if ($draw_tools_player_id != 0 && !$draw_two) {
            self::setGameStateValue('drawToolsPlayer', 0);
            $this->reshuffleDeckIfEmpty('tools');
            $card = $this->cards->pickCard('tools_deck', $current_player_id);
            $card_name = $this->getCardType($card);
            self::incStat( 1, 'tools_drawn', $current_player_id );
            self::notifyAllPlayers('message', clienttranslate('${player_name} draws ${card_name}'), [
                'player_name' => self::getActivePlayerName(),
                'card_name' => $this->getDisplayedCardName($card_name),
            ]);
            $this->notifyPlayerHand($current_player_id);
            $this->gamestate->nextState($next_state);
        } else {
            self::setGameStateValue('drawToolsPlayer', 0);
            if ($draw_tools_player_id != $current_player_id) {
                self::setGameStateValue('drawToolsNextPlayer', $current_player_id);
                $this->gamestate->changeActivePlayer($draw_tools_player_id);
            }
            $this->reshuffleDeckIfEmpty('tools');
            $this->cards->pickCardForLocation('tools_deck', 'choice');
            $this->reshuffleDeckIfEmpty('tools');
            $this->cards->pickCardForLocation('tools_deck', 'choice');
            $this->gamestate->nextState('drawTools');
        }
        self::setGameStateValue('firstAction', 0);
    }

    function stEndTurn() {
        $current_player_id = self::getCurrentPlayerId();
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        $type = $player_tile['type'];
        if ($type == 'thermo' && !$this->tokensInTile('crowbar', $player_tile['id'])) {
            $this->triggerAlarm($player_tile);
        }
        self::setGameStateValue('invisibleSuitActive', 0);
        self::setGameStateValue('characterAbilityUsed', 0);
        self::setGameStateValue('playerPass', 0);
        self::setGameStateValue('firstAction', 1);
        if (self::getGameStateValue('acrobatEnteredGuardTile')) {
            $this->decrementPlayerStealth($current_player_id);
        }
        self::setGameStateValue('acrobatEnteredGuardTile', 0);
        self::notifyAllPlayers('message', clienttranslate('${player_name} ended their turn'), [
            'player_name' => self::getActivePlayerName()
        ]);
        $this->gamestate->nextState( 'moveGuard' );
    }

    function stMoveGuard() {
        $current_player_id = self::getCurrentPlayerId();
        $player_token = $this->getPlayerToken($current_player_id);
        $player_tile = $this->getPlayerTile($current_player_id, $player_token);
        $floor = $player_tile['location'][5];
        $shift_change = $this->getActiveEvent('shift-change');
        if ($shift_change) {
            for ($other_floor=1; $other_floor <= 3; $other_floor++) { 
                if ($other_floor != $floor) {
                    $guard_token = array_values($this->tokens->getCardsOfType('guard', $other_floor))[0];;
                    if ($guard_token['location'] == 'tile') {
                        $alarms = count($this->getFloorAlarmTiles($other_floor));
                        $movement = self::getGameStateValue("patrolDieCount$other_floor") + $alarms;
                        $this->notifyGuardMovement($other_floor, $movement, $alarms > 0, TRUE);
                        $this->moveGuard($other_floor, $movement);
                    }
                }
            }
            $this->cards->moveCard($shift_change['id'], 'events_discard');
            $this->notifyPlayerHand($current_player_id, array($shift_change['id']));
        } else {
            $guard_token = array_values($this->tokens->getCardsOfType('guard', $floor))[0];
            $guard_tile = $this->tiles->getCard($guard_token['location_arg']);
            if ($this->tokensInTile('crow', $guard_tile['id']) &&
                $this->getPlayerCharacter(null, 'raven2') &&
                count($this->getFloorAlarmTiles($floor)) == 0) {
                $crow = array_values($this->tokens->getCardsOfType('crow'))[0];
                $this->moveToken($crow['id'], 'deck');
            } else {
                $alarms = count($this->getFloorAlarmTiles($floor));
                $movement = self::getGameStateValue("patrolDieCount$floor") + $alarms;
                $has_event = FALSE;
                $daydreaming = $this->getActiveEvent('daydreaming');
                if ($daydreaming) {
                    $movement--;
                    $this->cards->moveCard($daydreaming['id'], 'events_discard');
                    $this->notifyPlayerHand($current_player_id, array($daydreaming['id']));
                    $has_event = TRUE;
                }
                $espresso = $this->getActiveEvent('espresso');
                if ($espresso) {
                    $movement++;
                    $this->cards->moveCard($espresso['id'], 'events_discard');
                    $this->notifyPlayerHand($current_player_id, array($espresso['id']));
                    $has_event = TRUE;
                }
                $this->notifyGuardMovement($floor, $movement, $alarms > 0, $has_event);
                $this->moveGuard($floor, $movement);
            }
        }
        if (self::getGameStateValue('stealthDepleted')) {
            $this->gamestate->nextState('gameOver');
        } else {
            $this->gamestate->nextState( 'nextPlayer' );
        }
    }

    function stNextPlayer() {
        $player_id = self::activeNextPlayer();
        $jump_the_gun = $this->getActiveEvent('jump-the-gun');
        if ($jump_the_gun) {
            self::notifyAllPlayers('message', clienttranslate( 'Skipped ${player_name}\'s turn' ), array(
                'player_name' => self::getActivePlayerName()
            ));
            $this->skipEscapedPlayers($player_id);
            $player_id = self::activeNextPlayer();
            $this->cards->moveCard($jump_the_gun['id'], 'events_discard');
            $this->notifyPlayerHand($player_id, array($jump_the_gun['id']));
        } else {
            $player_id = $this->skipEscapedPlayers($player_id);
        }
        self::giveExtraTime( $player_id );
        $heads_up = $this->getActiveEvent('heads-up');
        $actions = $heads_up ? 5 : 4;
        if ($this->getPlayerLoot('mirror', $player_id)) {
            $actions--;
        }
        self::setGameStateValue('actionsRemaining', $actions);
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
        // Cleanup round events for a player
        $round_events = array_keys($this->cards->getCardsOfTypeInLocation(3, null, 'hand', $player_id));
        if (count($round_events) > 0) {
            $this->cards->moveCards($round_events, 'events_discard');
            $this->notifyPlayerHand($player_id, $round_events);
        }
        $this->clearTileTokens('keypad');

        $chihuahua = $this->getPlayerLoot('chihuahua', $player_id);
        if ($chihuahua) {
            $rolls = $this->rollDice(1);
            $this->notifyRoll($rolls, 'chihuahua');
            if (isset($rolls[6])) {
                $tile = $this->getPlayerTile($player_id);
                $this->triggerAlarm($tile);
            }
        }

        $persian_kitty = $this->getPlayerLoot('persian-kitty', $player_id);
        if ($persian_kitty) {
            if (count($this->getPlacedTokens(array('cat'))) == 0) {
                $rolls = $this->rollDice(1);
                $this->notifyRoll($rolls, 'persian-kitty');
                if (isset($rolls[1]) || isset($rolls[2])) {
                    $this->moveCatToken($player_id);
                    self::notifyAllPlayers('message', clienttranslate('${player_name} must pick up the cat token before escaping'), [
                        'player_name' => self::getActivePlayerName()
                    ]);
                }
            }
        }

        $player_tile = $this->getPlayerTile($player_id);
        self::notifyAllPlayers('showFloor', '', [
            'floor' => $player_tile['location'][5]
        ]);

        self::incStat( 1, 'turns_number' );
        self::incStat( 1, 'turns_number', $player_id );

        $this->gamestate->nextState( 'playerTurn' );
    }

    function stNextTradePlayer() {
        $trade = $this->getTrade();
        $this->gamestate->changeActivePlayer( $trade['other_player'] );
        $this->gamestate->nextState( 'confirmTrade' );
    }

    function stEndTradeOtherPlayer() {
        $trade = $this->getTrade();
        $this->gamestate->changeActivePlayer( $trade['current_player'] );
        $this->deleteTrade();
        $this->gamestate->nextState( 'nextAction' );
    }

    function stDrawToolsOtherPlayer() {
        $this->gamestate->changeActivePlayer( self::getGameStateValue('drawToolsNextPlayer') );
        self::setGameStateValue('drawToolsNextPlayer', 0);
        $this->gamestate->nextState( 'nextAction' );
    }

    function stGameOver() {
        $tiles_unflipped = self::getCollectionFromDB("SELECT card_id FROM tile WHERE flipped=0");
        self::setStat( count($tiles_unflipped), 'tiles_unflipped' );
        $sql = "SELECT player_id id, player_score score, player_stealth_tokens stealth_tokens FROM player ";
        $players = self::getCollectionFromDb( $sql );
        foreach ($players as $player_id => $player) {
            self::setStat( $player['stealth_tokens'], 'stealth_remaining', $player_id );
        }
        $this->gamestate->nextState( 'endGame' );   
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
