<?php

namespace Drupal\ymca_blog_listing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        $output = array('view' => $view);
        break;

      case 'location':
        $query = \Drupal::entityQuery('node')
          ->condition('type', 'blog')
          ->sort('created', 'DESC')
          ->range(0, 10);
        $group = $query->orConditionGroup()
          ->condition('field_site_section.target_id', NULL, 'IS NULL')
          ->condition('field_site_section.target_id', $node->id());
        $nids = $query->condition($group)->execute();

        $output = array('#markup' => '');
        if (!empty($nids)) {
          $node_storage = \Drupal::entityManager()->getStorage('node');
          $nodes = $node_storage->loadMultiple($nids);
          foreach ($nodes as $node) {
            $node_view = node_view($node, 'location_blog_teaser');
            $output['#markup'] .= render($node_view);
          }
        }
        break;

      default:
        throw new NotFoundHttpException();
    }

    return $output;
  }

}
