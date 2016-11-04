<?php

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
        'location-popup-link',
        'use-ajax',
        'js-hide',
      ),
      'data-dialog-type' => 'modal',
    );
    if ($this->checkRequestParams()) {
      $link['#attributes']['class'][] = 'popup-autostart';
    }
    $link['#cache'] = [
      'contexts' => [
        'url.path',
        'url.query_args:location',
      ],
    ];
    // Fix: lazy_builder can't render by only '#type' property.
    $element_info = \Drupal::service('plugin.manager.element_info');
    return $link + $element_info->getInfo('link');
  }

  /**
   * Check for required popup output.
   */
  private function checkRequestParams() {
    $show_popup = TRUE;
    if (!empty($_REQUEST['location'])) {
      $show_popup = FALSE;
    }
    if (!empty($_COOKIE['ygs_preferred_branch'])) {
      $show_popup = FALSE;
    }
    return $show_popup;
  }

}
