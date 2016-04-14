<?php

/**
 * @file
 * Contains \Drupal\page_manager_test\Controller\PageManagerTestController.
 */

namespace Drupal\page_manager_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route controller for Page Manager Test.
 */
class PageManagerTestController extends ControllerBase {

  /**
   * Returns a render array for this page.
   */
  public function helloWorld($page) {
    return ['#markup' => "Hello World! Page $page"];
  }

}
