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
            this.cardwidth = 150;
            this.cardheight = 150;
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

            this.playerHand = new ebg.stock();
            this.playerHand.create( this, $('myhand'), this.cardwidth, this.cardheight);
            this.playerHand.image_items_per_row = 2;

            // Create cards types:
            for (var type in gamedatas.card_types) {
                var typeInfo = gamedatas.card_types[type];
                for (var index = 0; index < typeInfo.cards.length; index++) {
                    // Build card type id
                    var card = typeInfo.cards[index];
                    var cardTypeId = this.getCardUniqueId(card.type, card.index);
                    var cardIndex = card.type == 0 ? card.index * 2 : card.index;
                    this.playerHand.addItemType(cardTypeId, cardTypeId, g_gamethemeurl + 'img/' + type + '.jpg', cardIndex);
                }
            }

            for (var playerId in gamedatas.players) {
                var player = gamedatas.players[playerId];
                var hand = player.hand;
                for(var cardId in hand) {
                    var card = hand[cardId];
                    var cardTypeId = this.getCardUniqueId(card.type, card.type_arg);
                    this.playerHand.addToStockWithId(cardTypeId, cardId);
                }
                this.createPlayerStealthToken(playerId, player.stealth_tokens);
            }

            for(var floor = 1; floor <= 3; floor++) {
                var key = 'floor' + floor;
                for ( var tileId in this.gamedatas[key]) {
                    var tile = this.gamedatas[key][tileId];
                    // this[key].addToStockWithId(tile.type_arg, tile.id);
                    this.playTileOnTable(floor, tile);
                }

                var patrolKey = 'patrol' + floor;
                this[patrolKey] = new ebg.stock();
                this[patrolKey].create(this, $(patrolKey), this.cardwidth, this.cardheight);
                this[patrolKey].image_items_per_row = 4;
                this[patrolKey].onItemCreate = dojo.hitch(this, 'setupPatrolItem', floor);
                console.log(this[patrolKey].jstpl_stock_item);

                for (var type in gamedatas.patrol_types) {
                    var typeInfo = gamedatas.patrol_types[type];
                    for (var index = 0; index < typeInfo.cards.length; index++) {
                        var cardInfo = typeInfo.cards[index];
                        var id = ((cardInfo.type - 4) * 16) + index;
                        this[patrolKey].addItemType(id, id, g_gamethemeurl + 'img/patrol.jpg', id);
                    }
                }
                var patrolDeckKey = patrolKey + '_discard';
                var weights = {};
                for (var cardId in gamedatas[patrolDeckKey]) {
                    var card = gamedatas[patrolDeckKey][cardId];
                    var cardType = parseInt(card.type, 10);
                    var cardIndex = parseInt(card.type_arg, 10) - 1;
                    var id = ((cardType - 4) * 16) + cardIndex;
                    this[patrolKey].addToStockWithId(id, cardId);
                    weights[id] = parseInt(card.location_arg, 10);
                }
                this[patrolKey].changeItemsWeight(weights);

                this.createGuardToken(floor);
            }

            for (var wallIdx = 0; wallIdx < 24; wallIdx++) {
                var wall = gamedatas.walls[wallIdx];
                this.playWallOnTable(wall);
            }

            for (var player_id in gamedatas.players) {
                this.createPlayerToken(player_id);
            }

            for (var token_id in gamedatas.player_tokens) {
                var token = gamedatas.player_tokens[token_id];
                if (token.location === 'tile') {
                    this.moveToken('player', token.type_arg, token.location_arg);
                }
            }

            for (var token_id in gamedatas.guard_tokens) {
                var token = gamedatas.guard_tokens[token_id];
                if (token.location === 'tile') {
                    this.moveToken('guard', token.type_arg, token.location_arg);
                }
            }

            for (var token_id in gamedatas.patrol_tokens) {
                var token = gamedatas.patrol_tokens[token_id];
                this.createPatrolToken(token.type_arg, token.die_num);
                if (token.location === 'tile') {
                    this.moveToken('patrol', token.type_arg, token.location_arg);
                }
            }

            for (var token_id in gamedatas.safe_tokens) {
                var token = gamedatas.safe_tokens[token_id];
                this.createSafeToken(token.type_arg, token.die_num);
                if (token.location === 'tile') {
                    this.moveToken('crack', token.type_arg, token.location_arg);
                }
            }

            for (var token_id in gamedatas.generic_tokens) {
                var token = gamedatas.generic_tokens[token_id];
                this.createGenericToken(token_id, token.color, token.letter);
                if (token.location === 'tile') {
                    this.moveToken('generic', token_id, token.location_arg);
                }
            }
 
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
                        this.addActionButton( 'button_peek', _('Peek'), dojo.hitch(this, 'handleIntentClick', 'peek') );
                        // this.addActionButton( 'button_move', _('Move'), dojo.hitch(this, 'handleIntentClick', 'move') );
                        if (this.canAddSafeDie()) {
                            this.addActionButton( 'button_add_safe_die', _('Add Safe Die'), 'handleAddSafeDie' );
                        }
                        if (this.canRollSafeDice()) {
                            this.addActionButton( 'button_roll_safe_dice', _('Roll Safe Dice'), 'handleRollSafeDice' );
                        }
                        if (this.canHack()) {
                            this.addActionButton( 'button_hack' , _('Hack'), 'handleHack' );
                        }
                        this.addActionButton( 'button_pass', _('Pass'), 'handlePassClick' );
                        break;
                }
            }
        },
        
        setupPatrolItem: function(floor, card_div, card_type_id, card_id) {
            var key = 'patrol' + floor;
            card_div.innerText = this.gamedatas.patrol_types[key].cards[card_type_id % 16].name;
        },

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */
        // Get card unique identifier based on its row and col
        getCardUniqueId : function(type, index) {
            return parseInt(type, 10) * 100 + parseInt(index, 10);
        },

        getTileUniqueId : function(row, col) {
            return parseInt(row, 10) * 4 + parseInt(col, 10);
        },

        playTileOnTable : function(floor, tile) {
            var div_id = 'tile_' + tile.id;
            if ($(div_id)) {
                dojo.destroy(div_id);
            }
                
            var idx = parseInt(tile.location_arg, 10);
            var row = Math.floor(idx / 4);
            var col = idx % 4;
            var bg_row = Math.floor(tile.type_arg / 2) * -100;
            var bg_col = (tile.type_arg % 2) * -100;
            dojo.place(this.format_block('jstpl_tile', {
                id : tile.id, 
                x : (this.cardwidth + 40) * col,
                y : (this.cardheight + 40) * row,
                bg_image: g_gamethemeurl + 'img/tiles.jpg',
                bg_position: bg_col.toString() + '% ' + bg_row.toString() + '%',
                name : tile.type + tile.safe_die
            }), 'floor' + floor);
            
            dojo.connect( $(div_id), 'onclick', this, function(evt) {
                this.handleTileClick(evt, floor, tile.location_arg);
            });

            var zone = new ebg.zone();
            var zoneId = div_id + '_tokens';
            zone.create( this, zoneId, 30, 30 );
            zone.setPattern( 'grid' );
            this.zones[zoneId] = zone;
        },

        playWallOnTable : function(wall) {
            var div_id = 'wall_' + wall.id;
                
            var idx = parseInt(wall.position, 10);
            var row = Math.floor(idx / 3);
            var col = idx % 3;
            var x = wall.vertical == '1' ?  175 + (col * 190) : 10 + (row * 190);
            var y = wall.vertical == '1' ? 20 + (row * 190) : 185 + (col * 190);
            dojo.place(this.format_block('jstpl_wall', {
                wall_id : wall.id,
                wall_direction : wall.vertical == '1' ? 'vertical' : 'horizontal', 
                x : x,
                y : y
            }), 'floor' + wall.floor);
        },

        createPlayerToken: function(player_id) {
            dojo.place(this.format_block('jstpl_player_token', {
                player_id : player_id,
                player_color: this.gamedatas.players[player_id].color
            }), 'token_container');
        },

        moveToken: function(token_type, id, tile_id) {
            var zoneId = 'tile_' + tile_id + '_tokens';
            this.zones[zoneId].placeInZone(token_type + '_token_' + id);
        },

        createGuardToken: function(floor) {
            dojo.place(this.format_block('jstpl_guard_token', {
                guard_floor : floor,
            }), 'token_container');
        },

        createPatrolToken: function(floor, die_num) {
            dojo.place(this.format_block('jstpl_patrol_die', {
                guard_floor : floor,
                num_spaces : die_num,
            }), 'token_container');
        },

        createSafeToken: function(floor, die_num) {
            dojo.place(this.format_block('jstpl_safe_die', {
                safe_floor : floor,
                die_num : die_num,
            }), 'token_container');
        },

        createGenericToken: function(id, color, letter) {
            
            dojo.place(this.format_block('jstpl_generic_token', {
                token_id : id,
                token_color : color,
                token_letter : letter
            }), 'token_container');
        },

        createPlayerStealthToken: function(id, count) {
            dojo.place(this.format_block('jstpl_generic_token', {
                token_id : 'p' + id,
                token_color : 'darkcyan',
                token_letter : count
            }), 'player_board_' + id);
        },

        canAddSafeDie: function() {
            return this.gamedatas.current.tile.type === 'safe' &&
                this.gamedatas.current.actions_remaining >= 2;
        },

        canRollSafeDice: function() {
            return this.gamedatas.current.tile.type === 'safe';
        },

        canHack: function() {
            return this.gamedatas.current.tile.type.endsWith('-computer');
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

        handleTileClick: function(evt, floor, location_arg) {
            dojo.stopEvent(evt);

            var intent = this.intent || 'move';
            var url = '/burglebros/burglebros/' + intent + '.html';
            this.ajaxcall(url, { lock: true, floor: floor, location_arg: location_arg }, this, function() {
                location.reload();
            }, console.error);
            this.intent = 'move';
        },

        handleIntentClick: function(intent, evt) {
            dojo.stopEvent(evt);
            this.intent = intent;
        },

        handleAddSafeDie: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcall('/burglebros/burglebros/addSafeDie.html', { lock: true }, this, function() {
                console.log(arguments);
                location.reload();
            }, console.error);
        },

        handleRollSafeDice: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcall('/burglebros/burglebros/rollSafeDice.html', { lock: true }, this, function() {
                console.log(arguments);
                location.reload();
            }, console.error);
        },

        handleHack: function(evt) {
            dojo.stopEvent(evt);
            this.ajaxcall('/burglebros/burglebros/hack.html', { lock: true }, this, console.log, console.error);
        },

        handlePassClick: function(evt) {
            dojo.stopEvent(evt);

            var url = '/burglebros/burglebros/pass.html';
            this.ajaxcall(url, { lock: true }, this, function() {
                console.log(arguments);
                location.reload();
            }, console.error);
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
            dojo.subscribe('peek', this, 'notif_peek');
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
        notif_peek: function(notif) {
            var tiles = notif.args.tiles,
                floor = notif.args.floor
                deck = 'floor' + floor;
            this.gamedatas[deck] = tiles;
            for ( var tileId in this.gamedatas[deck]) {
                var tile = this.gamedatas[deck][tileId];
                this.playTileOnTable(floor, tile);
            }
       }
   });             
});
