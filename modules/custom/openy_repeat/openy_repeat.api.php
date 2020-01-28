<?php

/**
 * @file
 * Documents hooks provided by this module.
 */

/**
 * Modify the schedules search results prior output.
 *
 * Example: GroupExPro modifies links to register for activity.
 */
function hook_openy_repeat_results_alter(&$result, $request) {

}

/**
 * Modify the schedules query before execute.
 *
 * Example: you can add new fields or additional conditions here.
 *
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Database%21database.api.php/function/hook_query_TAG_alter/8.2.x
 * @see \Drupal\openy_repeat\Controller\RepeatController
 */
function hook_query_openy_repeat_get_data_alter(Drupal\Core\Database\Query\AlterableInterface $query) {
  $query->condition('re.actual', 1);
}

/**
 * Modify locations info for Repeat Schedules block.
 *
 * @param array $data
 *   The array of locations data.
 */
function hook_openy_repeat_locations_info_alter(array &$data) {

}
