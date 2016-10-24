<?php
/**
 * @file
 * Contains \Drupal\ygs_branch_selector\BranchLinkGenerator.
 */

namespace Drupal\ygs_branch_selector;
/**
 * Class BranchLinkGenerator.
 *
 * @package Drupal\ygs_branch_selector
 */
class BranchLinkGenerator {
  /**
   * Generate link for My YMCA location.
   */
  public function generateLink($nid, $action = FALSE) {
    $force = FALSE;
    if (!$action) {
      $action = (!empty($_COOKIE["ygs_preferred_branch"])) ? 'unflag' : 'flag';
    }
    elseif ($action == 'unflag') {
      // Only for ajax request (we have manual set $action).
      $force = TRUE;
    }
    $link_title = t('Save as preferred branch.');
    if (($action == 'unflag' && isset($_COOKIE["ygs_preferred_branch"]) && $_COOKIE["ygs_preferred_branch"] == $nid) || $force) {
      // If current branch was set as current YMCA.
      $link_title = t('This is your preferred branch, remove as preferred branch');
    }
    else {
      // For update current YMCA branch.
      $action = 'flag';
    }
    $url = \Drupal\Core\Url::fromRoute('ygs_branch_selector.location_set', array(
      'js' => 'nojs',
      'id' => $nid,
      'action' => $action,
    ));
    $link = \Drupal\Core\Link::fromTextAndUrl($link_title, $url);
    $link = $link->toRenderable();
    // Add wrapper to element for ajax replace.
    $link['#prefix'] = '<div class="ygs-branch-selector">';
    $link['#suffix'] = '</div>';
    $link['#attributes'] = array('class' => array('use-ajax'));
    $link['#attached'] = array('library' => array('core/drupal.ajax'));
    // Fix: lazy_builder can't render by only '#type' property.
    $element_info = \Drupal::service('plugin.manager.element_info');
    return $link + $element_info->getInfo('link');
  }

}
