/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * burglebros implementation : © Brian Gregg baritonehands@gmail.com
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
            
            // TODO: Set up your game interface here, according to "gamedatas"
            window.gamedatas = gamedatas;
            window.app = this;

            this.zones = {};

            // Setting up player boards
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
                hand.centerItems = true;
                if (me) {
                    hand.setSelectionMode(1);
                    hand.setSelectionAppearance('class');
                    dojo.connect( hand, 'onChangeSelection', this, 'handleCardSelected');
                } else {
                    hand.setSelectionMode(0);
                }

                this.addCardTypesToStock(hand, [0, 1, 2, 3]);

                var player = gamedatas.players[playerId];
                var cards = player.hand;
                this.loadPlayerHand(hand, cards, [], false);
                this.createPlayerBoard(playerId);
            }

            for(var floor = 1; floor <= 3; floor++) {
                var key = 'floor' + floor;
                for ( var tileId in this.gamedatas[key]) {
                    var tile = this.gamedatas[key][tileId];
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
                if (this.canMoveToken(token)) {
                    this.moveToken('player', token);
                }
            }

            for (var token_id in gamedatas.guard_tokens) {
                var token = gamedatas.guard_tokens[token_id];
                this.createGuardToken(token_id);
                if (this.canMoveToken(token)) {
                    this.moveToken('guard', token);
                }
            }

            for (var token_id in gamedatas.patrol_tokens) {
                var token = gamedatas.patrol_tokens[token_id];
                this.createPatrolToken(token, token.die_num);
            }

            for (var token_id in gamedatas.crack_tokens) {
                var token = gamedatas.crack_tokens[token_id];
                this.createSafeToken(token, token.die_num);
            }

            for (var token_id in gamedatas.generic_tokens) {
                var token = gamedatas.generic_tokens[token_id];
                this.createGenericToken(token);
                if (this.canMoveToken(token)) {
                    this.moveToken('generic', token);
                }
            }

            for (var card_id in gamedatas.card_tokens) {
                var token = gamedatas.card_tokens[card_id];
                this.createCardToken(card_id, token.type, token.count, '');
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
            console.log( 'Entering state: '+stateName, args.args );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
            case 'endTurn':
            case 'tileChoice':
                this.showFloor(this.currentFloor());
                break;
            
            case 'cardChoice':
                if (args.args.spotter_card && (this.isCardChoice('spotter1') || this.isCardChoice('spotter2'))) {
                    var card = args.args.spotter_card;
                    if (card.type == 3) {
                        dojo.place( this.eventCardHtml(card), 'spotter_card' );
                    } else {
                        var cardType = parseInt(card.type, 10);
                        var cardIndex = parseInt(card.type_arg, 10) - 1;
                        var id = ((cardType - 4) * 16) + cardIndex;
                        dojo.place( this.patrolCardHtml(card, id, true), 'spotter_card' );
                    }
                    this.addCardTooltip(card, 'event_card_dialog');
                    this.displayElement('temp_display');
                    dojo.removeClass('spotter_card_wrapper', 'hidden');
                }
                // Crystal Ball, player can choose to reorder the 3 upcoming events
                if (args.args.event_cards) {
                    this.setupCrystalBallCards(args.args.event_cards);
                }
                // Stethoscope, player can reroll one die
                if (this.isCurrentPlayerActive() && args.args.rolls) {
                    this.setupStethoscope(args.args.rolls);
                }
                break;
            
            case 'proposeTrade':
                if (this.isCurrentPlayerActive()) {
                    this.proposeTrade(args.args);
                }
                break;

            case 'confirmTrade':
                if (this.isCurrentPlayerActive()) {
                    this.confirmTrade(args.args);
                }
                break;
            
            case 'drawToolsAndDiscard':
                if (this.isCurrentPlayerActive()) {
                    this.drawToolsAndDiscard(args.args.tools);
                }
                break;
            
            case 'takeCards':
                if (this.isCurrentPlayerActive()) {
                    this.takeCards(args.args);
                }
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
            case 'cardChoice':
                this.hideElement('temp_display');
                dojo.addClass('crystal_ball_wrapper', 'hidden');
                dojo.addClass('spotter_card_wrapper', 'hidden');
                $('crystal_ball_cards').innerHTML = '';
                dojo.query("#maintitlebar_content .icon_die").forEach( (el) => this.fadeOutAndDestroy(el) );
                $('spotter_card').innerHTML = '';
                if ($("dice_choice"))
                    this.fadeOutAndDestroy($("dice_choice"));
                this.disconnectAll();
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
                        if (this.canTrade()) {
                            this.addActionButton( 'button_trade' , _('Trade'), 'handleTrade' );
                        }
                        if (this.canTakeCards()) {
                            this.addActionButton( 'button_take_cards' , _('Take Cards'), 'handleTakeCards' );
                        }
                        if (this.canPickUpKitty()) {
                            this.addActionButton( 'button_pickup' , _('Pick Up Cat'), 'handlePickUpCat' );
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
                            var detail = this.gamedatas.gamestate.args.peterman2_detail;
                            if (floor < 3 && detail[floor + 1]) {
                                this.addActionButton('button_add_die_up', _('Add Safe Die (Up)'), dojo.hitch(this, 'handleCardChoiceButton', floor + 1));
                                this.addActionButton('button_roll_dice_up', _('Roll Safe Dice (Up)'), dojo.hitch(this, 'handleCardChoiceButton', floor + 11));
                            }
                            if (floor > 1 && detail[floor - 1]) {
                                this.addActionButton('button_add_die_down', _('Add Safe Die (Down)'), dojo.hitch(this, 'handleCardChoiceButton', floor - 1));
                                this.addActionButton('button_roll_dice_down', _('Roll Safe Dice (Down)'), dojo.hitch(this, 'handleCardChoiceButton', floor + 9));
                            }
                        } else if(this.isCardChoice('acrobat2') && this.actionsRemaining() >= 3) {
                            var floor = this.currentFloor();
                            if (floor < 3) {
                                this.addActionButton('button_acrobat_up', _('Move Up'), dojo.hitch(this, 'handleCardChoiceButton', floor + 1));
                            }
                            if (floor > 1) {
                                this.addActionButton('button_acrobat_down', _('Move Down'), dojo.hitch(this, 'handleCardChoiceButton', floor - 1));
                            }
                        } else if(this.isCardChoice('crystal-ball')) {
                            this.addActionButton('crystal_ball_button', _('Confirm event order'), 'handleMultipleIdCardChoiceButton');
                        } else if(this.isCardChoice('spotter') || this.isCardChoice('spotter2')) {
                            this.addActionButton('top', _('Keep on top'), dojo.hitch(this, 'handleCardChoiceButton', 1));
                            this.addActionButton('bottom', _('Put on bottom'), dojo.hitch(this, 'handleCardChoiceButton', 2), null, null, 'gray');
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
                    case 'playerChoice':
                        if (args.context !== "squeak")  // cannot cancel Squeak event
                            this.addActionButton('button_cancel', _('Cancel'), 'handleCancelPlayerChoice');
                        break;
                    case 'proposeTrade':
                    case 'confirmTrade':
                        this.addActionButton('button_cancel', _('Cancel Trade'), 'handleCancelTrade');
                        break;
                    case 'specialChoice':
                        this.addActionButton('button_cancel', _('Cancel'), 'handleCancelSpecialChoice');
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
                        // dojo.destroy(div_id);
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
            }), 'token_container');
        },

        moveToken: function(token_type, token) {
            if (token_type === 'player') {
                var meepleZoneId = 'tile_' + token.location_arg + '_meeples';
                this.zones[meepleZoneId].placeInZone('meeple_' + token.id);
            } else {
                var zoneId = token.location + '_' + token.location_arg + '_tokens';
                if (this.zones[zoneId]) {
                    this.zones[zoneId].placeInZone(token_type + '_token_' + token.id);
                }
            }
        },

        removeToken: function(token_type, id) {
            var deck = this.gamedatas[token_type + '_tokens'];
            var token = deck[id];
            if (token && this.canMoveToken(token)) {
                if (token_type === 'player') {
                    var meepleZoneId = 'tile_' + token.location_arg + '_meeples';
                    this.zones[meepleZoneId].removeFromZone('meeple_' + token.id, token.location === 'roof');
                } else {
                    var zoneId = token.location + '_' + token.location_arg + '_tokens';
                    this.zones[zoneId].removeFromZone(token_type + '_token_' + id, token_type === 'generic' || token.location === 'deck');
                }
            }
        },

        createGuardToken: function(token_id) {
            dojo.place(this.format_block('jstpl_guard_token', {
                token_id : token_id,
            }), 'token_container');
        },

        createPatrolToken: function(token, die_num) {
            var div_id = 'patrol_token_' + token.id;
            if ($(div_id)) {
                $(div_id).innerText = die_num;
            } else {
                dojo.place(this.format_block('jstpl_patrol_die', {
                    token_id : token.id,
                    num_spaces : die_num,
                }), 'token_container');
            }

            if (this.canMoveToken(token)) {
                this.moveToken(token.type, token);
            }
        },

        createSafeToken: function(token, die_num) {
            var div_id = 'crack_token_' + token.id;
            if ($(div_id)) {
                $(div_id).innerText = die_num;
            } else {
                dojo.place(this.format_block('jstpl_safe_die', {
                    token_id : token.id,
                    die_num : die_num,
                }), 'token_container');
            }

            if (token.location !== 'deck') {
                this.moveToken('crack', token);
            } 
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

        createCardToken: function(id, type, count, extra_classes) {
            var card_type = this.gamedatas.card_types[type];
            dojo.place(this.format_block('jstpl_card_token', {
                tile_id : id,
                card_type : card_type.name,
                card_count : count || 1,
                token_background : g_gamethemeurl + '/img/tokens.jpg',
                extra_classes: extra_classes
            }), 'tile_' + id + '_cards');
        },

        destroyCardToken: function(id) {
            dojo.destroy('card_token_' + id);
        },

        createPlayerBoard: function(id) {
            var tokenType = this.gamedatas.token_types['stealth'];
            dojo.place(this.format_block('jstpl_player_zone', {
                id : id,
            }), 'player_board_' + id);

            var zone = new ebg.zone();
            var zoneId = 'player_' + id + '_tokens';
            zone.create( this, zoneId, 24, 24 );
            zone.setPattern( 'grid' );
            this.zones[zoneId] = zone;
        },

        addCharacterAction: function() {
            var character = this.gamedatas.gamestate.args.character.name;

            if (!this.gamedatas.gamestate.args.character_action_enabled) {
                return;
            }

            var typeToTitle = {
                acrobat1: 'Acrobat: Flexibility',
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
            };

            if (typeToTitle[character]) {
                this.addActionButton('button_character', _(typeToTitle[character]), 'handleCharacterAction');
            }
        },

        canUseCharacterAction: function() {
        },

        canEscape: function() {
            return this.gamedatas.gamestate.args.escape && this.actionsRemaining() >= 1;
        },

        canPeek: function() {
            return this.gamedatas.gamestate.args.peekable.length > 0 && this.actionsRemaining() >= 1;
        },

        canAddSafeDie: function() {
            return this.gamedatas.gamestate.args.tile.type === 'safe' &&
                this.actionsRemaining() >= 2 &&
                !this.tileContainsToken('open');
        },

        canRollSafeDice: function() {
            return this.gamedatas.gamestate.args.tile.type === 'safe' &&
                this.actionsRemaining() >= 1 &&
                !this.tileContainsToken('open');
        },

        canHack: function() {
            return this.gamedatas.gamestate.args.tile.type.endsWith('-computer') && this.actionsRemaining() >= 1;
        },

        canHackAlarm: function() {
            return this.gamedatas.gamestate.args.can_hack;
        },

        canCancelCardChoice: function() {
            var type = this.gamedatas.gamestate.args.card['type'];
            if (this.gamedatas.gamestate.args.card['type_arg'] == 3) // crystal-ball
                return false;
            return type == 1 || type == 0; // Tools and Characters
        },

        canUseExtraAction: function() {
            return this.gamedatas.gamestate.args.can_use_extra_action;
        },

        canMoveToken: function(token) {
            return ['tile', 'card', 'player'].indexOf(token.location) !== -1;
        },

        canTrade: function() {
            return this.gamedatas.gamestate.args.other_players > 0;
        },

        canTakeCards: function() {
            return this.gamedatas.gamestate.args.tile.type === 'safe' &&
                Object.keys(this.gamedatas.gamestate.args.tile_cards).length > 0;
        },

        canPickUpKitty: function() {
            var type_id = this.getCardTypeForName(2, 'persian-kitty');
            return this.tileContainsToken('cat') && this.handContainsCard(type_id);
        },

        isCardChoice: function(name) {
            return this.gamedatas.gamestate.args.card_name === name;
        },

        playerChoiceContext: function() {
            return this.gamedatas.gamestate.args.context;
        },

        currentFloor: function() {
            return parseInt(this.gamedatas.gamestate.args.floor, 10);
        },

        actionsRemaining: function() {
            return parseInt(this.gamedatas.gamestate.args.actions_remaining, 10);
        },

        tileContainsToken: function(name) {
            var tokens = this.gamedatas.gamestate.args.tile_tokens;
            for(var tokenId in this.gamedatas.gamestate.args.tile_tokens) {
                if (tokens[tokenId].type == name) {
                    return true;
                }
            }
            return false;
        },

        getCardTypeForName: function(type_id, name) {
            var deck_types = this.gamedatas.card_types[type_id].cards;
            for(var idx = 0; idx < deck_types.length; idx++) {
                if (deck_types[idx].name === name) {
                    return deck_types[idx].index;
                }
            }
            return;
        },

        handContainsCard: function(type_id) {
            var hand = this.gamedatas.players[this.player_id].hand;
            for(var id in hand) {
                var card = hand[id];
                if (card.type_arg == type_id) {
                    return true;
                }
            }
            return false;
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

                var tooltipHtml = this.patrolCardHtml(card, id, this.gamedatas[patrolKey + '_discard']);
                var divId = this[patrolKey].getItemDivId(card.id);
                this.addTooltipHtml(divId, tooltipHtml);
            } else {
                this[patrolKey].addToStockWithId(51, 51);
            }
        },

        loadPlayerHand: function(handStock, hand, discard_ids, tradable) {
            for(var cardId in hand) {
                var card = hand[cardId];
                if (tradable && (card.type == 0 || card.type == 3)) {
                    continue;
                }

                var cardTypeId = this.getCardUniqueId(card.type, card.type_arg);
                if (!handStock.getItemById(cardId)) {
                    handStock.addToStockWithId(cardTypeId, cardId);
                    this.addCardTooltip(card, handStock.getItemDivId(cardId));
                }
            }
            for(var idx = 0; idx < discard_ids.length; idx++) {
                var discard_id = discard_ids[idx];
                if (handStock.getItemById(discard_id)) {
                    handStock.removeFromStockById(discard_id);
                }
            }
        },

        addCardTooltip: function(card, divId) {
            var typeInfo = this.gamedatas.card_types[card.type];
            var index = card.type == 0 ? card.type_arg - 1 : card.type_arg;
            var bg_row = Math.floor(index / 2) * -100;
            var bg_col = (index % 2) * -100;
            var tooltipHtml = this.format_block('jstpl_card_tooltip', {
                id : card.id, 
                bg_image: g_gamethemeurl + 'img/' + typeInfo.name + '.jpg',
                bg_position: bg_col.toString() + '% ' + bg_row.toString() + '%'
            });
            this.addTooltipHtml(divId, tooltipHtml);
        },

        addCardTypesToStock: function(stock, types) {
            // Create cards types:
            for (var type = 0; type < types.length; type++) {
                var typeInfo = gamedatas.card_types[types[type]];
                for (var index = 0; index < typeInfo.cards.length; index++) {
                    // Build card type id
                    var card = typeInfo.cards[index];
                    var cardTypeId = this.getCardUniqueId(card.type, card.index);
                    var cardIndex = card.type == 0 ? card.index - 1 : card.index;
                    stock.addItemType(cardTypeId, cardTypeId, g_gamethemeurl + 'img/' + typeInfo.name + '.jpg', cardIndex);
                }
            }
        },

        showTradeDialog: function(opts) {
            var combinedOpts = dojo.mixin({
                l_color: 'black',
                r_color: 'black',
                l_name: 'You',
                r_name: 'Other',
                l_cards: [],
                r_cards: [],
                title: _('Trade Cards'),
                cancel_title: _('Cancel Trade'),
                confirm_title: _('Propose Trade'),
                // Required: close_callback, confirm_callback
            }, opts);
            var dialog = new ebg.popindialog();
            dialog.create( 'proposeTradeDialog' );
            dialog.setTitle( combinedOpts.title );
            
            var html = this.format_block('jstpl_trade_dialog', {
                p1_color: combinedOpts.l_color,
                p2_color: combinedOpts.r_color,
                p1_name: combinedOpts.l_name,
                p2_name: combinedOpts.r_name,
                cancel_title: combinedOpts.cancel_title,
                confirm_title: combinedOpts.confirm_title,
            });
            
            dialog.setContent( html ); // Must be set before calling show() so that the size of the content is defined before positioning the dialog

            var l_stock = new ebg.stock();
            l_stock.create(this, $('trade_p1'), this.cardwidth, this.cardheight);
            l_stock.image_items_per_row = 2;
            l_stock.setSelectionMode(1);
            l_stock.setSelectionAppearance('class');
            this.addCardTypesToStock(l_stock, [1, 2, 3]);
            this.loadPlayerHand(l_stock, combinedOpts.l_cards, [], true);

            var r_stock = new ebg.stock();
            r_stock.create(this, $('trade_p2'), this.cardwidth, this.cardheight);
            r_stock.image_items_per_row = 2;
            r_stock.setSelectionMode(1);
            r_stock.setSelectionAppearance('class');
            this.addCardTypesToStock(r_stock, [1, 2, 3]);
            this.loadPlayerHand(r_stock, combinedOpts.r_cards, [], true);

            var cards = dojo.mixin({}, combinedOpts.l_cards, combinedOpts.r_cards);
            this.connectTradeButtonHandlers(cards, l_stock, r_stock);
            this.connectTradeButtonHandlers(cards, r_stock, l_stock);

            dialog.show();
            
            // Now that the dialog has been displayed, you can connect your method to some dialog elements
            // Example, if you have an "OK" button in the HTML of your dialog:
            var closeCallback = function(evt) {
                evt.preventDefault();
                l_stock.destroy();
                r_stock.destroy();
                dialog.destroy();
                combinedOpts.close_callback();
            };
            dialog.replaceCloseCallback(dojo.hitch(this, closeCallback));
            dojo.connect( $('trade_cancel_button'), 'onclick', this, closeCallback);
            dojo.connect( $('trade_confirm_button'), 'onclick', this, function(evt) {
                evt.preventDefault();
                var idGetter = function (item) { return item.id; };
                var l_cards = dojo.map(l_stock.getAllItems(), idGetter).join(';');
                var r_cards = dojo.map(r_stock.getAllItems(), idGetter).join(';');
                var params = {
                    l_cards: l_cards,
                    r_cards: r_cards,
                    cleanup: function() {
                        l_stock.destroy();
                        r_stock.destroy();
                        dialog.destroy();
                    }
                };
                combinedOpts.confirm_callback(params);
            });
        },

        proposeTrade: function(args) {
            var p1 = this.gamedatas.players[args.trade.current_player];
            var p2 = this.gamedatas.players[args.trade.other_player];
            this.showTradeDialog({
                l_cards: p1.hand,
                r_cards: p2.hand,
                l_name: _('You'),
                r_name: p2.name,
                l_color: p1.color,
                r_color: p2.color,
                close_callback: dojo.hitch(this, function() {
                    this.handleCancelTrade();
                }),
                confirm_callback: dojo.hitch(this, function(confirmArgs) {
                    if (this.checkAction('proposeTrade')) {
                        var params = { lock: true, p1_cards: confirmArgs.l_cards, p2_cards: confirmArgs.r_cards };
                        this.ajaxcall('/burglebros/burglebros/proposeTrade.html', params, this, confirmArgs.cleanup, console.error);
                    }
                })
            });
        },

        connectTradeButtonHandlers: function(cards, from_stock, to_stock) {
            dojo.connect( from_stock, 'onChangeSelection', this, function (control_name, item_id) {
                var item = from_stock.getItemById(item_id);
                var anim_from = from_stock.getItemDivId(item_id);
                this.removeTooltip(anim_from);
                to_stock.addToStockWithId(item.type, item.id, anim_from);
                from_stock.removeFromStockById(item_id);
                this.addCardTooltip(cards[item_id], to_stock.getItemDivId(item_id));
            });
        },

        confirmTrade: function(args) {
            var dialog = new ebg.popindialog();
            dialog.create( 'confirmTradeDialog' );
            dialog.setTitle( _("Confirm Trade") );
            
            // Swap for confirming player
            var p1 = this.gamedatas.players[args.trade.other_player];
            var p2 = this.gamedatas.players[args.trade.current_player];
            var html = this.format_block('jstpl_trade_confirmation_dialog', {
                p1_color: p1.color,
                p2_color: p2.color,
                p2_name: p2.name,
            });
            
            dialog.setContent( html ); // Must be set before calling show() so that the size of the content is defined before positioning the dialog

            var p1_stock = new ebg.stock();
            p1_stock.create(this, $('trade_p1'), this.cardwidth, this.cardheight);
            p1_stock.image_items_per_row = 2;
            p1_stock.setSelectionMode(0);
            this.addCardTypesToStock(p1_stock, [1, 2, 3]);
            this.loadPlayerHand(p1_stock, args.p1_cards, [], true);

            var p2_stock = new ebg.stock();
            p2_stock.create(this, $('trade_p2'), this.cardwidth, this.cardheight);
            p2_stock.image_items_per_row = 2;
            p2_stock.setSelectionMode(0);
            this.addCardTypesToStock(p2_stock, [1, 2, 3]);
            this.loadPlayerHand(p2_stock, args.p2_cards, [], true);

            dialog.show();
            
            // Now that the dialog has been displayed, you can connect your method to some dialog elements
            // Example, if you have an "OK" button in the HTML of your dialog:
            var closeCallback = function(evt) {
                evt.preventDefault();
                p1_stock.destroy();
                p2_stock.destroy();
                dialog.destroy();
                this.handleCancelTrade();
            };
            dialog.replaceCloseCallback(dojo.hitch(this, closeCallback));
            dojo.connect( $('trade_cancel_button'), 'onclick', this, closeCallback);
            dojo.connect( $('trade_confirm_button'), 'onclick', this, function(evt) {
                evt.preventDefault();
                if (this.checkAction('confirmTrade')) {
                    this.ajaxcall('/burglebros/burglebros/confirmTrade.html', { lock: true }, this, function() {
                        p1_stock.destroy();
                        p2_stock.destroy();
                        dialog.destroy();
                    }, console.error);
                }
            });
        },

        takeCards: function(args) {
            var player = this.gamedatas.players[this.player_id];
            this.showTradeDialog({
                l_cards: args.tile_cards,
                l_name: _('In Tile'),
                r_name: _('You'),
                r_color: player.color,
                title: _('Take Cards'),
                cancel_title: _('Cancel'),
                confirm_title: _('Take Cards'),
                close_callback: dojo.hitch(this, function() {
                    this.handleCancelTakeCards();
                }),
                confirm_callback: dojo.hitch(this, function(confirmArgs) {
                    if (this.checkAction('confirmTakeCards')) {
                        var params = { lock: true, l_cards: confirmArgs.l_cards, r_cards: confirmArgs.r_cards };
                        this.ajaxcall('/burglebros/burglebros/confirmTakeCards.html', params, this, confirmArgs.cleanup, console.error);
                    }
                })
            });
        },

        eventCardHtml: function(card, card_id='', extra_classes='') {
            var bg_row = Math.floor(card.type_arg / 2) * -100;
            var bg_col = (card.type_arg % 2) * -100;
            return this.format_block('jstpl_event_card', {
                bg_image: g_gamethemeurl + 'img/events.jpg',
                bg_position: bg_col.toString() + '% ' + bg_row.toString() + '%',
                card_id: card_id,
                extra_classes: extra_classes,
            });

        },

        patrolCardHtml: function(card, bg_id, discards) {
            var bg_row = Math.floor(bg_id / 4) * -100;
            var bg_col = (bg_id % 4) * -100;
            
            var discardHtml = '';
            if (discards) {
                discardHtml = '<div class="patrol-discard-container">';
                for(var discardId in discards) {
                    if (discardId != card.id) {   
                        var discard = discards[discardId];
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
            }
            
            return this.format_block('jstpl_patrol_tooltip', {
                patrol_floor: card['location'][5],
                patrol_discards: discardHtml,
                bg_image: g_gamethemeurl + 'img/patrol.jpg',
                bg_position: bg_col.toString() + '% ' + bg_row.toString() + '%'
            });
        },

        drawToolsAndDiscard: function(cards) {
            var dialog = new ebg.popindialog();
            dialog.create( 'drawToolsAndDiscardDialog' );
            dialog.setTitle( _("Choose a Card to Discard") );
            dialog.hideCloseIcon();
            
            var html = this.format_block('jstpl_draw_tools_dialog', {});
            
            dialog.setContent( html ); // Must be set before calling show() so that the size of the content is defined before positioning the dialog

            var tools_stock = new ebg.stock();
            tools_stock.create(this, $('draw_tools_stock'), this.cardwidth * 2, this.cardheight * 2);
            tools_stock.image_items_per_row = 2;
            tools_stock.setSelectionMode(1);
            tools_stock.setSelectionAppearance('class');
            this.addCardTypesToStock(tools_stock, [1]);
            this.loadPlayerHand(tools_stock, cards, [], true);
            
            dialog.show();
            
            // Now that the dialog has been displayed, you can connect your method to some dialog elements
            // Example, if you have an "OK" button in the HTML of your dialog:
            dojo.connect( $('draw_tools_discard_button'), 'onclick', this, function(evt) {
                evt.preventDefault();
                var selected = tools_stock.getSelectedItems();
                if (selected.length == 0) {
                    this.showMessage(_("You must select a tool to discard"), 'error');
                } else {
                    this.handleDiscardToolButton(selected[0].id, function() {
                        tools_stock.destroy();
                        dialog.destroy();
                    });
                }
            });
        },

        setupStethoscope: function(rolls) {
            for (id in rolls) {
                dojo.place(this.format_block('jstpl_die', {
                    die_id : 'die_' + id + '_' + rolls[id].type_arg,
                    die_value : rolls[id].type_arg,
                }), 'maintitlebar_content');
                this.connect($('die_' + id + '_' + rolls[id].type_arg), 'onclick', 'selectDie');
            }
            dojo.place( '<div style="display:block;position:relative;width:100%;" id="dice_choice" class="hidden_animated"></div>', 'maintitlebar_content')
            for (var i = 1; i <= 6; i++) {
                dojo.place(this.format_block('jstpl_die', {
                    die_id : 'alternative_die_' + i,
                    die_value : i,
                }), 'dice_choice');
                this.connect($('alternative_die_' + i), 'onclick', 'handleMultipleIdCardChoiceButton');
            }
        },
        selectDie: function(e) {
            dojo.query('.icon_die.selected').removeClass('selected');
            dojo.addClass(e.target, 'selected');
            this.displayElement('dice_choice');
        },

        setupCrystalBallCards: function(event_cards) {
            this.elementDragged = null;
            for (var i in event_cards) {
                var card = event_cards[i];
                dojo.place( this.eventCardHtml(card, '_' + card.id, 'crystal_ball_card', true), 'crystal_ball_cards' );
                this.addCardTooltip(card, 'event_card_dialog' + card.id);
            }
            if (this.isCurrentPlayerActive()) {
                this.connectClass('crystal_ball_card', 'onclick', 'toggleCardSelection');
            }
            this.displayElement('temp_display');
            dojo.removeClass('crystal_ball_wrapper', 'hidden');
        },
        toggleCardSelection (e) {
            var is_toggle = dojo.hasClass(e.target, 'selected');
            dojo.query('#crystal_ball_cards .selected').removeClass('selected');
            dojo.query('#crystal_ball_cards .vertical_arrow').forEach( (node) => dojo.destroy(node) );
            if ( !is_toggle ) {
                dojo.addClass(e.target, 'selected');
                var index = Array.from(e.target.parentNode.children).indexOf(e.target);
                if (index < 2 )
                    dojo.place( '<div id="move_after" class="vertical_arrow">&#x25B6;</div>', e.target, 'after' );
                if (index > 0)
                    dojo.place( '<div id="move_before" class="vertical_arrow">&#x25C0;</div>', e.target, 'before' );
                this.connectClass('vertical_arrow', 'onclick', 'moveEventCard');
            }
        },
        moveEventCard(e) {
            var container = e.target.parentNode;
            var previous_card = e.target.previousElementSibling;
            var next_card = e.target.nextElementSibling;
            container.insertBefore(next_card, previous_card);
            dojo.query('#crystal_ball_cards .vertical_arrow').forEach( (node) => dojo.destroy(node) );
            dojo.query('#crystal_ball_cards .selected').removeClass('selected');
        },
        displayElement: function(id) {
            $(id).style.maxHeight = '1500px';
            $(id).style.opacity = 1;
        },
        hideElement: function(id) {
            $(id).style.maxHeight = '0px';
            $(id).style.opacity = 0;
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
                if (dojo.hasClass(evt.target, 'meeple')) {
                    selected_type = 'meeple';
                    selected_id = evt.target.id.substring(evt.target.id.lastIndexOf('_') + 1);
                }
                this.ajaxcall('/burglebros/burglebros/selectCardChoice.html', { lock: true, selected_type: selected_type, selected_id: selected_id }, this, console.log, console.error);
            } else if(this.gamedatas.gamestate.name == 'startingTile' && this.checkAction('chooseStartingTile')) {
                this.ajaxcall('/burglebros/burglebros/chooseStartingTile.html', { lock: true, id: id }, this, console.log, console.error);
            } else if(this.gamedatas.gamestate.name == 'playerChoice' && dojo.hasClass(evt.target, 'meeple') && this.checkAction('selectPlayerChoice')) {
                var player_id = evt.target.id.substring(evt.target.id.lastIndexOf('_') + 1);
                this.ajaxcall('/burglebros/burglebros/selectPlayerChoice.html', { lock: true, selected: player_id }, this, console.log, console.error);
            } else if(this.gamedatas.gamestate.name == 'specialChoice' && dojo.hasClass(evt.target, 'tile') && this.checkAction('selectSpecialChoice')) {
                this.ajaxcall('/burglebros/burglebros/selectSpecialChoice.html', { lock: true, selected: id }, this, console.log, console.error);
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

        handleTrade: function(evt) {
            dojo.stopEvent(evt);
            if (this.checkAction('trade')) {
                this.ajaxcall('/burglebros/burglebros/trade.html', { lock: true }, this, console.log, console.error);
            }
        },

        handleTakeCards: function(evt) {
            dojo.stopEvent(evt);
            if (this.checkAction('takeCards')) {
                this.ajaxcall('/burglebros/burglebros/takeCards.html', { lock: true }, this, console.log, console.error);
            }
        },

        handlePickUpCat: function(evt) {
            dojo.stopEvent(evt);
            if (this.checkAction('pickUpCat')) {
                this.ajaxcall('/burglebros/burglebros/pickUpCat.html', { lock: true }, this, console.log, console.error);
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
            if (this.myHand.isSelected(card_id) && this.checkAction('playCard')) {
                this.ajaxcall('/burglebros/burglebros/playCard.html', { lock: true, id: card_id }, this, console.log, console.error);
            } else if (!this.myHand.isSelected(card_id)) {
                this.handleCancelCardChoice();
            }
        },

        handleCancelCardChoice: function() {
            if (this.checkAction('cancelCardChoice')) {
                this.myHand.unselectAll();
                this.ajaxcall('/burglebros/burglebros/cancelCardChoice.html', { lock: true }, this, console.log, console.error);
            }
        },

        handleMultipleIdCardChoiceButton: function(e) {
            var ids = [];
            if (this.isCardChoice('crystal-ball')) {
                dojo.query('#crystal_ball_cards .crystal_ball_card').forEach( (node) => ids.push(node.id.split("_").pop()) );
            } else if (this.isCardChoice('stethoscope')) {
                // Push old value first then new value
                ids.push( dojo.query('.icon_die.selected')[0].id.split('_').pop() );
                ids.push( e.target.id.split('_').pop() );
            }
            this.handleCardChoiceButton(ids.join(";"), null);
        },

        handleCardChoiceButton: function(id, callback) {
            callback = typeof callback == 'object' ? null : callback;
            if (this.checkAction('selectCardChoice')) {
                this.ajaxcall('/burglebros/burglebros/selectCardChoice.html', { lock: true, selected_type: 'button', selected_id: id }, this, callback || console.log, console.error);
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

        handleCancelPlayerChoice: function() {
            if (this.checkAction('cancelPlayerChoice')) {
                this.ajaxcall('/burglebros/burglebros/cancelPlayerChoice.html', { lock: true }, this, console.log, console.error);
            }
        },

        handleCancelTrade: function() {
            if (this.checkAction('cancelTrade')) {
                this.ajaxcall('/burglebros/burglebros/cancelTrade.html', { lock: true }, this, console.log, console.error);
            }
        },

        handleCancelTakeCards: function() {
            if (this.checkAction('cancelTakeCards')) {
                this.ajaxcall('/burglebros/burglebros/cancelTakeCards.html', { lock: true }, this, console.log, console.error);
            }
        },

        handleCancelSpecialChoice: function() {
            if (this.checkAction('cancelSpecialChoice')) {
                this.ajaxcall('/burglebros/burglebros/cancelSpecialChoice.html', { lock: true }, this, console.log, console.error);
            }
        },

        handleDiscardToolButton: function(id, callback) {
            if (this.checkAction('discardTool')) {
                this.ajaxcall('/burglebros/burglebros/discardTool.html', { lock: true, selected: id }, this, callback || console.log, console.error);
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
            dojo.subscribe('safeDieIncreased', this, 'notif_safeDieIncreased');
            dojo.subscribe('patrolDieIncreased', this, 'notif_patrolDieIncreased');
            dojo.subscribe('tileCards', this, 'notif_tileCards');
            dojo.subscribe('showFloor', this, 'notif_showFloor');
            dojo.subscribe('removeWall', this, 'notif_removeWall');
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
                if (this.canMoveToken(token)) {
                    if (isGeneric) {
                        this.createGenericToken(token);
                    }
                    if (token.floor) {
                        this.showFloor(token.floor);
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
            this.showFloor(floor);
            this.playTileOnTable(floor, tile);
        },

        notif_nextPatrol: function(notif) {
            var deck = 'patrol' + notif.args.floor + '_discard';
            this.gamedatas[deck] = notif.args.cards;
            this.gamedatas[deck + '_top'] = notif.args.top;
            this.showFloor(notif.args.floor);
            this.loadPatrolDiscard(notif.args.floor, notif.args.top);
        },

        notif_playerHand: function(notif) {
            var hand = notif.args.hand;
            var playerId = notif.args.player_id;
            this.gamedatas.players[playerId].hand = hand;
            var handStock = playerId == this.player_id ? this.myHand : this.playerHands[playerId];
            this.loadPlayerHand(handStock, hand, notif.args.discard_ids, false);
        },

        notif_eventCard: function(notif) {
            var dialog = new ebg.popindialog();
            dialog.create( 'eventCardDialog' );
            dialog.setTitle( _("Event Card") );
            
            // Show the dialog
            dialog.setContent( this.eventCardHtml(notif.args.card) ); // Must be set before calling show() so that the size of the content is defined before positioning the dialog
            dialog.show();
            
            // Now that the dialog has been displayed, you can connect your method to some dialog elements
            // Example, if you have an "OK" button in the HTML of your dialog:
            // dojo.connect( $('my_ok_button'), 'onclick', this, function(evt){
            //     evt.preventDefault();
            //     dialog.destroy();
            // } );
        },

        notif_safeDieIncreased: function(notif) {
            this.createSafeToken(notif.args.token, notif.args.die_num);
            this.showFloor(notif.args.floor);
        },

        notif_patrolDieIncreased: function(notif) {
            this.createPatrolToken(notif.args.token, notif.args.die_num);
            this.showFloor(notif.args.floor);
        },

        notif_tileCards: function(notif) {
            var tile_id = notif.args.tile_id;
            var token = notif.args.tokens[tile_id];
            this.destroyCardToken(tile_id);
            if (token) {
                this.createCardToken(tile_id, token.type, token.count, '');
            }
        },

        notif_showFloor: function(notif) {
            this.showFloor(notif.args.floor);
        },

        notif_removeWall: function(notif) {
            this.fadeOutAndDestroy($("wall_" + notif.args.wall_id));
        },

   });             
});
