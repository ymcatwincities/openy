<?php
/**
 * @file
 * Contains \Drupal\ygs_popups\PopupLinkGenerator.
 */

namespace Drupal\ygs_popups;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class PopupLinkGenerator.
 *
 * @package Drupal\ygs_branch_selector
 */
class PopupLinkGenerator {
  /**
   * Generate link for My YMCA location.
   *
   * @param int $nid
   *   Class node ID.
   *
   * @return array
   *   Render array with link.
   */
  public function generateLink($nid = 0) {
    if (!$this->checkRequestParams()) {
      return array();
    }

    // Get destination with query string.
    $destination = \Drupal::request()->getRequestUri();

    // Create popup link url.
    if ($nid) {
      // For class branches.
      $url = Url::fromRoute('ygs_popups.class_branch', array(
        'node' => $nid,
        'js' => 'nojs',
        'destination' => $destination,
      ));
    }
    else {
      $url = Url::fromRoute('ygs_popups.branch', array(
        'js' => 'nojs',
        'destination' => $destination,
      ));
    }
    $link = Link::fromTextAndUrl(t('Popup link'), $url);
    $link = $link->toRenderable();
    $link['#attributes'] = array(
      'class' => array(
        'use-ajax',
        'popup-autostart',
        'js-hide',
      ),
      'data-dialog-type' => 'modal',
    );
    $link['#attached'] = array('library' => array('ygs_popups/ygs_popups.autoload'));

    // Fix: lazy_builder can't render by only '#type' property.
    $element_info = \Drupal::service('plugin.manager.element_info');
    return $link + $element_info->getInfo('link');
  }

  /**
   * Check for required popup output.
   */
  private function checkRequestParams() {
    if (!isset($_REQUEST['location'])) {
      // Show popup.
      return TRUE;
    }
    return FALSE;
  }

}
