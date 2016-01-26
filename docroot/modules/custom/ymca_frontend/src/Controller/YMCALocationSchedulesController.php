<?php
/**
 * @file
 * Contains Drupal\ymca_frontend\Controller\YMCALocationSchedulesController.
 */

namespace Drupal\ymca_frontend\Controller;

use Drupal\Core\Render\Element;
use Drupal\node\NodeInterface;


/**
 * Controller for Scheduler view mode of Location CT.
 */
class YMCALocationSchedulesController {

  /**
   * Set Markup.
   */
  public function content(NodeInterface $node) {
    \Drupal::service('pagecontext.service')->setContext($node);
    $markup = render(node_view($node, 'schedules'));

    return array(
      '#markup' => $markup,
    );
  }

  /**
   * Set Title.
   */
  public function setTitle(NodeInterface $node) {
    return $node->getTitle();
  }

}
