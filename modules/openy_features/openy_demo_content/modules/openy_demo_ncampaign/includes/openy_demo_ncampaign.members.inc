<?php

/**
 * @file
 * Generate fake members and activities.
 *
 */

use Drupal\openy_campaign\Entity\Member;
//use Drupal\openy_campaign\Entity\MemberActivity;
use Drupal\taxonomy\TermStorage;

/**
 * Helper function to create fake members and activities.
 */
function _openy_demo_ncampaign_members_create($options) {
  $count = 1;
  foreach (_openy_demo_ncampaign_fake_members() as $i => $fake_member) {
    //$location_repository = \Drupal::service('ymca_mappings.location_repository');

    /** @var Member $member */
    $member = \Drupal::entityTypeManager()
      ->getStorage('openy_campaign_member')
      ->create([
        'membership_id' => /*'1234567890' . */$fake_member['membership_id'],
        'mail' => 'test' . $i . '@test.com',
        'first_name' => $fake_member['first_name'],
        'last_name' => $fake_member['last_name'],
        'personify_id' => _openy_demo_ncampaign_generate_random_string(6, TRUE),
        //'branch' => $location_repository->findByLocationPersonifyBranchCode($fake_member['branch_id']),
        'is_employee' => $fake_member['is_employee'],
        'total_visits' => 0,
        'total_bonuses' => 0,
        'created_by_staff' => FALSE,
      ]);
    $member->save();

    // Create activities for the member.
    // _openy_demo_ncampaign_activities_create($member);

    // Create checkins for the member.
    //_openy_demo_ncampaign_checkins_create($member, !empty($options['everyday']));

    // Create bonuses for the member.
    //_openy_demo_ncampaign_bonuses_create($member);

    if (10 <= $count++) {
      return;
    }
  }
}

/**
 * Helper function to create fake checkins.
 */
function _openy_demo_ncampaign_checkins_create($member, $everyday = FALSE) {
  $count = rand(3, 8);
  $dates = _openy_demo_ncampaign_generate_random_dates($count, $everyday);
  if ($everyday) {
    $count = count($dates);
  }
  for ($i = 0; $i < $count; $i++) {
    $checkin = \Drupal::entityTypeManager()
      ->getStorage('openy_campaign_member_checkin')
      ->create([
        'created' => REQUEST_TIME,
        'date' => $dates[$i]->getTimestamp(),
        'member' => $member->id(),
        'checkin' => TRUE,
      ]);
    $checkin->save();
  }
}

/**
 * Helper function to create fake bonuses.
 */
function _openy_demo_ncampaign_bonuses_create($member) {
  $count = rand(3, 8);
  $dates = _openy_demo_ncampaign_generate_random_dates($count);
  for ($i = 0; $i < $count; $i++) {
    $bonus = \Drupal::entityTypeManager()
      ->getStorage('openy_campaign_member_bonus')
      ->create([
        'created' => REQUEST_TIME,
        'date' => $dates[$i]->getTimestamp(),
        'member' => $member->id(),
        'bonus_code' => _openy_demo_ncampaign_generate_random_string(3),
      ]);
    $bonus->save();
  }
}

/**
 * Helper function to create fake activities.
 */
function _openy_demo_ncampaign_activities_create($member) {
  $activities_number = rand(20, 50);

  // Load activity terms.
  /** @var TermStorage $term_storage */
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $terms = $term_storage->loadTree('ymca_retention_activities');
  $term_count = count($terms);

  // Create activities.
  for ($i = 0; $i < $activities_number; $i++) {
    $term = $terms[rand(0, $term_count - 1)];
    $parents = $term_storage->loadParents($term->tid);
    if (empty($parents)) {
      $i--;
      continue;
    }

    $activity = MemberActivity::create([
      'timestamp' => REQUEST_TIME,
      'member' => $member->id(),
      'activity_type' => $term->tid,
    ]);
    $activity->save();
  }
}

/**
 * Returns YMCA retention fake members data.
 */
function _openy_demo_ncampaign_fake_members() {
  return [
    [
      'membership_id' => '4000044307',
      'first_name' => 'Amber',
      'last_name' => 'Richard',
      'branch_id' => 20,
      'is_employee' => 1,
    ],
    [
      'membership_id' => '4000301612',
      'first_name' => 'Andrea',
      'last_name' => 'Krohnberg',
      'branch_id' => 30,
      'is_employee' => 1,
    ],
    [
      'membership_id' => '4000301611',
      'first_name' => 'Andrew',
      'last_name' => 'Krohnberg',
      'branch_id' => 38,
      'is_employee' => 1,
    ],
    [
      'membership_id' => '4000168913',
      'first_name' => 'Robin',
      'last_name' => 'Hedrick',
      'branch_id' => 16,
      'is_employee' => 1,
    ],
    [
      'membership_id' => '4000577860',
      'first_name' => 'Kelli',
      'last_name' => 'Teel',
      'branch_id' => 89,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000314817',
      'first_name' => 'Sarah',
      'last_name' => 'Martinez',
      'branch_id' => 82,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '007000015014',
      'first_name' => 'Kevin',
      'last_name' => 'Lagos',
      'branch_id' => 34,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000545206',
      'first_name' => 'Mai',
      'last_name' => 'Lagos',
      'branch_id' => 18,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '077001139605',
      'first_name' => 'Taivy',
      'last_name' => 'Lagos',
      'branch_id' => 17,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '076002176700',
      'first_name' => 'Dawn',
      'last_name' => 'Flores',
      'branch_id' => 24,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '076002176701',
      'first_name' => 'Robert',
      'last_name' => 'Diaz',
      'branch_id' => 22,
      'is_employee' => 0,
    ],

    [
      'membership_id' => '14044936700',
      'first_name' => 'Veronica',
      'last_name' => 'O\'Connor',
      'branch_id' => 36,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000566878',
      'first_name' => 'Lorelei',
      'last_name' => 'Giddings',
      'branch_id' => 14,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000399111',
      'first_name' => 'Samantha',
      'last_name' => 'Kleiner',
      'branch_id' => 14,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000359294',
      'first_name' => 'Jessica',
      'last_name' => 'Fisher',
      'branch_id' => 14,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000405873',
      'first_name' => 'Deanna',
      'last_name' => 'DeMay',
      'branch_id' => 14,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '532392',
      'first_name' => 'Robert',
      'last_name' => 'Hunter',
      'branch_id' => 14,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000454010',
      'first_name' => 'Mary',
      'last_name' => 'Leoni',
      'branch_id' => 14,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000042681',
      'first_name' => 'Tim',
      'last_name' => 'Herbstrith',
      'branch_id' => 14,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '814629',
      'first_name' => 'Susan',
      'last_name' => 'Johnson',
      'branch_id' => 14,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000387642',
      'first_name' => 'Lisa',
      'last_name' => 'Motz',
      'branch_id' => 14,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000004674',
      'first_name' => 'Michael',
      'last_name' => 'Peller',
      'branch_id' => 14,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000059604',
      'first_name' => 'Cary',
      'last_name' => 'Stegall',
      'branch_id' => 14,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000484464',
      'first_name' => 'Sarah',
      'last_name' => 'Linnes-Robinson',
      'branch_id' => 14,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000466610',
      'first_name' => 'Anna',
      'last_name' => 'Schiltz',
      'branch_id' => 14,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000568263',
      'first_name' => 'Charles',
      'last_name' => 'Dunbar',
      'branch_id' => 14,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000341148',
      'first_name' => 'Susanne',
      'last_name' => 'Fry',
      'branch_id' => 14,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000524837',
      'first_name' => 'Paula',
      'last_name' => 'Fleming',
      'branch_id' => 14,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000003466',
      'first_name' => 'Patricia',
      'last_name' => 'Berg',
      'branch_id' => 26,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000399340',
      'first_name' => 'Amy',
      'last_name' => 'Melcher',
      'branch_id' => 26,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000399126',
      'first_name' => 'Haruko',
      'last_name' => 'Ruggiero',
      'branch_id' => 26,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000268491',
      'first_name' => 'Stephen',
      'last_name' => 'Glover',
      'branch_id' => 26,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '437248',
      'first_name' => 'Kathleen',
      'last_name' => 'Sapp',
      'branch_id' => 26,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000322513',
      'first_name' => 'Sandra',
      'last_name' => 'Freeman',
      'branch_id' => 26,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000326060',
      'first_name' => 'Robert',
      'last_name' => 'Eng',
      'branch_id' => 26,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000388061',
      'first_name' => 'Jayne',
      'last_name' => 'Kainulainen',
      'branch_id' => 26,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000399126',
      'first_name' => 'Cassandra',
      'last_name' => 'Herold',
      'branch_id' => 26,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000428446',
      'first_name' => 'Patricia',
      'last_name' => 'Parnow',
      'branch_id' => 26,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000302774',
      'first_name' => 'Gregory',
      'last_name' => 'Gonzalez',
      'branch_id' => 26,
      'is_employee' => 0,
    ],
    [
      'membership_id' => '4000441175',
      'first_name' => 'Johnny',
      'last_name' => 'Simpson',
      'branch_id' => 26,
      'is_employee' => 0,
    ],
  ];
}

/**
 * Return random string.
 */
function _openy_demo_ncampaign_generate_random_string($length = 10, $only_number = FALSE) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  if ($only_number) {
    $characters = '0123456789';
  }
  $characters_length = strlen($characters);
  $random_string = '';
  for ($i = 0; $i < $length; $i++) {
    $random_string .= $characters[rand(0, $characters_length - 1)];
  }
  return $random_string;
}

/**
 * Return array with random dates.
 */
function _openy_demo_ncampaign_generate_random_dates($number_elements = 3, $everyday = FALSE) {
  $max_array_length = 50;
  $config = \Drupal::config('ymca_retention.general_settings');
  $start_date = new \DateTime($config->get('date_campaign_open'));
  $end_date = new \DateTime($config->get('date_campaign_close'));
  $current_date = clone $start_date;
  $current_date->setTime(0, 0, 0);
  $dates = [];
  $result = [];

  if ($current_date < $start_date) {
    $current_date->add(new \DateInterval('P1D'));
  }

  $count = 0;
  while ($current_date < $end_date && $max_array_length > $count) {
    $dates[] = clone $current_date;
    $current_date->add(new \DateInterval('P1D'));
    $count++;
  }

  if ($everyday) {
    return $dates;
  }

  $random_keys = array_rand($dates, max(1, min($number_elements, $count)));

  foreach ($random_keys as $key) {
    $result[] = $dates[$key];
  }

  return $result;
}
