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
 * material.inc.php
 *
 * burglebros game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/

$this->card_types = array(
  'tools' => array(
    array('name'=>'dynamite'),
  ),
  'loot' => array(
    array('name'=>'isotope'),
    array('name'=>'gold-bar','nbr'=>2),
  ),
  'events' => array(
    array('name'=>'throw-voice'),
    array('name'=>'buddy-system')
  )
  // Patrol cards are created programmatically
);

$this->tile_types = array(
  'safe' => 3,
  'stairs' => 3,
  'walkway' => 3,
  'laboratory' => 2,
  'lavatory' => 1,
  'service-duct' => 2,
  'secret-door' => 2,
  'fingerprint-computer' => 1,
  'laser-computer' => 1,
  'motion-computer' => 1,
  'camera' => 4,
  'laser' => 3,
  'motion' => 3,
  'detector' => 3,
  'fingerprint' => 3,
  'thermo' => 3,
  'keypad' => 3,
  'deadbolt' => 3,
  'foyer' => 2,
  'atrium' => 2
);
