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

  /**
   * @see \Drupal\simpletest\TestBase::$container
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Triggers a router rebuild.
   *
   * The UI would trigger a router rebuild, call it manually for API calls.
   */
  protected function triggerRouterRebuild() {
    $this->container->get('router.builder')->rebuildIfNeeded();
  }

}
