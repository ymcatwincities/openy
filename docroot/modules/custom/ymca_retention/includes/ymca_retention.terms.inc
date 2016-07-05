<?php
/**
 * @file
 * Terms for YMCA Retention Activity tracker.
 */

use \Drupal\taxonomy\Entity\Term;

/**
 * Helper function to create taxonomy term.
 */
function _ymca_retention_term_create($values, $parent = NULL) {
  $term = Term::create([
    'vid' => 'ymca_retention_activities',
    'name' => $values['name'],
    'weight' => isset($values['weight']) ? $values['weight'] : 0,
    'parent' => $parent ? [$parent->id()] : [],
  ]);
  $term->save();
  if (!isset($values['terms'])) {
    return $term;
  }

  foreach ($values['terms'] as $child) {
    _ymca_retention_term_create($child, $term);
  }
  return $term;
}

/**
 * Returns YMCA retention activity tracker terms.
 */
function _ymca_retention_activity_tracker_terms() {
  return [
    [
      'name' => 'Swimming',
      'weight' => 0,
      'terms' => [
        [
          'name' => 'Swim laps',
        ],
        [
          'name' => 'Enjoy open swim (self)',
        ],
        [
          'name' => 'Open swim (w/ family or friends)',
        ],
        [
          'name' => 'Soak in hot tub',
        ],
        [
          'name' => 'Indoor water features (e.g. slide)',
        ],
        [
          'name' => 'Utilize outdoor splash pad',
        ],
      ],
    ],
    [
      'name' => 'Fitness',
      'weight' => 1,
      'terms' => [
        [
          'name' => 'Use one or more cardio equipment',
        ],
        [
          'name' => 'Use one or more weight equipment',
        ],
        [
          'name' => 'Stretching and mobility',
        ],
        [
          'name' => 'Work with a Personal Trainer',
        ],
        [
          'name' => 'Participate in paid Group Training Class',
        ],
      ],
    ],
    [
      'name' => 'Group Exercises',
      'weight' => 2,
      'terms' => [
        [
          'name' => '55+',
        ],
        [
          'name' => 'Cardio',
        ],
        [
          'name' => 'Cardio + Strength Combo',
        ],
        [
          'name' => 'Dance',
        ],
        [
          'name' => 'Kids and Family',
        ],
        [
          'name' => 'Pilates & Yoga',
        ],
        [
          'name' => 'Strength',
        ],
        [
          'name' => 'Water Exercise',
        ],
      ],
    ],
  ];
}
