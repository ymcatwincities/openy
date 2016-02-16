<?php

namespace Drupal\ymca_blog_listing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;

/**
 * Class YMCANewsEventsPageController.
 *
 * @package Drupal\ymca_blog_listing\Controller
 */
class YMCANewsEventsPageController extends ControllerBase {

  /**
   * Generate page content.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node object.
   *
   * @return array
   *   Return render array.
   */
  public function pageView(NodeInterface $node) {
    $view = views_embed_view('camp_blog_listing', 'blog_listing_embed', $node->id());

    return [
      'view' => $view,
    ];
  }

}
