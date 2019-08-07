<?php

/**
 * @file
 * Documents hooks provided by this module.
 */

/**
 * Modify the alerts rest resource results.
 *
 * Example: you can add new alerts or remove existing before output.
 *
 * @see \Drupal\openy_node_alert\Plugin\rest\resource\AlertsRestResource
 */
function hook_openy_node_alert_get_alter(array &$sendAlerts, array $alerts) {
  // Example code:
  foreach ($alerts as $id => $alert) {
    if ($alert->hasField('field_alert_belongs') && $alert->field_alert_belongs->target_id == '{Some node ID}') {
      $sendAlerts[$alert->field_alert_place->value]['global'][] = \Drupal\openy_node_alert\Plugin\rest\resource\AlertsRestResource::formatAlert($alert);
    }
  }
}
