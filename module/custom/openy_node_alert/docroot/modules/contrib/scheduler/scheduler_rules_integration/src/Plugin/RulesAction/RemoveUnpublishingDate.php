<?php

namespace Drupal\scheduler_rules_integration\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Url;

/**
 * Provides a 'Remove date for scheduled unpublishing' action.
 *
 * @RulesAction(
 *   id = "scheduler_remove_unpublishing_date_action",
 *   label = @Translation("Remove date for scheduled unpublishing"),
 *   category = @Translation("Scheduler"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Node"),
 *       description = @Translation("The node from which to remove the scheduled unpublishing date"),
 *     ),
 *   }
 * )
 */
class RemoveUnpublishingDate extends RulesActionBase {

  /**
   * Remove the unpublish_on date from the node.
   */
  public function doExecute() {
    $node = $this->getContextValue('node');
    $config = \Drupal::config('scheduler.settings');
    if ($node->type->entity->getThirdPartySetting('scheduler', 'unpublish_enable', $config->get('default_unpublish_enable'))) {
      $node->set('unpublish_on', NULL);
      scheduler_node_presave($node);
      scheduler_node_update($node);
    }
    else {
      $type_name = node_get_type_label($node);
      $arguments = [
        '%type' => $type_name,
        'link' => \Drupal::l(t('@type settings', ['@type' => $type_name]), new Url('entity.node_type.edit_form', ['node_type' => $node->getType()])),
      ];
      \Drupal::logger('scheduler')->warning('Scheduler rules action "Remove unpublishing date" -  Scheduled unpublishing is not enabled for %type content. To prevent this message add the condition "Scheduled unpublishing is enabled" to your Rule, or enable the Scheduler options via the %type content type settings.', $arguments);
    }
  }

}
