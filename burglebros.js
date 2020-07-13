/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * burglebros implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * burglebros.js
 *
 * burglebros user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock",
    "ebg/zone"
],
function (dojo, declare) {
    return declare("bgagame.burglebros", ebg.core.gamegui, {
        constructor: function(){
            console.log('burglebros constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
            this.cardwidth = 120;
            this.cardheight = 120;
            this.nonGenericTokenTypes = ['player', 'guard', 'patrol', 'crack'];
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );
            
            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                         
                // TODO: Setting up players boards if needed
            }
            
            // TODO: Set up your game interface here, according to "gamedatas"
            window.gamedatas = gamedatas;
            window.app = this;

            this.zones = {};

            this.playerHands = {};
            for (var playerId in gamedatas.players) {
                var hand, handDivId, me = false;
                if (playerId == this.player_id) {
                    this.myHand = new ebg.stock();
                    hand = this.myHand;
                    handDivId = 'myhand';
                    me = true;
                } else {
                    this.playerHands[playerId] = new ebg.stock();
                    hand = this.playerHands[playerId];
                    handDivId = 'player_hand_' + playerId.toString();
                }
                
                hand.create( this, $(handDivId), this.cardwidth, this.cardheight);
                hand.image_items_per_row = 2;
                hand.onItemCreate = dojo.hitch(this, 'createCardZone', hand);
                if (me) {
                    hand.setSelectionMode(1);
                    hand.setSelectionAppearance('class');
                    dojo.connect( hand, 'onChangeSelection', this, 'handleCardSelected' );
                } else {
                    hand.setSelectionMode(0);
                }

                // Create cards types:
                for (var type = 0; type < gamedatas.card_types.length; type++) {
                    var typeInfo = gamedatas.card_types[type];
                    for (var index = 0; index < typeInfo.cards.length; index++) {
                        // Build card type id
                        var card = typeInfo.cards[index];
                        var cardTypeId = this.getCardUniqueId(card.type, card.index);
                        var cardIndex = card.type == 0 ? card.index - 1 : card.index;
                        hand.addItemType(cardTypeId, cardTypeId, g_gamethemeurl + 'img/' + typeInfo.name + '.jpg', cardIndex);
                    }
                }

                var player = gamedatas.players[playerId];
                var cards = player.hand;
                this.loadPlayerHand(playerId, cards);
                this.createPlayerStealthToken(playerId, player.stealth_tokens);
            }

            for(var floor = 1; floor <= 3; floor++) {
                var key = 'floor' + floor;
                for ( var tileId in this.gamedatas[key]) {
                    var tile = this.gamedatas[key][tileId];
                    // this[key].addToStockWithId(tile.type_arg, tile.id);
                    this.createTileContainer(floor, tile);
                    this.playTileOnTable(floor, tile);
                }

                var patrolKey = 'patrol' + floor;
                this[patrolKey] = new ebg.stock();
                this[patrolKey].create(this, $(patrolKey), this.cardwidth, this.cardheight);
                this[patrolKey].image_items_per_row = 4;
                this[patrolKey].setSelectionMode(0);

                for (var type in gamedatas.patrol_types) {
                    var typeInfo = gamedatas.patrol_types[type];
                    for (var index = 0; index < typeInfo.cards.length; index++) {
                        var cardInfo = typeInfo.cards[index];
                        var id = ((cardInfo.type - 4) * 16) + index;
                        this[patrolKey].addItemType(id, id, g_gamethemeurl + 'img/patrol.jpg', id);
                    }
                }
                // Patrol back
                this[patrolKey].addItemType(51, 51, g_gamethemeurl + 'img/patrol.jpg', 51);
                
                var patrolTopKey = patrolKey + '_discard_top';
                this.loadPatrolDiscard(floor, gamedatas[patrolTopKey]);
                dojo.connect( $('floor' + floor.toString() + '_preview'), 'onclick', dojo.hitch(this, 'showFloor', floor));
            }

            for (var wallIdx = 0; wallIdx < gamedatas.walls.length; wallIdx++) {
                var wall = gamedatas.walls[wallIdx];
                this.playWallOnTable(wall);
            }

            for (var token_id in gamedatas.player_tokens) {
                var token = gamedatas.player_tokens[token_id];
                this.createPlayerToken(token_id, token.type_arg);
                if (token.location === 'tile') {
                    this.moveToken('player', token);
                }
            }

            for (var token_id in gamedatas.guard_tokens) {
                var token = gamedatas.guard_tokens[token_id];
                this.createGuardToken(token_id);
                if (token.location === 'tile') {
                    this.moveToken('guard', token);
                }
            }

            for (var token_id in gamedatas.patrol_tokens) {
                var token = gamedatas.patrol_tokens[token_id];
                this.createPatrolToken(token_id, token.die_num);
                if (token.location === 'tile') {
                    this.moveToken('patrol', token);
                }
            }

            for (var token_id in gamedatas.crack_tokens) {
                var token = gamedatas.crack_tokens[token_id];
                this.createSafeToken(token_id, token.die_num);
                if (token.location === 'tile') {
                    this.moveToken('crack', token);
                }
            }

            for (var token_id in gamedatas.generic_tokens) {
                var token = gamedatas.generic_tokens[token_id];
                this.createGenericToken(token);
                if (token.location === 'tile' || token.location === 'card') {
                    this.moveToken('generic', token);
                }
            }

            this.showFloor(this.currentFloor());
 
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
            case 'playerTurn':
            case 'endTurn':
                this.showFloor(this.currentFloor());
                break;
           
           
            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
            case 'playerTurn':
                this.showFloor(this.currentFloor());
                break;
           
           
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                    case 'playerTurn':
                        if (this.canEscape()) {
                            this.addActionButton( 'button_escape', _('Escape'), dojo.hitch(this, 'handleEscape') );
                        }
                        if (this.canPeek()) {
                            this.addActionButton( 'button_peek', _('Peek'), dojo.hitch(this, 'handlePeekClick') );
                        }
                        if (this.canAddSafeDie()) {
                            this.addActionButton( 'button_add_safe_die', _('Add Safe Die'), 'handleAddSafeDie' );
                        }
                        if (this.canRollSafeDice()) {
                            this.addActionButton( 'button_roll_safe_dice', _('Roll Safe Dice'), 'handleRollSafeDice' );
                        }
                        if (this.canHack()) {
                            this.addActionButton( 'button_hack' , _('Hack'), 'handleHack' );
                        }
                        this.addCharacterAction();
                        this.addActionButton( 'button_pass', _('Pass'), 'handlePassClick' );
                        break;
                    case 'cardChoice':
                        if (this.isCardChoice('thermal-bomb')) {
                            var floor = this.currentFloor();
                            if (floor < 3) {
                                this.addActionButton('button_up', _('Up'), dojo.hitch(this, 'handleCardChoiceButton', floor + 1));
                            }
                            if (floor > 1) {
                                this.addActionButton('button_down', _('Down'), dojo.hitch(this, 'handleCardChoiceButton', floor - 1));
                            }
                        } else if(this.isCardChoice('peterman2')) {
                            var floor = this.currentFloor();
                            // XY, X = 0 is add, X = 1 is roll, Y is floor
                            if (floor < 3) {
                                this.addActionButton('button_add_die_up', _('Add Safe Die (Up)'), dojo.hitch(this, 'handleCardChoiceButton', floor + 1));
                                this.addActionButton('button_roll_dice_up', _('Roll Safe Dice (Up)'), dojo.hitch(this, 'handleCardChoiceButton', floor + 11));
                            }
                            if (floor > 1) {
                                this.addActionButton('button_add_die_down', _('Add Safe Die (Down)'), dojo.hitch(this, 'handleCardChoiceButton', floor - 1));
                                this.addActionButton('button_roll_dice_down', _('Roll Safe Dice (Down)'), dojo.hitch(this, 'handleCardChoiceButton', floor + 9));
                            }
                        } else if(this.isCardChoice('acrobat2') && this.gamedatas.gamestate.args.actions_remaining >= 3) {
                            var floor = this.currentFloor();
                            if (floor < 3) {
                                this.addActionButton('button_acrobat_up', _('Move Up'), dojo.hitch(this, 'handleCardChoiceButton', floor + 1));
                            }
                            if (floor > 1) {
                                this.addActionButton('button_acrobat_down', _('Move Down'), dojo.hitch(this, 'handleCardChoiceButton', floor - 1));
                            }
                        }
                        if (this.canCancelCardChoice()) {
                            this.addActionButton('button_cancel', _('Cancel'), 'handleCancelCardChoice');
                        }
                        break;
                    case 'tileChoice':
                        this.addActionButton('button_trigger', _('Trigger Alarm'), dojo.hitch(this, 'handleTileChoiceButton', 0));
                        if (this.canHackAlarm()) {
                            this.addActionButton('button_hack_alarm', _('Hack Alarm'), dojo.hitch(this, 'handleTileChoiceButton', 1));
                        }
                        if (this.canUseExtraAction()) {
                            this.addActionButton('button_extra_action', _('Use an Extra Action'), dojo.hitch(this, 'handleTileChoiceButton', 2));
                        }
                        break;
                }
            }
        },
        
        setupPatrolItem: function(floor, card_div, card_type_id, card_id) {
            var key = floor + 3;
            card_div.innerText = this.gamedatas.patrol_types[key].cards[card_type_id % 16].name;
        },

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */
        changeMainBar: function(message) {
            this.removeActionButtons();
            $("pagemaintitletext").innerHTML = message;
        },

        // Get card unique identifier based on its row and col
        getCardUniqueId : function(type, index) {
            return parseInt(type, 10) * 100 + parseInt(index, 10);
        },

        getTileUniqueId : function(row, col) {
            return parseInt(row, 10) * 4 + parseInt(col, 10);
        },

        createTileContainer: function(floor, tile) {
            var div_id = 'tile_' + tile.id + '_container';
                
            var idx = parseInt(tile.location_arg, 10);
            var row = Math.floor(idx / 4);
            var col = idx % 4;
            dojo.place(this.format_block('jstpl_tile_container', {
                id : tile.id, 
                x : (this.cardwidth + 36) * col,
                y : (this.cardheight + 36) * row,
                name : tile.type + tile.safe_die
            }), 'floor' + floor);
            
            dojo.connect( $(div_id), 'onclick', this, function(evt) {
                this.handleTileClick(evt, tile.id);
            });

            var zone = new ebg.zone();
            var zoneId = 'tile_' + tile.id + '_tokens';
            zone.create( this, zoneId, 24, 24 );
            zone.setPattern( 'grid' );
            this.zones[zoneId] = zone;

            zone = new ebg.zone();
            zoneId = 'tile_' + tile.id + '_meeples';
            zone.create( this, zoneId, 35, 50 );
            zone.setPattern( 'grid' );
            this.zones[zoneId] = zone;
        },

        createCardZone: function(stock, card_div, card_type_id, card_div_id) {
            var card = stock.getFirstItemOfType(card_type_id);
            var card_type = Math.floor(card_type_id / 100);
            if (card_type === 0) { // Character
                dojo.place('<div id="card_' + card.id + '_tokens" class="card-zone"></div>', card_div_id);

                var zone = new ebg.zone();
                var zoneId = 'card_' + card.id + '_tokens';
                zone.create( this, zoneId, 24, 24 );
                zone.setPattern( 'grid' );
                this.zones[zoneId] = zone;
            }
        },

        playTileOnTable : function(floor, tile) {
            var div_id = 'tile_' + tile.id,
                preview_div_id = 'tile_' + tile.id + '_preview';
            if ($(div_id)) {
                dojo.destroy(div_id);
            }
            if ($(preview_div_id)) {
                dojo.destroy(preview_div_id);
            }
                
            var bg_row = Math.floor(tile.type_arg / 2) * -100;
            var bg_col = (tile.type_arg % 2) * -100;
            dojo.place(this.format_block('jstpl_tile', {
                id : tile.id, 
                bg_image: g_gamethemeurl + 'img/tiles.jpg',
                bg_position: bg_col.toString() + '% ' + bg_row.toString() + '%',
                name : tile.type + tile.safe_die
            }), div_id + '_container');
            var preview_row = Math.floor(tile.location_arg / 4) * 28 + 8;
            var preview_col = (tile.location_arg % 4) * 28 + 8;
            dojo.place(this.format_block('jstpl_tile_preview', {
                id : tile.id,
                tile_type : tile.type,
                preview_row: preview_row,
                preview_col: preview_col
            }), 'floor' + floor.toString() + '_preview');

            if (tile.type != 'back') {                
                var tooltipHtml = this.format_block('jstpl_tile_tooltip', {
                    id : tile.id, 
                    bg_image: g_gamethemeurl + 'img/tiles.jpg',
                    bg_position: bg_col.toString() + '% ' + bg_row.toString() + '%'
                });
                this.addTooltipHtml(div_id + '_container', tooltipHtml);
            }
        },

        playWallOnTable : function(wall) {
            var div_id = 'wall_' + wall.id;
                
            var idx = parseInt(wall.position, 10);
            var row = Math.floor(idx / 3);
            var col = idx % 3;
            var sizePlusPadding = 120 + 36;
            var x = wall.vertical == '1' ? 142.5 + (col * sizePlusPadding) : 10 + (row * sizePlusPadding);
            var y = wall.vertical == '1' ? 20 + (row * sizePlusPadding) : 152.5 + (col * sizePlusPadding);
            dojo.place(this.format_block('jstpl_wall', {
                wall_id : wall.id,
                wall_direction : wall.vertical == '1' ? 'vertical' : 'horizontal', 
                x : x,
                y : y
            }), 'floor' + wall.floor);

            dojo.connect( $(div_id), 'onclick', this, function(evt) {
                dojo.stopEvent(evt);
                if (this.checkAction('selectCardChoice')) {
                    this.ajaxcall('/burglebros/burglebros/selectCardChoice.html', { lock: true, selected_type: 'wall', selected_id: wall.id }, this, function () {
                        dojo.destroy(div_id);
                    }, console.error);
                }
            });
        },

        createPlayerToken: function(id, player_id) {
            var character = this.gamedatas.players[player_id].character,
                index = character.type_arg - 1,
                bg_col = index % 2,
                bg_row = Math.floor(index / 2);
            dojo.place(this.format_block('jstpl_meeple', {
                meeple_id : id,
                meeple_background : g_gamethemeurl + '/img/meeples.png',
                meeple_bg_pos : -(bg_col * 35) + 'px ' + -(bg_row * 50) + 'px',
                player_color: this.gamedatas.players[player_id].color
            }), 'debug');
        },

        moveToken: function(token_type, token) {
            if (token_type === 'player') {
                var meepleZoneId = 'tile_' + token.location_arg + '_meeples';
                this.zones[meepleZoneId].placeInZone('meeple_' + token.id);
            } else {
                var zoneId = token.location + '_' + token.location_arg + '_tokens';
                this.zones[zoneId].placeInZone(token_type + '_token_' + token.id);
            }
        },

        removeToken: function(token_type, id) {
            var deck = this.gamedatas[token_type + '_tokens'];
            var token = deck[id];
            if (token && (token.location === 'tile' || token.location === 'card')) {
                if (token_type === 'player') {
                    var meepleZoneId = 'tile_' + token.location_arg + '_meeples';
                    this.zones[meepleZoneId].removeFromZone('meeple_' + token.id, false);
                } else {
                    var zoneId = token.location + '_' + token.location_arg + '_tokens';
                    this.zones[zoneId].removeFromZone(token_type + '_token_' + id, token_type === 'generic');
                }
            }
        },

        createGuardToken: function(token_id) {
            dojo.place(this.format_block('jstpl_guard_token', {
                token_id : token_id,
            }), 'token_container');
        },

        createPatrolToken: function(token_id, die_num) {
            dojo.place(this.format_block('jstpl_patrol_die', {
                token_id : token_id,
                num_spaces : die_num,
            }), 'token_container');
        },

        createSafeToken: function(token_id, die_num) {
            dojo.place(this.format_block('jstpl_safe_die', {
                token_id : token_id,
                die_num : die_num,
            }), 'token_container');
        },

        createGenericToken: function(token) {
            var tokenType = this.gamedatas.token_types[token.type];
            dojo.place(this.format_block('jstpl_generic_token', {
                token_id : token.id,
                token_color : tokenType.color,
                token_type : token.type,
                token_background : g_gamethemeurl + '/img/tokens.jpg',
                token_bg_pos : (tokenType.id * -32) - 4,
                token_letter : token.letter
            }), 'token_container');
        },

        destroyGenericToken: function(id) {
            dojo.destroy('generic_token_' + id);
        },

        createPlayerStealthToken: function(id, count) {
            var tokenType = this.gamedatas.token_types['stealth'];
            dojo.place(this.format_block('jstpl_generic_token', {
                token_id : 'p' + id,
                token_color : tokenType.color,
                token_type : 'stealth',
                token_background : g_gamethemeurl + '/img/tokens.jpg',
                token_bg_pos : (tokenType.id * -32) - 4,
                token_letter : count
            }), 'player_board_' + id);
        },

        addCharacterAction: function() {
            var character = this.gamedatas.gamestate.args.character.name;

            typeToTitle = {
                acrobat2: 'Acrobat: Climb Window',
                hacker2: 'Hacker: Laptop',
                hawk1: 'Hawk: X-Ray',
                hawk2: 'Hawk: Enhance',
                juicer1: 'Juicer: Crybaby',
                juicer2: 'Juicer: Reroute',
                peterman2: 'Peterman: Drill',
                raven1: 'Raven: Distract',
                raven2: 'Raven: Disrupt',
                rigger2: 'Rigger: Tinker',
                rook1: 'Rook: Orders',
                rook2: 'Rook: Disguise',
                spotter1: 'Spotter: Clairvoyance',
                spotter2: 'Spotter: Precognition'
            }

            if (typeToTitle[character]) {
                this.addActionButton('button_character', _(typeToTitle[character]), 'handleCharacterAction');
            }
        },

        canEscape: function() {
            return this.gamedatas.gamestate.args.escape;
        },

        canPeek: function() {
            return this.gamedatas.gamestate.args.peekable.length > 0;
        },

        canAddSafeDie: function() {
            return this.gamedatas.gamestate.args.tile.type === 'safe' &&
                this.gamedatas.gamestate.args.actions_remaining >= 2;
        },

        canRollSafeDice: function() {
            return this.gamedatas.gamestate.args.tile.type === 'safe';
        },

        canHack: function() {
            return this.gamedatas.gamestate.args.tile.type.endsWith('-computer');
        },

        canHackAlarm: function() {
            return this.gamedatas.gamestate.args.can_hack;
        },

        canCancelCardChoice: function() {
            var type = this.gamedatas.gamestate.args.card['type'];
            return type == 1 || type == 0; // Tools and Characters
        },

        canUseExtraAction: function() {
            return this.gamedatas.gamestate.args.can_use_extra_action;
        },

        isCardChoice: function(name) {
            return this.gamedatas.gamestate.args.card_name === name;
        },

        currentFloor: function() {
            return parseInt(this.gamedatas.gamestate.args.floor, 10);
        },

        showFloor: function(floorNum) {
            this.selected_floor = floorNum;
            for (var floor = 1; floor <= 3; floor++) {
                var floorId = 'floor' + floor.toString() + '_tiles';
                var patrolId = 'patrol' + floor.toString();
                var previewId = 'floor' + floor.toString() + '_preview';
                if (floor == floorNum) {
                    dojo.removeClass(floorId, 'hidden');
                    dojo.removeClass(patrolId, 'hidden');
                    dojo.addClass(previewId, 'selected');
                } else {
                    dojo.addClass(floorId, 'hidden');
                    dojo.addClass(patrolId, 'hidden');
                    dojo.removeClass(previewId, 'selected');
                }
            }
        },

        loadPatrolDiscard: function(floor, card) {
            var patrolKey = 'patrol' + floor;
            var existing = this[patrolKey].getAllItems();
            for(var idx = 0; idx < existing.length; idx++) {
                var discardDiv = this[patrolKey].getItemDivId(existing[idx].id);
                this.removeTooltip(discardDiv);
            }
            this[patrolKey].removeAll();
    
            if (card) {
                var cardType = parseInt(card.type, 10);
                var cardIndex = parseInt(card.type_arg, 10) - 1;
                var id = ((cardType - 4) * 16) + cardIndex;
                this[patrolKey].addToStockWithId(id, card.id);

                var bg_row = Math.floor(id / 4) * -100;
                var bg_col = (id % 4) * -100;
                var divId = this[patrolKey].getItemDivId(card.id);
                var discardHtml = '<div class="patrol-discard-container">';
                for(var discardId in this.gamedatas[patrolKey + '_discard']) {
                    if (discardId != card.id) {   
                        var discard = this.gamedatas[patrolKey + '_discard'][discardId];
                        var discardIndex = parseInt(discard.type_arg, 10) - 1;
                        var discard_top = Math.floor(discardIndex / 4) * 62;
                        var discard_left = (discardIndex % 4) * 62;
                        discardHtml += this.format_block('jstpl_patrol_tooltip_discard', {
                            discard_left: discard_left,
                            discard_top: discard_top,
                            bg_image: g_gamethemeurl + 'img/patrol.jpg',
                        });
                    }
                }
                discardHtml += '</div>';
                var tooltipHtml = this.format_block('jstpl_patrol_tooltip', {
                    patrol_floor: floor,
                    patrol_discards: discardHtml,
                    bg_image: g_gamethemeurl + 'img/patrol.jpg',
                    bg_position: bg_col.toString() + '% ' + bg_row.toString() + '%'
                });
                this.addTooltipHtml(divId, tooltipHtml);
                // dojo.place(tooltipHtml, 'tooltip_debug');
            } else {
                this[patrolKey].addToStockWithId(51, 51);
            }
        },

        loadPlayerHand: function(playerId, hand) {
            var handStock = playerId == this.player_id ? this.myHand : this.playerHands[playerId];
            for(var cardId in hand) {
                var card = hand[cardId];
                var cardTypeId = this.getCardUniqueId(card.type, card.type_arg);
                if (!handStock.getItemById(cardId)) {
                    handStock.addToStockWithId(cardTypeId, cardId);

                    var typeInfo = gamedatas.card_types[card.type];
                    var index = card.type == 0 ? card.type_arg - 1 : card.type_arg;
                    var bg_row = Math.floor(index / 2) * -100;
                    var bg_col = (index % 2) * -100;
                    var divId = handStock.getItemDivId(cardId);
                    var tooltipHtml = this.format_block('jstpl_card_tooltip', {
                        id : cardId, 
                        bg_image: g_gamethemeurl + 'img/' + typeInfo.name + '.jpg',
                        bg_position: bg_col.toString() + '% ' + bg_row.toString() + '%'
                    });
                    this.addTooltipHtml(divId, tooltipHtml);
                }
            }
        },

        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/burglebros/burglebros/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        handleTileClick: function(evt, id) {
            dojo.stopEvent(evt);

            if (this.gamedatas.gamestate.name == 'cardChoice' && this.checkAction('selectCardChoice')) {
                var selected_type = 'tile', selected_id = id;
                if (dojo.hasClass(evt.target, 'token')) {
                    selected_type = 'token';
                    selected_id = evt.target.id.substring(evt.target.id.lastIndexOf('_') + 1);
                }
                this.ajaxcall('/burglebros/burglebros/selectCardChoice.html', { lock: true, selected_type: selected_type, selected_id: selected_id }, this, console.log, console.error);
            } else if(this.gamedatas.gamestate.name == 'startingTile' && this.checkAction('chooseStartingTile')) {
                this.ajaxcall('/burglebros/burglebros/chooseStartingTile.html', { lock: true, id: id }, this, console.log, console.error);
            } else {
                var intent = this.intent || 'move';
                if (this.checkAction(intent)) {
                    var url = '/burglebros/burglebros/' + intent + '.html';
                    this.ajaxcall(url, { lock: true, id: id }, this, function() {
                        console.log(arguments);
                        // location.reload();
                    }, console.error);
                    this.intent = 'move';
                }
            }
        },

        handleEscape: function(evt) {
            dojo.stopEvent(evt);
            if (this.checkAction('escape')) {
                this.ajaxcall('/burglebros/burglebros/escape.html', { lock: true }, this, console.log, console.error);
            }
        },

        handlePeekClick: function(evt) {
            dojo.stopEvent(evt);
            this.intent = 'peek';
            this.changeMainBar('Select an adjacent tile to peek');
            this.addActionButton('button_cancel', _('Cancel'), 'handleCancelClick');
        },

        handleCancelClick: function(evt) {
            dojo.stopEvent(evt);
            this.intent = 'move';
            this.updatePageTitle();
        },

        handleAddSafeDie: function(evt) {
            dojo.stopEvent(evt);
            if (this.checkAction('addSafeDie')) {
                this.ajaxcall('/burglebros/burglebros/addSafeDie.html', { lock: true }, this, console.log, console.error);
            }
        },

        handleRollSafeDice: function(evt) {
            dojo.stopEvent(evt);
            if (this.checkAction('rollSafeDice')) {
                this.ajaxcall('/burglebros/burglebros/rollSafeDice.html', { lock: true }, this, function() {
                    console.log(arguments);
                    // location.reload();
                }, console.error);
            }
        },

        handleHack: function(evt) {
            dojo.stopEvent(evt);
            if (this.checkAction('hack')) {
                this.ajaxcall('/burglebros/burglebros/hack.html', { lock: true }, this, console.log, console.error);
            }
        },

        handlePassClick: function(evt) {
            dojo.stopEvent(evt);

            if (this.checkAction('pass')) {
                this.ajaxcall('/burglebros/burglebros/pass.html', { lock: true }, this, function() {
                    console.log(arguments);
                    // location.reload();
                }, console.error);
            }
        },

        handleCardSelected: function(control_name, card_id) {
            if (this.checkAction('playCard')) {
                this.ajaxcall('/burglebros/burglebros/playCard.html', { lock: true, id: card_id }, this, function() {
                    console.log(arguments);
                    // location.reload();
                }, console.error);
            }
        },

        handleCancelCardChoice: function() {
            if (this.checkAction('cancelCardChoice')) {
                this.ajaxcall('/burglebros/burglebros/cancelCardChoice.html', { lock: true }, this, console.log, console.error);
            }
        },

        handleCardChoiceButton: function(id) {
            if (this.checkAction('selectCardChoice')) {
                this.ajaxcall('/burglebros/burglebros/selectCardChoice.html', { lock: true, selected_type: 'button', selected_id: id }, this, console.log, console.error);
            }
        },

        handleTileChoiceButton: function(selected) {
            if (this.checkAction('selectTileChoice')) {
                this.ajaxcall('/burglebros/burglebros/selectTileChoice.html', { lock: true, selected: selected }, this, console.log, console.error);
            }
        },

        handleCharacterAction: function() {
            if (this.checkAction('characterAction')) {
                this.ajaxcall('/burglebros/burglebros/characterAction.html', { lock: true }, this, console.log, console.error);
            }
        },
        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your burglebros.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
            dojo.subscribe('tokensPicked', this, 'notif_tokensPicked');
            dojo.subscribe('tokensPickedSync', this, 'notif_tokensPicked');
            this.notifqueue.setSynchronous( 'tokensPickedSync', 750 );
            dojo.subscribe('tileFlipped', this, 'notif_tileFlipped');
            dojo.subscribe('nextPatrol', this, 'notif_nextPatrol');
            dojo.subscribe('playerHand', this, 'notif_playerHand');
            dojo.subscribe('eventCard', this, 'notif_eventCard');
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */

        notif_tokensPicked: function(notif) {
            var tokens = notif.args.tokens;
            for(var tokenId in tokens) {
                var token = tokens[tokenId];
                var isGeneric = this.nonGenericTokenTypes.indexOf(token.type) == -1;
                var type = isGeneric ? 'generic' : token.type;
                this.removeToken(type, tokenId);
                if(isGeneric) {
                    delete this.gamedatas.generic_tokens[tokenId];
                }
                if (token.location === 'tile' || token.location === 'card') {
                    if (isGeneric) {
                        this.createGenericToken(token);
                    }
                    this.moveToken(type, token);
                    this.gamedatas[type + '_tokens'][tokenId] = token;
                }
            }
        },

        notif_tileFlipped: function(notif) {
            var tile = notif.args.tile,
            floor = tile.location[5];
            deck = 'floor' + floor;
            this.gamedatas[deck][tile.location_arg] = tile;
            this.playTileOnTable(floor, tile);
        },

        notif_nextPatrol: function(notif) {
            var deck = 'patrol' + notif.args.floor + '_discard';
            this.gamedatas[deck] = notif.args.cards;
            this.gamedatas[deck + '_top'] = notif.args.top;
            this.loadPatrolDiscard(notif.args.floor, notif.args.top);
        },

        notif_playerHand: function(notif) {
            for(var playerId in notif.args) {
                var hand = notif.args[playerId];
                this.gamedatas.players[playerId].hand = hand;
                this.loadPlayerHand(playerId, hand);
            }
        },

        notif_eventCard: function(notif) {
            var dialog = new ebg.popindialog();
            dialog.create( 'eventCardDialog' );
            dialog.setTitle( _("Event Card") );
            
            var card = notif.args.card;
            var bg_row = Math.floor(card.type_arg / 2) * -100;
            var bg_col = (card.type_arg % 2) * -100;
            var html = this.format_block('jstpl_event_card', {
                bg_image: g_gamethemeurl + 'img/events.jpg',
                bg_position: bg_col.toString() + '% ' + bg_row.toString() + '%'
            });
            
            // Show the dialog
            dialog.setContent( html ); // Must be set before calling show() so that the size of the content is defined before positioning the dialog
            dialog.show();
            
            // Now that the dialog has been displayed, you can connect your method to some dialog elements
            // Example, if you have an "OK" button in the HTML of your dialog:
            // dojo.connect( $('my_ok_button'), 'onclick', this, function(evt){
            //     evt.preventDefault();
            //     dialog.destroy();
            // } );
        }
   });             
});
