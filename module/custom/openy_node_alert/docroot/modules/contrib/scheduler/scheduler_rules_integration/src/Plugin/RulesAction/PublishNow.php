<?php

namespace Drupal\scheduler_rules_integration\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Publish the node immediately' action.
 *
 * @RulesAction(
 *   id = "scheduler_publish_now_action",
 *   label = @Translation("Publish the content immediately"),
 *   category = @Translation("Scheduler"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Node"),
 *       description = @Translation("The node to be published now"),
 *     ),
 *   }
 * )
 */
class PublishNow extends RulesActionBase {

  /**
   * Set the node status to Published.
   *
   * This action should really be provided by Rules or by Core, but it is not
   * yet done (as of Aug 2016). Scheduler users need this action so we provide
   * it here. It could be removed later when Rules or Core includes it.
   */
  public function doExecute() {
    $node = $this->getContextValue('node');
    $node->setPublished(TRUE);
  }

}
