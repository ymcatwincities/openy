<?php
// @codingStandardsIgnoreFile

namespace Drupal\tqextension\Controller;

use Drupal\Core\Controller\ControllerBase;

class Pages extends ControllerBase {

  public function jsErrors() {
    $page = [];

    // Ensure that jQuery and our injected script will be on the page.
    $page['#attached']['library'][] = 'system/drupal.system';

    $page['#attached']['html_head'][] = [
      [
        '#tag' => 'script',
        '#type' => 'html_tag',
        '#value' => 'console.l0g(12)',

      ],
      'js-errors',
    ];

    return $page;
  }

}
