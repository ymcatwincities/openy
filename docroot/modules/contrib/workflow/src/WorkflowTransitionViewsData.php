<?php

/**
 * @file
 * Contains \Drupal\workflow\WorkflowTransitionViewsData.
 */

namespace Drupal\workflow;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the workflow entity type.
 */
class WorkflowTransitionViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Use flexible $base_table, since both WorkflowTransition and
    // WorkflowScheduledTransition use this.
    $base_table = $this->entityType->getBaseTable();
    $base_field = $this->entityType->getKey('id');

    // @todo D8-port: Add data from D7 function workflow_views_views_data_alter()
    // @see http://cgit.drupalcode.org/workflow/tree/workflow_views/workflow_views.views.inc
    $data[$base_table]['from_sid']['filter']['id'] = 'workflow_state';
    $data[$base_table]['to_sid']['filter']['id'] = 'workflow_state';

    return $data;
  }

}
