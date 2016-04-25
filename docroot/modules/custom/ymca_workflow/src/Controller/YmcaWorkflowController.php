<?php

namespace Drupal\ymca_workflow\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\node\NodeInterface;

/**
 * Controller for Preview tab.
 */
class YmcaWorkflowController {
  protected $latestRevisionVid;

  protected $node;

  /**
   * Constructor to avoid duplicate code in access and preview methods.
   */
  public function __construct() {
    $request = \Drupal::request();
    $storage = \Drupal::entityManager()->getStorage('node');

    $this->node = $request->get('node');
    if (!$this->node instanceof NodeInterface) {
      $this->node = node_load($this->node);
    }
    $vids = $storage->revisionIds($this->node);
    $this->latestRevisionVid = array_pop($vids);
  }

  /**
   * Check access to Preview tab.
   */
  public function access(AccountInterface $account) {
    if ($this->latestRevisionVid > $this->node->getRevisionId()) {
      return new AccessResultAllowed();
    }
    return new AccessResultForbidden();
  }

  /**
   * Callback for Preview page.
   */
  public function preview() {
    $node_revision = node_revision_load($this->latestRevisionVid);

    $page = node_view($node_revision);

    return $page;
  }

}
