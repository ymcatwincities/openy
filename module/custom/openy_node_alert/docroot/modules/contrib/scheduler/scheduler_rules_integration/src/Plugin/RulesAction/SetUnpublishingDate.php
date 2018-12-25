<?php

namespace Drupal\scheduler_rules_integration\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Url;

/**
 * Provides a 'Set date for scheduled unpublishing' action.
 *
 * @RulesAction(
 *   id = "scheduler_set_unpublishing_date_action",
 *   label = @Translation("Set date for scheduled unpublishing"),
 *   category = @Translation("Scheduler"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Node for scheduling"),
 *       description = @Translation("The node which is to have a scheduled unpublishing date set"),
 *     ),
 *     "date" = @ContextDefinition("timestamp",
 *       label = @Translation("The date for unpublishing"),
 *       description = @Translation("The date when Scheduler will unpublish the node"),
 *     )
 *   }
 * )
 */
class SetUnpublishingDate extends RulesActionBase {

  /**
   * Set the unpublish_on date for the node.
   */
  public function doExecute() {
    $node = $this->getContextValue('node');
    $date = $this->getContextValue('date');
    $config = \Drupal::config('scheduler.settings');
    if ($node->type->entity->getThirdPartySetting('scheduler', 'unpublish_enable', $config->get('default_unpublish_enable'))) {
      $node->set('unpublish_on', $date);
      // When this action is invoked and it operates on the node being editted
      // then hook_node_presave() and hook_node_update() will be executed
      // automatically. But if this action is being used to schedule a different
      // node then we need to call the functions directly here.
      scheduler_node_presave($node);
      scheduler_node_update($node);
    }
    else {
      $type_name = node_get_type_label($node);
      $arguments = [
        '%type' => $type_name,
        'link' => \Drupal::l(t('@type settings', ['@type' => $type_name]), new Url('entity.node_type.edit_form', ['node_type' => $node->getType()])),
      ];
      \Drupal::logger('scheduler')->warning('Scheduler rules action "Set unpublishing date" - Scheduled unpublishing is not enabled for %type content. To prevent this message add the condition "Scheduled unpublishing is enabled" to your Rule, or enable the Scheduler options via the %type content type settings.', $arguments);
    }
  }

}
