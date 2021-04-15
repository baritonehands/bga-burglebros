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
    array('name'=>'acrobat1', 'choice_description' => self::_('an adjacent tile containing a guard')),
    array('name'=>'acrobat2', 'choice_description' => self::_('an option')),
    array('name'=>'hacker1'),
    array('name'=>'hacker2'),
    array('name'=>'hawk1', 'choice_description' => self::_('an adjacent tile behind a wall to peek')),
    array('name'=>'hawk2', 'choice_description' => self::_('a tile up to two spaces away to peek')),
    array('name'=>'juicer1', 'choice_description' => self::_('an adjacent tile to trigger an alarm')),
    array('name'=>'juicer2'),
    array('name'=>'peterman1'),
    array('name'=>'peterman2', 'choice_description' => self::_('up or down to crack safe')),
    array('name'=>'raven1', 'choice_description' => self::_('a tile up to two spaces away to place the crow')),
    array('name'=>'raven2'),
    array('name'=>'rigger1'),
    array('name'=>'rigger2'),
    array('name'=>'rook1', 'choice_description' => self::_('a player token to move')),
    array('name'=>'rook2', 'choice_description' => self::_('a player token to trade places')),
    array('name'=>'spotter1', 'choice_description' => self::_('to place card on top or bottom of deck')),
    array('name'=>'spotter2', 'choice_description' => self::_('to place card on top or bottom of deck')),
  ),
  1 => array(
    array('name'=>'blueprints', 'choice_description' => self::_('any tile to peek')),
    array('name'=>'crowbar', 'choice_description' => self::_('an adjacent tile to disable')),
    array('name'=>'crystal-ball', 'choice_description' => self::_('to reorder the 3 upcoming events')),
    array('name'=>'donuts', 'choice_description' => self::_('any guard to lose all movement for one turn')),
    array('name'=>'dynamite', 'choice_description' => self::_('an adjacent wall to remove')),
    array('name'=>'emp'),
    array('name'=>'invisible-suit'),
    array('name'=>'makeup-kit'),
    array('name'=>'rollerskates'),
    array('name'=>'smoke-bomb'),
    array('name'=>'stethoscope', 'choice_description' => self::_('if you want to change the result of one die using the Stethoscope')),
    array('name'=>'thermal-bomb', 'choice_description' => self::_('up or down to create stairs')),
    array('name'=>'virus', 'choice_description' => self::_('a computer to add hack tokens')),
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
    array('name'=>'buddy-system', 'choice_description' => self::_('a player token to move to your tile')),
    array('name'=>'change-of-plans'),
    array('name'=>'crash'),
    array('name'=>'daydreaming'),
    array('name'=>'dead-drop'),
    array('name'=>'espresso'),
    array('name'=>'freight-elevator'),
    array('name'=>'go-with-your-gut', 'choice_description' => self::_('an adjacent unexplored tile to move to')),
    array('name'=>'gymnastics'),
    array('name'=>'heads-up'),
    array('name'=>'jump-the-gun'),
    array('name'=>'jury-rig'),
    array('name'=>'keycode-change'),
    array('name'=>'lampshade'),
    array('name'=>'lost-grip'),
    array('name'=>'peekhole', 'choice_description' => self::_('an adjacent tile (also through a wall or up/down floors) to peek')),
    array('name'=>'reboot'),
    array('name'=>'shift-change'),
    array('name'=>'shoplifting'),
    array('name'=>'squeak', 'choice_description' => self::_('a player token closest to the guard on your floor')),
    array('name'=>'switch-signs'),
    array('name'=>'throw-voice', 'choice_description' => self::_('an adjacent tile to move the guard destination')),
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
  array('name'=>'A1'),
  array('name'=>'B1'),
  array('name'=>'C1'),
  array('name'=>'D1'),
  array('name'=>'A2'),
  array('name'=>'B2'),
  array('name'=>'C2'),
  array('name'=>'D2'),
  array('name'=>'A3'),
  array('name'=>'B3'),
  array('name'=>'C3'),
  array('name'=>'D3'),
  array('name'=>'A4'),
  array('name'=>'B4'),
  array('name'=>'C4'),
  array('name'=>'D4'),
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

$this->token_types = array(
  array('name' => 'alarm', 'color' => '#CE5638'),
  array('name' => 'cat', 'color' => '#8E8644'),
  array('name' => 'safe', 'color' => '#74B189'),
  array('name' => 'crow', 'color' => '#C9C0BD'),
  array('name' => 'hack', 'color' => '#C6A7BE'),
  array('name' => 'open', 'color' => '#DDA860'),
  array('name' => 'stairs', 'color' => '#86939D'),
  array('name' => 'stealth', 'color' => '#568F9F'),
  array('name' => 'thermal', 'color' => '#74B189'),
  array('name' => 'keypad', 'color' => '#DDA860'),
  array('name' => 'crowbar', 'color' => '#74B189')
);

$this->player_choices = array('none', 'trade', 'rook1', 'rook2');

$this->special_choices = array('none', 'rook1');

$this->clockwise_mappings = [
  'LU' => [
    'DL' => 'D',
    'LU' => 'L',
    'RU' => 'R',
    'DR' => 'D',
    'DU' => 'D',
    'LR' => 'L',
  ],
  'DL' => [
    'DL' => 'D',
    'LU' => 'L',
    'RU' => 'R',
    'DR' => 'D',
    'DU' => 'D',
    'LR' => 'R',
  ],
  'RU' => [
    'DL' => 'L',
    'LU' => 'U',
    'RU' => 'U',
    'DR' => 'R',
    'DU' => 'U',
    'LR' => 'L',
  ],
  'DR' => [
    'DL' => 'L',
    'LU' => 'U',
    'RU' => 'U',
    'DR' => 'R',
    'DU' => 'U',
    'LR' => 'R',
  ],
  'U' => [
    'DL' => 'L',
    'LU' => 'L',
    'RU' => 'U',
    'DR' => 'D',
    'LR' => 'L',
  ],
  'D' => [
    'DL' => 'D',
    'LU' => 'U',
    'RU' => 'R',
    'DR' => 'R',
    'LR' => 'R',
  ],
  'L' => [
    'DL' => 'D',
    'LU' => 'L',
    'RU' => 'R',
    'DR' => 'D',
    'DU' => 'D',
  ],
  'R' => [
    'DL' => 'L',
    'LU' => 'U',
    'RU' => 'U',
    'DR' => 'R',
    'DU' => 'U'
  ],
];
