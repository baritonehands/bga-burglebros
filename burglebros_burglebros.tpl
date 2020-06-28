{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- burglebros implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    burglebros_burglebros.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->

<div id="myhand_wrap" class="whiteblock">
    <h3>{MY_HAND}</h3>
    <div id="myhand">
    </div>
</div>

<div id="tooltip_debug"><div>

<div id="board_wrap">
    <div class="floor_container whiteblock">
        
        <div  class="tiles">
            <!-- BEGIN tiles -->
            <div id="floor{FLOOR}_tiles" class="floor_tiles">
                <h3>Floor {FLOOR}</h3>
                <div class="floor" id="floor{FLOOR}">
                </div>
            </div>
            <!-- END tiles -->
        </div>
        
        <div class="patrols">
            <h3>Patrol</h3>
            <!-- BEGIN patrol -->
            <div class="patrol" id="patrol{FLOOR}">
            </div>
            <!-- END patrol -->
            <div class="floor_preview_container">
                <!-- BEGIN floor_preview -->
                <div class="floor_preview" id="floor{FLOOR}_preview">
                {FLOOR}
                </div>
                <!-- END floor_preview -->
            </div>
        </div>
    </div>
</div>

<!-- BEGIN player_hand -->
<div class="player_hand whiteblock">
    <h3 style="color: #{PLAYER_COLOR};">{PLAYER_NAME}</h3>
    <div id="player_hand_{PLAYER_ID}">
    </div>
</div>
<!-- END player_hand -->

<div id="token_container" style="display: none;">
</div>


<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/
var jstpl_tile_container = '<div id="tile_${id}_container" class="tile-container" style="left: ${x}px; top: ${y}px;" aria-label="${name}" title="${name}">\n' +
'    <div id="tile_${id}_tokens" class="tile-zone"></div>\n' +
'</div>';

var jstpl_tile = '<div id="tile_${id}" class="tile" style="background-image: url(${bg_image}); background-position: ${bg_position};"></div>';

var jstpl_tile_tooltip = '<div id="tile_${id}_tooltip" class="tile tooltip" style="background-image: url(${bg_image}); background-position: ${bg_position};"></div>';

var jstpl_card_tooltip = '<div id="card_${id}_tooltip" class="card tooltip" style="background-image: url(${bg_image}); background-position: ${bg_position};"></div>';

var jstpl_patrol_tooltip = '<div id="patrol_tooltip_${patrol_floor}" class="card tooltip" style="background-image: url(${bg_image}); background-position: ${bg_position}; background-size: 1440px;">${patrol_discards}</div>';

var jstpl_patrol_tooltip_discard = '<div class="patrol-discard" style="left: ${discard_left}px; top: ${discard_top}px; background-image: url(${bg_image});"></div>'

var jstpl_wall = '<div id="wall_${wall_id}" class="wall ${wall_direction}" style="left: ${x}px; top: ${y}px"></div>';

var jstpl_player_token = '<div id="player_token_${token_id}" class="token" style="background-color: #${player_color}">P</div>';

var jstpl_guard_token = '<div id="guard_token_${token_id}" class="token" style="background-color: black;">G</div>';

var jstpl_generic_token = '<div id="generic_token_${token_id}" class="token" style="background-color: ${token_color};">${token_letter}</div>';

var jstpl_patrol_die = '<div id="patrol_token_${token_id}" class="token die patrol">${num_spaces}</div>';

var jstpl_safe_die = '<div id="crack_token_${token_id}" class="token die safe">${die_num}</div>';

var jstpl_event_card = '<div id="event_card_dialog" class="card" style="background-image: url(${bg_image}); background-position: ${bg_position};"></div>'

</script>  

{OVERALL_GAME_FOOTER}
