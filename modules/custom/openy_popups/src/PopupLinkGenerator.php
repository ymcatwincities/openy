<?php

namespace Drupal\openy_popups;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Class PopupLinkGenerator.
 *
 * @package Drupal\openy_branch_selector
 */
class PopupLinkGenerator implements TrustedCallbackInterface {

  /**
   * Generate link for My YMCA location.
   *
   * @param string $type
   *   Type of link.
   * @param int $nid
   *   Class node ID.
   *
   * @return array
   *   Render array with link.
   */
  public function generateLink($type, $nid = 0) {
    // Get destination with query string.
    $destination = \Drupal::request()->getRequestUri();
    // Create popup link url.
    if ($nid && $type == 'class') {
      // For class branches.
      $url = Url::fromRoute('openy_popups.class_branch', [
        'node' => $nid,
        'js' => 'nojs',
        'destination' => $destination,
      ]);
    }
    elseif ($nid && $type == 'category') {
      $url = Url::fromRoute('openy_popups.branch', [
        'node' => $nid,
        'js' => 'nojs',
        'destination' => $destination,
      ]);
    }
    else {
      $url = Url::fromRoute('openy_popups.branch', [
        'js' => 'nojs',
        'destination' => $destination,
      ]);
    }
    $link = Link::fromTextAndUrl(t('Popup link'), $url);
    $link = $link->toRenderable();
    $link['#attributes'] = [
      'class' => [
        'location-popup-link',
        'use-ajax',
        'js-hide',
      ],
      'data-dialog-type' => 'modal',
    ];
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
    if (!empty($node = \Drupal::routeMatch()->getParameter('node')) && in_array($node->bundle(), ['branch', 'camp'])) {
      $show_popup = FALSE;
    }
    return $show_popup;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['generateLink'];
  }

}
