<?php

namespace Drupal\ymca_alters\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Controller\NodePreviewController;
use Drupal\Core\Entity\Controller\EntityViewController;

/**
 * Defines a controller to render a single node in preview.
 */
class YmcaNodePreviewController extends NodePreviewController {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $node_preview, $view_mode_id = 'full', $langcode = NULL) {
    $node_preview->preview_view_mode = $view_mode_id;
    $build = EntityViewController::view($node_preview, $view_mode_id);

    $build['#attached']['library'][] = 'node/drupal.node.preview';

    // Don't render cache previews.
    unset($build['#cache']);

    foreach ($node_preview->uriRelationships() as $rel) {
      // Do not generate revision link for preview.
      if ($rel == 'revision') {
        continue;
      }

      // Set the node path as the canonical URL to prevent duplicate content.
      $build['#attached']['html_head_link'][] = array(
        [
          'rel' => $rel,
          'href' => $node_preview->url($rel)
        ],
        TRUE
      );

      if ($rel == 'canonical') {
        // Set the non-aliased canonical path as a default shortlink.
        $build['#attached']['html_head_link'][] = array(
          [
            'rel' => 'shortlink',
            'href' => $node_preview->url($rel, array('alias' => TRUE))
          ],
          TRUE
        );
      }
    }

    return $build;
  }

}
