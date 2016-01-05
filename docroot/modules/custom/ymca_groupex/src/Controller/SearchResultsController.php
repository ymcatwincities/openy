<?php

/**
 * @file
 * Contains \Drupal\ymca_groupex\Controller\SearchResultsController.
 */

namespace Drupal\ymca_groupex\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * Implements SearchResultsController.
 */
class SearchResultsController {

  /**
   * Show the page.
   */
  public function pageView(NodeInterface $node) {
    \Drupal::service('pagecontext.service')->setContext($node);
    $view = node_view($node, 'groupex');
    $markup = render($view);

    return array(
      '#markup' => $markup,
    );
  }

}
