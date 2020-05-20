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
            this.playerHand.image_items_per_row = 1;

            // Create cards types:
            for (var type in gamedatas.card_types) {
                var typeInfo = gamedatas.card_types[type];
                for (var index = 0; index < typeInfo.cards.length; index++) {
                    // Build card type id
                    var cardTypeId = this.getCardUniqueId(type, index);
                    this.playerHand.addItemType(cardTypeId, cardTypeId, g_gamethemeurl + 'img/vertical_cards.jpeg', cardTypeId);
                }
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
                this[patrolKey].image_items_per_row = 1;
                this[patrolKey].onItemCreate = dojo.hitch(this, 'setupPatrolItem', floor);
                console.log(this[patrolKey].jstpl_stock_item);

                for (var type in gamedatas.patrol_types) {
                    var typeInfo = gamedatas.patrol_types[type];
                    for (var index = 0; index < typeInfo.cards.length; index++) {
                        var cardInfo = typeInfo.cards[index];
                        this[patrolKey].addItemType(cardInfo.index, cardInfo.index, g_gamethemeurl + 'img/patrol.jpeg', cardInfo.index);
                    }
                }
                var patrolDeckKey = patrolKey + '_discard';
                for (var cardId in gamedatas[patrolDeckKey]) {
                    var card = gamedatas[patrolDeckKey][cardId];
                    this[patrolKey].addToStockWithId(card.type_arg, card.location_arg);
                }

                this.createGuardToken(floor);
                this.createPatrolToken(floor);
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
                if (token.location === 'tile') {
                    this.moveToken('patrol', token.type_arg, token.location_arg);
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
                        this.addActionButton( 'button_peek', _('Peek'), console.log );
                        this.addActionButton( 'button_move', _('Move'), console.log );
                        this.addActionButton( 'button_pass', _('Pass'), console.log );
                        break;
                }
            }
        },
        
        setupPatrolItem: function(floor, card_div, card_type_id, card_id) {
            var key = 'patrol' + floor;
            card_div.innerText = this.gamedatas.patrol_types[key].cards[card_type_id].name;
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
            dojo.place(this.format_block('jstpl_tile', {
                id : tile.id, 
                x : (this.cardwidth + 40) * col,
                y : (this.cardheight + 40) * row,
                name : tile.type
            }), 'floor' + floor);
            
            dojo.connect( $(div_id), 'onclick', this, function(evt) {
                this.handleTileClick(evt, floor, tile.location_arg);
            });

            var zone = new ebg.zone();
            var zoneId = div_id + '_tokens';
            zone.create( this, zoneId, 30, 30 );
            zone.setPattern( 'grid' );
            this.zones[zoneId] = zone;

            // if (player_id != this.player_id) {
            //     // Some opponent played a card
            //     // Move card from player panel
            //     this.placeOnObject('cardontable_' + player_id, 'overall_player_board_' + player_id);
            // } else {
            //     // You played a card. If it exists in your hand, move card from there and remove
            //     // corresponding item

            //     if ($('myhand_item_' + card_id)) {
            //         this.placeOnObject('cardontable_' + player_id, 'myhand_item_' + card_id);
            //         this.playerHand.removeFromStockById(card_id);
            //     }
            // }

            // // In any case: move it to its final destination
            // this.slideToObject('cardontable_' + player_id, 'playertablecard_' + player_id).play();
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

        createPatrolToken: function(floor) {
            dojo.place(this.format_block('jstpl_patrol_token', {
                guard_floor : floor,
                num_spaces : floor + 1,
            }), 'token_container');
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

            this.ajaxcall('/burglebros/burglebros/peek.html', { lock: true, floor: floor, location_arg: location_arg }, this, console.log, console.error);
        },

        handlePeekClick: function(evt) {

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
