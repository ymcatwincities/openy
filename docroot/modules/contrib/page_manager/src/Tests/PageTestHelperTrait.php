<?php

/**
 * @file
 * Contains \Drupal\page_manager\Tests\PageTestHelperTrait.
 */

namespace Drupal\page_manager\Tests;

/**
 * Provides helpers for Page Manager tests.
 */
trait PageTestHelperTrait {

  // @fixme: Remove this change when https://www.drupal.org/node/2684281 is fixed.

  /**
   * Triggers a router rebuild.
   *
   * The UI would trigger a router rebuild, call it manually for API calls.
   */
  protected function triggerRouterRebuild() {
    $this->container->get('router.builder')->rebuildIfNeeded();
  }

}
