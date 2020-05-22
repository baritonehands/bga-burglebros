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

<div id="token_container">
    <h3>Tokens</h3>
</div>

<div id="board_wrap" class="whiteblock">
    <!-- BEGIN floor -->
    <div class="floor_container">
        <h3>Floor {FLOOR}</h3>
        <div class="floor" id="floor{FLOOR}">
        </div>
        <h4>Patrol {FLOOR}</h4>
        <div class="patrol" id="patrol{FLOOR}">
        </div>
    </div>
    <!-- END floor -->
</div>


<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/
var jstpl_tile = '<div id="tile_${id}" class="tile" style="background-image: url(${bg_image}); background-position: ${bg_position}; left: ${x}px; top: ${y}px" aria-label="${name}" title="${name}">${name}<div id="tile_${id}_tokens" class="tile-zone"></div></div>';

var jstpl_wall = '<div id="wall_${wall_id}" class="wall ${wall_direction}" style="left: ${x}px; top: ${y}px"></div>';

var jstpl_player_token = '<div id="player_token_${player_id}" class="token" style="background-color: #${player_color}">P</div>';

var jstpl_guard_token = '<div id="guard_token_${guard_floor}" class="token" style="background-color: black;">G</div>';

var jstpl_generic_token = '<div id="generic_token_${token_id}" class="token" style="background-color: ${token_color};">${token_letter}</div>';

var jstpl_patrol_die = '<div id="patrol_token_${guard_floor}" class="token die patrol">${num_spaces}</div>';

var jstpl_safe_die = '<div id="safe_token_${safe_floor}" class="token die safe">${die_num}</div>';

</script>  

{OVERALL_GAME_FOOTER}
