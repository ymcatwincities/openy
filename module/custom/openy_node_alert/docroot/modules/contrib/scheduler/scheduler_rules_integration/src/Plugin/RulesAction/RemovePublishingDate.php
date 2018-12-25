<?php

namespace Drupal\scheduler_rules_integration\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Url;

/**
 * Provides a 'Remove date for scheduled publishing' action.
 *
 * @RulesAction(
 *   id = "scheduler_remove_publishing_date_action",
 *   label = @Translation("Remove date for scheduled publishing"),
 *   category = @Translation("Scheduler"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Node"),
 *       description = @Translation("The node from which to remove the scheduled publishing date"),
 *     ),
 *   }
 * )
 */
class RemovePublishingDate extends RulesActionBase {

  /**
   * Remove the publish_on date from the node.
   */
  public function doExecute() {
    $node = $this->getContextValue('node');
    $config = \Drupal::config('scheduler.settings');
    if ($node->type->entity->getThirdPartySetting('scheduler', 'publish_enable', $config->get('default_publish_enable'))) {
      $node->set('publish_on', NULL);
      scheduler_node_presave($node);
      scheduler_node_update($node);
    }
    else {
      $type_name = node_get_type_label($node);
      $arguments = [
        '%type' => $type_name,
        'link' => \Drupal::l(t('@type settings', ['@type' => $type_name]), new Url('entity.node_type.edit_form', ['node_type' => $node->getType()])),
      ];
      \Drupal::logger('scheduler')->warning('Scheduler rules action "Remove publishing date" - Scheduled publishing is not enabled for %type content. To prevent this message add the condition "Scheduled publishing is enabled" to your Rule, or enable the Scheduler options via the %type content type settings.', $arguments);
    }
  }

}
