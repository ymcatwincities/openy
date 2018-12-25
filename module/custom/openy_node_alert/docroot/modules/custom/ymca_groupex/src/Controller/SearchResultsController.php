<?php

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
    // It catches cases with old arguments and redirect to this page without arguments.
    // @var  \Symfony\Component\HttpFoundation\Request $request
    $request = \Drupal::request();
    $query = $request->query->all();
    if (array_key_exists('location', $query)) {
      unset($query['location']);
      return $this->redirect('ymca_frontend.location_schedules', ['node' => $node->id()], ['query' => $query]);
    }
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
