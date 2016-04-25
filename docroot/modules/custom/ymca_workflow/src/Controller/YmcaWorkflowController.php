<?php

namespace Drupal\ymca_workflow\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\node\NodeInterface;

class YmcaWorkflowController {
  protected $latest_revision_vid;

  protected $node;

  public function __construct() {
    $request = \Drupal::request();
    $storage = \Drupal::entityManager()->getStorage('node');

    $this->node = $request->get('node');
    if (!$this->node instanceof NodeInterface) {
      $this->node = node_load($this->node);
    }
    $vids = $storage->revisionIds($this->node);
    $this->latest_revision_vid = array_pop($vids);
  }

  public function access(AccountInterface $account) {
    if ($this->latest_revision_vid > $this->node->getRevisionId()) {
      return new AccessResultAllowed();
    }
    return new AccessResultForbidden();
  }

  public function preview() {
    $node_revision = node_revision_load($this->latest_revision_vid);

    $page = node_view($node_revision);

    return $page;
  }

}
