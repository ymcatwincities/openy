<?php
/**
 * @file
 * Contains \Drupal\ygs_branch_selector\Controller\BranchSelectorController.
 */

namespace Drupal\ygs_branch_selector\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * {@inheritdoc}
 */
class BranchSelectorController extends ControllerBase {

  const EXPIRE_TIME = '+ 365 day';

  /**
   * Set or unset cookie for current user.
   *
   * @param string $js
   *   Nojs|ajax.
   * @param int $id
   *   Node id.
   * @param string $action
   *   Flag|Unflag.
   *
   * @return object $response
   *   Response
   */
  public function ygs_preferred_branch($js = 'nojs', $id = NULL, $action = 'flag') {

    if ($action == 'flag') {
      // Set ygs_preferred_branch cookie.
      setcookie('ygs_preferred_branch', $id, strtotime(self::EXPIRE_TIME), base_path());
    }
    else {
      // Delete ygs_preferred_branch cookie.
      setcookie('ygs_preferred_branch', FALSE, time() - 3600, '/');
    }

    if ($js == 'ajax') {
      // Update link.
      $response = new AjaxResponse();
      $reverce_action = ($action == 'flag') ? 'unflag' : 'flag';
      $link = ygs_branch_selector_get_link($id, $reverce_action);
      $response->addCommand(new ReplaceCommand('.ygs-branch-selector', $link));
      return $response;
    }
    else {
      // Redirect to node page.
      $node_path_alias = \Drupal::service('path.alias_manager')->getAliasByPath(base_path() . "node/$id");
      return new RedirectResponse($node_path_alias);
    }
  }

}
