<?php

namespace Drupal\ymca_groupex\Controller;

use Drupal\node\NodeInterface;

/**
 * Implements SearchResultsController.
 */
class SearchResultsController {

  /**
   * Show the page.
   */
  public function pageView(NodeInterface $node) {
    $view = node_view($node, 'groupex');
    $markup = render($view);

    return [
      '#markup' => $markup,
      '#cache' => [
        'max-age' => 3600,
        'contexts' => ['url.query_args'],
      ],
    ];
  }

}
