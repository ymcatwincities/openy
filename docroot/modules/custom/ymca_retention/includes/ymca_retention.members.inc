<?php

/**
 * @file
 * Generate fake members and activities.
 */

use Drupal\ymca_retention\Entity\Member;
use Drupal\ymca_retention\Entity\MemberActivity;
use Drupal\ymca_retention\PersonifyApi;
use Drupal\taxonomy\TermStorage;

/**
 * Helper function to create fake members and activities.
 */
function _ymca_retention_members_create() {
  foreach (_ymca_retention_fake_members() as $fake_member) {
    // Get information about member from Personify and validate entered membership ID.
    $personify_member = PersonifyApi::getPersonifyMemberInformation($fake_member['membership_id']);
    $membership_id = $fake_member['membership_id'];

    // Get retention settings.
    $settings = \Drupal::config('ymca_retention.general_settings');

    // Get information about number of checkins before campaign.
    $current_date = new \DateTime();
    $from_date = new \DateTime($settings->get('date_checkins_start'));
    $to_date = new \DateTime($settings->get('date_checkins_end'));

    $to = $to_date->format('m/d/Y g:i A');
    $number_weeks = ceil($from_date->diff($to_date)->days / 7);
    if ($to_date > $current_date) {
      $to = $current_date->format('m/d/Y g:i A');
      $number_weeks = ceil($from_date->diff($current_date)->days / 7);
    }
    $from = $from_date->format('m/d/Y g:i A');
    $past_result = PersonifyApi::getPersonifyVisitCountByDate($membership_id, $from, $to);

    // Calculate a goal for a member.
    $goal = 1;
    // @todo This is now working in case when user registered after $from_date.
    if (empty($past_result->ErrorMessage) && $past_result->TotalVisits > 0) {
      $limit_goal = $settings->get('limit_goal_number');
      $goal = ceil((($past_result->TotalVisits / $number_weeks) * 2) + 1);
      $goal = min($goal, $limit_goal);
    }

    // Get information about number of checkins in period of campaign.
    $from = $settings->get('date_registration_open');
    $to = $settings->get('date_registration_close');
    $current_result = PersonifyApi::getPersonifyVisitCountByDate($membership_id, $from, $to);

    $total_visits = 0;
    if (empty($current_result->ErrorMessage) && $current_result->TotalVisits > 0) {
      $total_visits = $current_result->TotalVisits;
    }
    // Identify is user an employee or not.
    $is_employee = !empty($personify_member->ProductCode) && strpos($personify_member->ProductCode, 'STAFF');

    // Create a new member.
    /** @var Member $member */
    $member = \Drupal::entityTypeManager()
      ->getStorage('ymca_retention_member')
      ->create([
        'membership_id' => $fake_member['membership_id'],
        'mail' => $fake_member['mail'],
        'first_name' => $personify_member->FirstName,
        'last_name' => $personify_member->LastName,
        'branch' => (int) $personify_member->BranchId,
        'is_employee' => $is_employee,
        'visit_goal' => $goal,
        'total_visits' => $total_visits,
        'created_by_staff' => FALSE,
      ]);
    $member->save();

    // Create activities for the member.
    _ymca_retention_activities_create($member);
  }
}

/**
 * Helper function to create fake activities.
 */
function _ymca_retention_activities_create($member) {
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
function _ymca_retention_fake_members() {
  return [
    [
      'membership_id' => '14044936700',
      'mail' => 'bonoronh@aol.com',
    ],
    [
      'membership_id' => '4000566878',
      'mail' => 'oboemuse@gmail.com',
    ],
    [
      'membership_id' => '4000399111',
      'mail' => 'klein705@umn.edu',
    ],
    [
      'membership_id' => '4000359294',
      'mail' => 'fishe439@umn.edu',
    ],
    [
      'membership_id' => '4000405873',
      'mail' => 'deanna.demay@gmail.com',
    ],
    [
      'membership_id' => '532392',
      'mail' => 'aa0fg@yahoo.com',
    ],
    [
      'membership_id' => '4000454010',
      'mail' => 'mal3115@netzero.net',
    ],
    [
      'membership_id' => '4000042681',
      'mail' => 'Tim@Hodder.tv',
    ],
    [
      'membership_id' => '814629',
      'mail' => 'suejohnsonemail@gmail.com',
    ],
    [
      'membership_id' => '4000387642',
      'mail' => 'lisa.motz5@gmail.com',
    ],
    [
      'membership_id' => '4000004674',
      'mail' => 'GR8TZMICHAEL@yahoo.com',
    ],
    [
      'membership_id' => '4000059604',
      'mail' => 'carystegall@gmail.com',
    ],
    [
      'membership_id' => '4000484464',
      'mail' => 'sarah.linnesro@earthlink.net',
    ],
    [
      'membership_id' => '4000466610',
      'mail' => 'annaschiltz@gmail.com',
    ],
    [
      'membership_id' => '4000568263',
      'mail' => 'dunbar612@yahoo.com',
    ],
    [
      'membership_id' => '4000341148',
      'mail' => 'fries125@gmail.com',
    ],
    [
      'membership_id' => '4000524837',
      'mail' => 'paulafleming@comcast.net',
    ],
    [
      'membership_id' => '4000003466',
      'mail' => 'patandbill60@earthlink.net',
    ],
    [
      'membership_id' => '4000399340',
      'mail' => 'melkan@tcq.net',
    ],
    [
      'membership_id' => '4000399126',
      'mail' => 'harrug@gmail.com',
    ],
    [
      'membership_id' => '4000268491',
      'mail' => 'steveglove@yahoo.com',
    ],
    [
      'membership_id' => '437248',
      'mail' => 'kathleen.sapp@gmail.com',
    ],
    [
      'membership_id' => '4000322513',
      'mail' => 'sfreeman1951@yahoo.com',
    ],
    [
      'membership_id' => '4000326060',
      'mail' => 'rreng@hotmail.com',
    ],
    [
      'membership_id' => '4000388061',
      'mail' => 'harrug@gmail.com',
    ],
    [
      'membership_id' => '4000399126',
      'mail' => 'jaynekain59@gmail.com',
    ],
    [
      'membership_id' => '4000428446',
      'mail' => 'cassandraherold@yahoo.com',
    ],
    [
      'membership_id' => '4000302774',
      'mail' => 'PParnow@aol.com',
    ],
    [
      'membership_id' => '4000441175',
      'mail' => 'gregoryagonzalez1@yahoo.com',
    ],
  ];
}
