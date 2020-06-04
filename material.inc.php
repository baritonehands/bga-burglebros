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
  0 => array( 'name' => clienttranslate('characters'),'nametr' => self::_('characters') ),
  1 => array( 'name' => clienttranslate('tools'),'nametr' => self::_('tools') ),
  2 => array( 'name' => clienttranslate('loot'),'nametr' => self::_('loot') ),
  3 => array( 'name' => clienttranslate('events'),'nametr' => self::_('events') ),
);

$this->card_info = array(
  0 => array(
    array('name'=>'acrobat'),
    array('name'=>'hacker'),
    array('name'=>'hawk'),
    array('name'=>'juicer'),
    array('name'=>'peterman'),
    array('name'=>'raven'),
    array('name'=>'rigger'),
    array('name'=>'rook'),
    array('name'=>'spotter'),
  ),
  1 => array(
    array('name'=>'blueprints'),
    array('name'=>'crowbar'),
    array('name'=>'crystal-ball'),
    array('name'=>'donuts'),
    array('name'=>'dynamite'),
    array('name'=>'emp'),
    array('name'=>'invisible-suit'),
    array('name'=>'makeup-kit'),
    array('name'=>'rollerskates'),
    array('name'=>'smoke-bomb'),
    array('name'=>'stethoscope'),
    array('name'=>'thermal-bomb'),
    array('name'=>'virus'),
  ),
  2 => array(
    array('name'=>'bust'),
    array('name'=>'chihuahua'),
    array('name'=>'cursed-goblet'),
    array('name'=>'gemstone'),
    array('name'=>'gold-bar','nbr'=>2),
    array('name'=>'isotope'),
    array('name'=>'keycard'),
    array('name'=>'mirror'),
    array('name'=>'painting'),
    array('name'=>'persian-kitty'),
    array('name'=>'stamp'),
    array('name'=>'tiara'),
  ),
  3 => array(
    array('name'=>'brown-out'),
    array('name'=>'buddy-system'),
    array('name'=>'change-of-plans'),
    array('name'=>'crash'),
    array('name'=>'daydreaming'),
    array('name'=>'dead-drop'),
    array('name'=>'espresso'),
    array('name'=>'freigh-elevator'),
    array('name'=>'go-with-your-gut'),
    array('name'=>'gymnastics'),
    array('name'=>'heads-up'),
    array('name'=>'jump-the-gun'),
    array('name'=>'jury-rig'),
    array('name'=>'keycode-change'),
    array('name'=>'lampshade'),
    array('name'=>'lost-grip'),
    array('name'=>'peekhole'),
    array('name'=>'reboot'),
    array('name'=>'shift-change'),
    array('name'=>'shoplifting'),
    array('name'=>'squeak'),
    array('name'=>'switch-signs'),
    array('name'=>'throw-voice'),
    array('name'=>'time-lock'),
    array('name'=>'video-loop'),
    array('name'=>'where-is-he'),
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

// Name to safe dice numbers
$this->tile_types = array(
  'atrium' => array(3, 4),
  'camera' => array(1, 2, 3, 6),
  'fingerprint-computer' => array(4),
  'laser-computer' => array(5),
  'motion-computer' => array(6),
  'deadbolt' => array(1, 2, 3),
  'detector' => array(4, 5, 6),
  'fingerprint' => array(4, 5, 6),
  'foyer' => array(1, 2),
  'keypad' => array(4, 5, 6),
  'laboratory' => array(3, 4),
  'laser' => array(1, 2, 3),
  'lavatory' => array(5),
  'motion' => array(1, 2, 3),
  'safe' => array(0, 0, 0),
  'secret-door' => array(1, 2),
  'service-duct' => array(5, 6),
  'stairs' => array(4, 5, 6),
  'thermo' => array(1, 2, 3),
  'walkway' => array(1, 2, 3),
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

$this->token_colors = array(
  'hack' => 'purple',
  'safe' => 'green',
  'stealth' => 'darkcyan',
  'alarm' => 'darkred',
  'open' => 'gold',
  'keypad' => 'gold',
  'stairs' => 'slategray',
  'thermal' => 'green'
);
