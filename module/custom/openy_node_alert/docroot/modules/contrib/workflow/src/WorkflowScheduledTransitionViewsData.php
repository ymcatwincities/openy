<?php

/**
 * @file
 * Contains \Drupal\workflow\WorkflowScheduledTransitionViewsData.
 */

namespace Drupal\workflow;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the workflow entity type.
 */
class WorkflowScheduledTransitionViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    // @todo D8-port: Add some data from D7 function workflow_views_views_data_alter() ??
    // @see http://cgit.drupalcode.org/workflow/tree/workflow_views/workflow_views.views.inc
    return $data;
  }

}
