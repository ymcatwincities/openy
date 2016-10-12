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
    if (!$action) {
      $action = (!empty($_COOKIE["ygs_preferred_branch"])) ? 'unflag' : 'flag';
    }
    $link_title = t('Save this location as My YMCA');
    if ($action == 'unflag') {
      $link_title = t('Remove as preferred branch.');
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
    $link['#sufix'] = '</div>';
    $link['#attributes'] = array('class' => array('use-ajax'));
    if ($action == 'unflag') {
      // Add text before link if 'unflag' action.
      $link['#prefix'] .= t('This is your current YMCA.');
    }
    // Fix: lazy_builder can't render by only '#type' property.
    $element_info = \Drupal::service('plugin.manager.element_info');
    return $link + $element_info->getInfo('link');
  }

}
