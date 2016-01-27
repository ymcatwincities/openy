<?php
/**
 * @file
 * Contains \Drupal\ymca_activity_finder\Controller\ActivityFinderController.
 */

namespace Drupal\ymca_activity_finder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
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
   * @param mixed $id
   *   Node ID.
   *
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
   * Check arguments.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node.
   * @param string $title
   *   Cleaned title.
   *
   * @throws \Exception
   *   Exception.
   */
  private function checkArgs(NodeInterface $node, $title) {
    // Check bundle.
    if ($node->bundle() != 'article') {
      throw new \Exception('The node has a wrong type.');
    }

    // Check title.
    if ($title != ActivityFinderTrait::cleanTitle($node->label())) {
      throw new \Exception('The node has a wrong title.');
    }
  }

  /**
   * Get produce page.
   *
   * @param mixed $id
   *   Node ID.
   * @param mixed $title
   *   Title.
   *
   * @return array
   *   Render array.
   *
   * @throws EnforcedResponseException
   *   Exception.
   *
   * @throws NotFoundHttpException
   *   Exception.
   */
  public function productView($id, $title) {
    try {
      $node = $this->getNode($id);
      $this->checkArgs($node, $title);

      \Drupal::service('pagecontext.service')->setContext($node);
      $view = node_view($node, 'product');
      $markup = render($view);

      return [
        '#markup' => $markup,
      ];
    }
    catch (EnforcedResponseException $e) {
      throw $e;
    }
    catch (\Exception $e) {
      watchdog_exception('ymca_activity_finder', $e, RfcLogLevel::INFO);
      throw new NotFoundHttpException();
    }
  }

  /**
   * Get page title.
   *
   * @param mixed $id
   *   Node ID.
   * @param string $title
   *   Cleaned title.
   *
   * @return string
   *   Title.
   */
  public function productTitle($id, $title) {
    return $title;
  }

}
