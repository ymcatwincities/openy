<?php

namespace Drupal\scheduler_rules_integration\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a 'Publishing is enabled' condition.
 *
 * @Condition(
 *   id = "scheduler_condition_publishing_is_enabled",
 *   label = @Translation("Node type is enabled for scheduled publishing"),
 *   category = @Translation("Scheduler"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("The node to use for scheduled publishing enabled"),
 *       description = @Translation("Enter 'node' for the node being processed, or use data selection.")
 *     )
 *   }
 * )
 */
class PublishingIsEnabled extends RulesConditionBase {

  /**
   * Determines whether scheduled publishing is enabled for this node type.
   *
   * @return bool
   *   TRUE if scheduled publishing is enabled for the content type of this
   *   node.
   */
  public function evaluate() {
    $node = $this->getContextValue('node');
    $config = \Drupal::config('scheduler.settings');
    return ($node->type->entity->getThirdPartySetting('scheduler', 'publish_enable', $config->get('default_publish_enable')));
  }

}
