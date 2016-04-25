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

  protected $route;

  /**
   * Constructor to avoid duplicate code in access and preview methods.
   */
  public function __construct() {
    $request = \Drupal::request();
    $this->route = $request->attributes->get('_route_object');

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
    $node_revision_access_check = \Drupal::getContainer()->get('access_check.node.revision');

    // Check if user has access to view latest revision.
    $node_revision_access = $node_revision_access_check->access($this->route, $account, $this->latestRevisionVid);

    if ($this->latestRevisionVid > $this->node->getRevisionId()) {
      return $node_revision_access;
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
