<?php

/**
 * @file
 * Contains \Drupal\workflow_ui\Controller\WorkflowUiController.
 */

namespace Drupal\workflow_ui\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Workflow UI routes.
 */
class WorkflowUiController extends ControllerBase {
  /**
   * Returns the settings page.
   *
   * @return array
   *   Renderable array.
   */
  public function settingsForm() {
    $element = [
      '#markup' => 'Workflow settings form is not implemented yet.',
    ];
    return $element;
  }
}
