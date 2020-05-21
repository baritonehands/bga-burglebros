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
  1 => array( 'name' => clienttranslate('tools'),'nametr' => self::_('tools') ),
  2 => array( 'name' => clienttranslate('loot'),'nametr' => self::_('loot') ),
  3 => array( 'name' => clienttranslate('events'),'nametr' => self::_('events') ),
);

$this->card_info = array(
  1 => array(
    array('name'=>'dynamite'),
    array('name'=>'stethoscope'),
    array('name'=>'donuts'),
  ),
  2 => array(
    array('name'=>'isotope'),
    array('name'=>'gold-bar','nbr'=>2),
  ),
  3 => array(
    array('name'=>'throw-voice'),
    array('name'=>'buddy-system')
  ),
);

$this->patrol_types = array(
  4 => array( 'name' => clienttranslate('patrol1'),'nametr' => self::_('patrol1') ),
  5 => array( 'name' => clienttranslate('patrol2'),'nametr' => self::_('patrol2') ),
  6 => array( 'name' => clienttranslate('patrol3'),'nametr' => self::_('patrol3') ),
);

$this->patrol_names = array(
  array('name'=>'a1'),
  array('name'=>'b1'),
  array('name'=>'c1'),
  array('name'=>'d1'),
  array('name'=>'a2'),
  array('name'=>'b2'),
  array('name'=>'c2'),
  array('name'=>'d2'),
  array('name'=>'a3'),
  array('name'=>'b3'),
  array('name'=>'c3'),
  array('name'=>'d3'),
  array('name'=>'a4'),
  array('name'=>'b4'),
  array('name'=>'c4'),
  array('name'=>'d4'),
);

$this->patrol_info = array(
  4 => $this->patrol_names,
  5 => $this->patrol_names,
  6 => $this->patrol_names,
);

$this->tile_types = array(
  'back' => 1,
  'atrium' => 2,
  'camera' => 4,
  'fingerprint-computer' => 1,
  'laser-computer' => 1,
  'motion-computer' => 1,
  'deadbolt' => 3,
  'detector' => 3,
  'fingerprint' => 3,
  'foyer' => 2,
  'keypad' => 3,
  'laboratory' => 2,
  'laser' => 3,
  'lavatory' => 1,
  'motion' => 3,
  'safe' => 3,
  'secret-door' => 2,
  'service-duct' => 2,
  'stairs' => 3,
  'thermo' => 3,
  'walkway' => 3,
);

$this->default_walls = array(
  1 => array(
    'vertical' => array(0, 5, 9, 10),
    'horizontal' => array(1, 4, 6, 11)
  ),
  2 => array(
    'vertical' => array(0, 1, 2, 9, 10, 11),
    'horizontal' => array(4, 7)
  ),
  3 => array(
    'vertical' => array(3, 5, 6, 7, 11),
    'horizontal' => array(1, 6, 7)
  )
);
