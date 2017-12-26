<?php

namespace Drupal\scheduler_rules_integration\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides 'Node is scheduled for publishing' condition.
 *
 * @Condition(
 *   id = "scheduler_condition_node_scheduled_for_publishing",
 *   label = @Translation("Node is scheduled for publishing"),
 *   category = @Translation("Scheduler"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("The node to test for having a scheduled publishing date."),
 *       description = @Translation("Enter 'node' for the node being processed, or use data selection.")
 *     )
 *   }
 * )
 */
class NodeIsScheduledForPublishing extends RulesConditionBase {

  /**
   * Determines whether a node is scheduled for publishing.
   *
   * @return bool
   *   TRUE if the node is scheduled for publishing, FALSE if not.
   */
  protected function doEvaluate() {
    $node = $this->getContextValue('node');
    return !empty($node->publish_on->value);
  }

}
