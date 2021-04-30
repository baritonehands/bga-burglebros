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
    array('name'=>'acrobat1', 'choice_description' => self::_('an adjacent tile containing a guard'), 'subhead' => clienttranslate('Retired Performer'), 'title' => clienttranslate('The Acrobat'), 'ability' => clienttranslate('Flexibility'), 'tooltip' => clienttranslate('You may move into a tile with a Guard as a free action and you don\'t use a Stealth when you do. You must leave before the Guard moves or lose a Stealth.')),
    array('name'=>'acrobat2', 'choice_description' => self::_('an option'), 'subhead' => clienttranslate('Retired Performer'), 'title' => clienttranslate('The Acrobat'), 'ability' => clienttranslate('Climb Window'), 'tooltip' => clienttranslate('If you are on an outer tile, you may spend 3 actions to move up or down 1 floor. This ends your actions.')),
    array('name'=>'hacker1', 'subhead' => clienttranslate('Computer Guy'), 'title' => clienttranslate('The Hacker'), 'ability' => clienttranslate('Jammer'), 'tooltip' => clienttranslate('You do not trigger Fingerprint, Laser or Motion tiles. Other players will not trigger them while you are there.')),
    array('name'=>'hacker2', 'subhead' => clienttranslate('Computer Guy'), 'title' => clienttranslate('The Hacker'), 'ability' => clienttranslate('Laptop'), 'tooltip' => clienttranslate('You can add a hack token to yourself for one action (limit 1). This token can be used as a laser, motion or fingerprint hack token by any player.')),
    array('name'=>'hawk1', 'choice_description' => self::_('an adjacent tile behind a wall to peek'), 'subhead' => clienttranslate('Recon Pro'), 'title' => clienttranslate('The Hawk'), 'ability' => clienttranslate('X-Ray'), 'tooltip' => clienttranslate('Once per turn as a free action you may peak at an adjacent tile through a wall.')),
    array('name'=>'hawk2', 'choice_description' => self::_('a tile up to two spaces away to peek'), 'subhead' => clienttranslate('Recon Pro'), 'title' => clienttranslate('The Hawk'), 'ability' => clienttranslate('Enhance'), 'tooltip' => clienttranslate('As a free action once per turn, you may peak at a tile up to two spaces away. You may not skip over an unrevealed tile. You cannot see through walls, but you can see around corners and up stairs.')),
    array('name'=>'juicer1', 'choice_description' => self::_('an adjacent tile to trigger an alarm'), 'subhead' => clienttranslate('Electronics Expert'), 'title' => clienttranslate('The Juicer'), 'ability' => clienttranslate('Crybaby'), 'tooltip' => clienttranslate('As a free action, you may create an alarm in the adjacent tile (but not through walls).')),
    array('name'=>'juicer2', 'subhead' => clienttranslate('Electronics Expert'), 'title' => clienttranslate('The Juicer'), 'ability' => clienttranslate('Reroute'), 'tooltip' => clienttranslate('Once per turn as a free action, you may pick up an active alarm on your tile and draw a new patrol card OR discard you alarm token to trigger an alarm (limit 1).')),
    array('name'=>'peterman1', 'subhead' => clienttranslate('Safecraker'), 'title' => clienttranslate('The Peterman'), 'ability' => clienttranslate('Steady Hands'), 'tooltip' => clienttranslate('When rolling for the Safe or Keypad, roll 1 additional die.')),
    array('name'=>'peterman2', 'choice_description' => self::_('up or down to crack safe'), 'subhead' => clienttranslate('Safecraker'), 'title' => clienttranslate('The Peterman'), 'ability' => clienttranslate('Drill'), 'tooltip' => clienttranslate('You may add dice and roll on safes above or below your tile, but cannot pick up loot or tools from those safes.')),
    array('name'=>'raven1', 'choice_description' => self::_('a tile up to two spaces away to place the crow'), 'subhead' => clienttranslate('Maverick Falconer'), 'title' => clienttranslate('The Raven'), 'ability' => clienttranslate('Distract'), 'tooltip' => clienttranslate('As a free action, you may place the crow token up to two tiles away from your character (not through walls). If the guard enters a tile with a crow, he loses one movement. The crow remains in that location until you move it again.')),
    array('name'=>'raven2', 'subhead' => clienttranslate('Maverick Falconer'), 'title' => clienttranslate('The Raven'), 'ability' => clienttranslate('Disrupt'), 'tooltip' => clienttranslate('As a free action, you may place the crow token on your current tile. If the Guard starts his movement on the same tile as the crow, AND there are no alarms, he loses all movement, and the crow is returned to you.')),
    array('name'=>'rigger1', 'subhead' => clienttranslate('Tinkerer Savant'), 'title' => clienttranslate('The Rigger'), 'ability' => clienttranslate('The Solution'), 'tooltip' => clienttranslate('You start with the Dynamite Tool. When any player finds a Tool, they may draw two Tool cards, keep one and discard the other.')),
    array('name'=>'rigger2', 'subhead' => clienttranslate('Tinkerer Savant'), 'title' => clienttranslate('The Rigger'), 'ability' => clienttranslate('Tinker'), 'tooltip' => clienttranslate('You can discard a Stealth token to draw a Tool. When any player finds a Tool, they may draw two Tool cards, keep one and discard the other.')),
    array('name'=>'rook1', 'choice_description' => self::_('a player token to move'), 'subhead' => clienttranslate('Mastermind'), 'title' => clienttranslate('The Rook'), 'ability' => clienttranslate('Orders'), 'tooltip' => clienttranslate('Once per turn you may spend an action to move another player one tile. Ignore move costs such as those from Deadbolt and Laser. Follow all other normal movement rules.')),
    array('name'=>'rook2', 'choice_description' => self::_('a player token to trade places'), 'subhead' => clienttranslate('Mastermind'), 'title' => clienttranslate('The Rook'), 'ability' => clienttranslate('Disguise'), 'tooltip' => clienttranslate('You may spend one action to trade places with any player. This does not count as entering the tile for either of you.')),
    array('name'=>'spotter1', 'choice_description' => self::_('to place card on top or bottom of deck'), 'subhead' => clienttranslate('Psychic Gone Rogue'), 'title' => clienttranslate('The Spotter'), 'ability' => clienttranslate('Clairvoyance'), 'tooltip' => clienttranslate('Once per turn, you may spend one action to look at the top of the Patrol deck for your floor. Choose to place it on the top or bottom of the deck.')),
    array('name'=>'spotter2', 'choice_description' => self::_('to place card on top or bottom of deck'), 'subhead' => clienttranslate('Psychic Gone Rogue'), 'title' => clienttranslate('The Spotter'), 'ability' => clienttranslate('Precognition'), 'tooltip' => clienttranslate('Once per turn, you may spend one action to look at the top of the Event deck. Choose to place it on the top or bottom of the deck.')),
  ),
  1 => array(
    array('name'=>'blueprints', 'choice_description' => self::_('any tile to peek'), 'title' => clienttranslate('Blueprints'), 'tooltip' => clienttranslate('Discard to peek at any one tile on any floor.')),
    array('name'=>'crowbar', 'choice_description' => self::_('an adjacent tile to disable'), 'title' => clienttranslate('Crowbar'), 'tooltip' => clienttranslate('Discard to permanently disable an adjacent tile. It can no longer block movement or trigger alarms.')),
    array('name'=>'crystal-ball', 'choice_description' => self::_('to reorder the 3 upcoming events'), 'title' => clienttranslate('Crystal Ball'), 'tooltip' => clienttranslate('Discard to look at the top 3 events. Put them back in any order.')),
    array('name'=>'donuts', 'choice_description' => self::_('any guard to lose all movement for one turn'), 'title' => clienttranslate('Donuts'), 'tooltip' => clienttranslate('Place the donuts under any guard. Next time that guard would move, he instead loses all movement and the donuts are discarded.')),
    array('name'=>'dynamite', 'choice_description' => self::_('an adjacent wall to remove'), 'title' => clienttranslate('Dynamite'), 'tooltip' => clienttranslate('Discard to destroy a wall adjacent to you that players and guard may pass through. Trigger an alarm in the player\s current tile.')),
    array('name'=>'emp', 'title' => clienttranslate('E.M.P.'), 'tooltip' => clienttranslate('Remove all alarms from all floors. No alarms triggered on any floor until your next turn. Discard at the start of your next turn.')),
    array('name'=>'invisible-suit', 'title' => clienttranslate('Invisible Suit'), 'tooltip' => clienttranslate('Discard to not be seen by Guards or Cameras while moving and gain one additional action this turn.')),
    array('name'=>'makeup-kit', 'title' => clienttranslate('Makeup Kit'), 'tooltip' => clienttranslate('Discard to give all players on your current tile a Stealth token.')),
    array('name'=>'rollerskates', 'title' => clienttranslate('Rollerskates'), 'tooltip' => clienttranslate('Discard togain two additional actions this turn.')),
    array('name'=>'smoke-bomb', 'title' => clienttranslate('Smoke Bomb'), 'tooltip' => clienttranslate('Discard to add three Stealth tokens to the current tile. These tokens may only be used in this room.')),
    array('name'=>'stethoscope', 'choice_description' => self::_('if you want to change the result of one die using the Stethoscope'), 'title' => clienttranslate('Stethoscope'), 'tooltip' => clienttranslate('Discard after a cracking attempt to change the result of one die to any side you wish.')),
    array('name'=>'thermal-bomb', 'choice_description' => self::_('up or down to create stairs'), 'title' => clienttranslate('Thermal Bomb'), 'tooltip' => clienttranslate('Discard to make stairs up or down from current tile. Mark with a stair token. Trigger alarm in the current player\'s tile.')),
    array('name'=>'virus', 'choice_description' => self::_('a computer to add hack tokens'), 'title' => clienttranslate('Virus'), 'tooltip' => clienttranslate('Discard to add three hack tokens to any computer room.')),
  ),
  2 => array(
    array('name'=>'bust', 'title' => clienttranslate('Bust'), 'tooltip' => clienttranslate('You may not use tools while holding the Bust.')),
    array('name'=>'chihuahua', 'title' => clienttranslate('Chihuahua'), 'tooltip' => clienttranslate('Each turn, roll a die. If 6, trigger an alarm on your tile.')),
    array('name'=>'cursed-goblet', 'title' => clienttranslate('Cursed Gobelet'), 'tooltip' => clienttranslate('Player who draws the Cursed Gobelet looses one Stealth.')),
    array('name'=>'gemstone', 'title' => clienttranslate('Gemstone'), 'tooltip' => clienttranslate('Pay an extra action to enter a tile occupied by another player.')),
    array('name'=>'gold-bar','nbr'=>2, 'title' => clienttranslate('Gold Bar'), 'tooltip' => clienttranslate('Find the other Gold Bar, put in play. Only one can be carried per player.')),
    array('name'=>'isotope', 'title' => clienttranslate('Isotope'), 'tooltip' => clienttranslate('Trigger an alarm when entering a Thermo tile while holding the Isotope.')),
    array('name'=>'keycard', 'title' => clienttranslate('Keycard'), 'tooltip' => clienttranslate('Holder must be present to roll dice for cracking any safe.')),
    array('name'=>'mirror', 'title' => clienttranslate('Mirror'), 'tooltip' => clienttranslate('-1 action while holding the Mirror. Holder does not trigger Laser alarms.')),
    array('name'=>'painting', 'title' => clienttranslate('Painting'), 'tooltip' => clienttranslate('Holder may not travel through Secret Doors or Service Ducts.')),
    array('name'=>'persian-kitty', 'title' => clienttranslate('Persian Kitty'), 'tooltip' => clienttranslate('Each turn roll a die. If 1 or 2, Kitty moves 1 tile towards the nearest alarm.')),
    array('name'=>'stamp', 'title' => clienttranslate('Stamp'), 'tooltip' => clienttranslate('When 3 actions or fewer are used by holder, trigger an event.')),
    array('name'=>'tiara', 'title' => clienttranslate('Tiara'), 'tooltip' => clienttranslate('Guards will see you from adjacent tiles while you are moving.')),
  ),
  3 => array(
    array('name'=>'brown-out', 'title' => clienttranslate('Brown Out'), 'tooltip' => clienttranslate('Alarm tokens on all floors are removed. Draw a new Patrol card for each alarm removed.')),
    array('name'=>'buddy-system', 'choice_description' => self::_('a player token to move to your tile'), 'title' => clienttranslate('Buddy System'), 'tooltip' => clienttranslate('Choose a player. Move their piece onto your current tile. Does not count as entering.')),
    array('name'=>'change-of-plans', 'title' => clienttranslate('Change of plans'), 'tooltip' => clienttranslate('Activate the next Patrol card on your floor.')),
    array('name'=>'crash', 'title' => clienttranslate('Crash!'), 'tooltip' => clienttranslate('Set Guard destination on your floor to your tile.')),
    array('name'=>'daydreaming', 'title' => clienttranslate('Daydreaming'), 'tooltip' => clienttranslate('The Guard on your floor has one less movement this turn.')),
    array('name'=>'dead-drop', 'title' => clienttranslate('Dead drop'), 'tooltip' => clienttranslate('Current player passes all tools and loot to the player on their right.')),
    array('name'=>'espresso', 'title' => clienttranslate('Expresso'), 'tooltip' => clienttranslate('The Guard on your floor has one additional movement this turn.')),
    array('name'=>'freight-elevator', 'title' => clienttranslate('Freight elevator'), 'tooltip' => clienttranslate('Fall up one floor. Does not count as entering the tile.')),
    array('name'=>'go-with-your-gut', 'choice_description' => self::_('an adjacent unexplored tile to move to'), 'title' => clienttranslate('Go with your gut'), 'tooltip' => clienttranslate('If you are adjacent to an explored tile, move into it now. Choose if there is more than one.')),
    array('name'=>'gymnastics', 'title' => clienttranslate('Gymnastics'), 'tooltip' => clienttranslate('Walkay tiles act as stairs for one round. Leave this in front of you and remove it at the start of your next turn.')),
    array('name'=>'heads-up', 'title' => clienttranslate('Heads up!'), 'tooltip' => clienttranslate('The next player gains an additional action on their turn.')),
    array('name'=>'jump-the-gun', 'title' => clienttranslate('Jump the gun'), 'tooltip' => clienttranslate('Skip the next player\'s turn (including Guard Movement).')),
    array('name'=>'jury-rig', 'title' => clienttranslate('Jury-rig'), 'tooltip' => clienttranslate('Draw a tool.')),
    array('name'=>'keycode-change', 'title' => clienttranslate('Keycode change'), 'tooltip' => clienttranslate('Any open keypad tiles are now locked again. Roll a 6 to enter and re-open.')),
    array('name'=>'lampshade', 'title' => clienttranslate('Lampshade'), 'tooltip' => clienttranslate('Gain a Stealth.')),
    array('name'=>'lost-grip', 'title' => clienttranslate('Lost grip'), 'tooltip' => clienttranslate('Gall one floor. This does not count as entering a tile.')),
    array('name'=>'peekhole', 'choice_description' => self::_('an adjacent tile (also through a wall or up/down floors) to peek'), 'title' => clienttranslate('Peekhole'), 'tooltip' => clienttranslate('You may peek at one adjacent tile, even through a wall or up/down floors. Resolve immediately.')),
    array('name'=>'reboot', 'title' => clienttranslate('Reboot'), 'tooltip' => clienttranslate('Set Hacks on any computer rooms to one token.')),
    array('name'=>'shift-change', 'title' => clienttranslate('Shift change'), 'tooltip' => clienttranslate('Guard does not move on your floor. Instead, Guards on the other floors move this turn (if revealed).')),
    array('name'=>'shoplifting', 'title' => clienttranslate('Shoplifting'), 'tooltip' => clienttranslate('Alarms are triggered on all laboratory tiles that have had tools taken from them.')),
    array('name'=>'squeak', 'title' => clienttranslate('Squeak!'), 'tooltip' => clienttranslate('Move the Guard on your floor one tile towards the nearest character.')),
    array('name'=>'switch-signs', 'title' => clienttranslate('Switch signs'), 'tooltip' => clienttranslate('The Guard on your floor and his destination swap positions.')),
    array('name'=>'throw-voice', 'choice_description' => self::_('an adjacent tile to move the guard destination'), 'title' => clienttranslate('Throw voice'), 'tooltip' => clienttranslate('Move the Guard destination into an adjacent tile from its current location.')),
    array('name'=>'time-lock', 'title' => clienttranslate('Time lock'), 'tooltip' => clienttranslate('Players cannot move up or down through stairs for one round. Leave this in front of you and remove at the start of your next turn.')),
    array('name'=>'video-loop', 'title' => clienttranslate('Video loop'), 'tooltip' => clienttranslate('All camera tiles are disabled for one round. Leave this in front of you and remove at the start of your next turn.')),
    array('name'=>'where-is-he', 'title' => clienttranslate('Where is he?'), 'tooltip' => clienttranslate('Guard on you floor jumps to his current destination.')),
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
  array('name' => 'diamond', 'color' => '#DDA860'),
  array('name' => 'crowbar', 'color' => '#74B189'),
  array('name' => 'keypad', 'color' => '#DDA860'),
);

$this->player_choices = array('none', 'trade', 'rook1', 'rook2', 'squeak');

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
