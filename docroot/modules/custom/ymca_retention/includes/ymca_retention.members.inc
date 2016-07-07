<?php

/**
 * @file
 * Generate fake members and activities.
 */

use Drupal\ymca_retention\Entity\Member;
use Drupal\ymca_retention\Entity\MemberActivity;
use Drupal\ymca_retention\PersonifyApi;
use Drupal\taxonomy\TermStorage;
use Drupal\taxonomy\Entity\Term;

/**
 * Helper function to create fake members and activities.
 */
function _ymca_retention_members_create() {
  foreach (_ymca_retention_fake_members() as $fake_member) {
    // Get information about member from Personify and validate entered membership ID.
    $personify_member = PersonifyApi::getPersonifyMemberInformation($fake_member['membership_id']);

    // Get retention settings.
    $settings = \Drupal::config('ymca_retention.general_settings');

    // Get information about number of checkins before campaign.
    $from = $settings->get('date_checkins_start');
    $to = $settings->get('date_checkins_end');
    $past_result = PersonifyApi::getPersonifyVisitCountByDate($fake_member['membership_id'], $from, $to);

    // Calculate a goal for a member.
    $goal = $settings->get('default_goal_number');
    if (empty($past_result->ErrorMessage) && $past_result->TotalVisits > 0) {
      $percent = $settings->get('goal_percentage');
      $calculated_goal = ceil($past_result->TotalVisits + ($past_result->TotalVisits * $percent));
      $goal = min($calculated_goal, $goal);
    }

    // Get information about number of checkins in period of campaign.
    $from = $settings->get('date_registration_open');
    $to = $settings->get('date_registration_close');
    $current_result = PersonifyApi::getPersonifyVisitCountByDate($fake_member['membership_id'], $from, $to);

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
  $terms = $term_storage->loadTree('ymca_retention_activities', 0, NULL, TRUE);
  $term_count = count($terms);

  // Create activities.
  for ($i = 0; $i < $activities_number; $i++) {
    /** @var Term $term */
    $term = $terms[rand(0, $term_count - 1)];
    $parents = $term_storage->loadParents($term->id());
    if (empty($parents)) {
      $i--;
      continue;
    }

    $activity = MemberActivity::create([
      'timestamp' => REQUEST_TIME,
      'member' => $member->id(),
      'activity_type' => $term->id(),
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
  ];
}
