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
    $view = '';
    switch ($node->getType()) {
      case 'camp':
        $view = views_embed_view('camp_blog_listing', 'blog_listing_embed', $node->id());
        break;

      case 'location':
        $view = views_embed_view('location_blog_listing', 'blog_listing_embed');
        break;

      default:
        \Drupal::logger('ymca_blog_listing')->alert(t('Not defined CT view.'));
    }

    return [
      'view' => $view,
    ];
  }

}
