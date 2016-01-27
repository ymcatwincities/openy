<?php
/**
 * @file
 * Contains \Drupal\ymca_activity_finder\Controller\ActivityFinderController.
 */

namespace Drupal\ymca_activity_finder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\node\Entity\Node;
use Drupal\ymca_activity_finder\ActivityFinderTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Implements ActivityFinderController.
 */
class ActivityFinderController extends ControllerBase {

  use ActivityFinderTrait;

  /**
   * Get node by ID.
   *
   * @param $id
   *
   *   Node ID.
   * @return \Drupal\node\Entity\Node
   *   Node object.
   *
   * @throws \Exception
   *   Exception.
   */
  private function getNode($id) {
    /** @var Node $node */
    $node = Node::load($id);
    if (!$node) {
      throw new \Exception('The node could not be found.');
    }

    return $node;
  }

  /**
   * Render a page.
   *
   * @param $id
   *   Node ID.
   * @param $title
   *   Cleaned title.
   *
   * @return array
   *   Render array.
   */
  public function productView($id, $title) {
    try {
      $node = $this->getNode($id);

      // Check bundle.
      if ($node->bundle() != 'article') {
        \Drupal::logger('ymca_activity_finder')->info('The node has a wrong type.');
        throw new NotFoundHttpException();
      }

      // Check title.
      if ($title != ActivityFinderTrait::cleanTitle($node->label())) {
        \Drupal::logger('ymca_activity_finder')->info('The node has a wrong title.');
        throw new NotFoundHttpException();
      }

      \Drupal::service('pagecontext.service')->setContext($node);
      $view = node_view($node, 'product');
      $markup = render($view);

      return [
        '#markup' => $markup,
      ];
    }
    catch (\Exception $e) {
      watchdog_exception('ymca_activity_finder', $e, RfcLogLevel::INFO);
      throw new NotFoundHttpException();
    }
  }

  /**
   * Get page title.
   *
   * @param $id
   *  Node ID.
   * @param $title
   *   Cleaned title.
   *
   * @return string
   *   Title.
   */
  public function productTitle($id, $title) {
    return $title;
  }

}
