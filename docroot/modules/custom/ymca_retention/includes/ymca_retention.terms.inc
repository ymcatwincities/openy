<?php

/**
 * @file
 * Terms for YMCA Retention Activity tracker.
 */

use \Drupal\taxonomy\Entity\Term;

/**
 * Helper function to create tracker activity terms.
 */
function _ymca_retention_terms_create() {
  foreach (_ymca_retention_activity_tracker_terms() as $weight => $values) {
    _ymca_retention_term_create($values, $weight);
  }
}

/**
 * Helper function to create taxonomy term.
 */
function _ymca_retention_term_create($values, $weight = NULL, $parent = NULL) {
  $term = Term::create([
    'vid' => 'ymca_retention_activities',
    'name' => $values['name'],
    'description' => [
      'value' => isset($values['description']) ? $values['description'] : '',
      'format' => 'full_html',
    ],
    'weight' => $weight ? $weight : 0,
    'parent' => $parent ? [$parent->id()] : [],
  ]);
  $term->save();
  if (!isset($values['terms'])) {
    return $term;
  }

  foreach ($values['terms'] as $weight => $child) {
    _ymca_retention_term_create($child, $weight, $term);
  }
  return $term;
}

/**
 * Returns YMCA retention activity tracker terms.
 */
function _ymca_retention_activity_tracker_terms() {
  return [
    [
      'name' => 'Swim',
      'description' => 'Aquatics, Stream Room, Sauna',
      'terms' => [
        [
          'name' => 'Indoor Pool',
        ],
        [
          'name' => 'Outdoor Pool',
        ],
        [
          'name' => 'Lap Pool Swimming',
        ],
        [
          'name' => 'Warm Water Leisure Pool',
          'description' => 'Shallow',
        ],
        [
          'name' => 'Splash Deck',
        ],
        [
          'name' => 'Whirlpool',
        ],
        [
          'name' => 'Water Slide Use',
          'description' => 'You or Family Members',
        ],
        [
          'name' => 'Sauna',
        ],
        [
          'name' => 'Steam Room',
        ],
        [
          'name' => 'Swim Lessons',
          'description' => 'You or Family Members',
        ],
        [
          'name' => 'Vortex Pool',
        ],
      ],
    ],
    [
      'name' => 'Fitness',
      'description' => 'Fitness Machines, Gym, PT & Paid Group X',
      'terms' => [
        [
          'name' => 'Cardio Machine',
          'description' => 'ex: Bikes, Treadmill, Elliptical, Stair Mill...',
        ],
        [
          'name' => 'Strengthening Machine',
        ],
        [
          'name' => 'Stretching/Mobility Equipment',
        ],
        [
          'name' => 'Weight Lifting Equipment',
        ],
        [
          'name' => 'Open Gym',
        ],
        [
          'name' => 'Indoor Track',
        ],
        [
          'name' => 'Fitness routine using the Youth/Family Fitness Cards',
        ],
        [
          'name' => 'Racquet/Handball Courts',
        ],
        [
          'name' => 'Climbing Wall',
        ],
        [
          'name' => 'Outdoor Trail',
        ],
        [
          'name' => 'Personal Training',
        ],
        [
          'name' => 'Buddy Training',
        ],
        [
          'name' => 'Paid Group Training',
        ],
      ],
    ],
    [
      'name' => 'Free Group X',
      'description' => 'Group Fitness, Kids & Family, Water Classes',
      'terms' => [
        [
          'name' => 'Group X Class',
        ],
        [
          'name' => 'Kids & Family Class',
        ],
        [
          'name' => 'Water Exercise Class',
        ],
      ],
    ],
  ];
}
