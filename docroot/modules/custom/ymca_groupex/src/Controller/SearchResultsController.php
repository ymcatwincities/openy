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
class SearchResultsController extends ControllerBase {

  /**
   * Show the page.
   */
  public function pageView(NodeInterface $node) {
    \Drupal::service('pagecontext.service')->setContext($node);
    $markup = render(node_view($node, 'groupex'));

    return array(
      '#markup' => $markup,
    );
  }

}
