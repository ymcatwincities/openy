<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\ServiceTestTrait.
 */

namespace Drupal\purge\Tests;

use Drupal\purge\ServiceBase;
use Drupal\purge\ServiceInterface;

/**
 * Properties and methods for services.yml exposed classes.
 *
 * @see \Drupal\purge\Tests\KernelTestBase
 * @see \Drupal\purge\Tests\WebTestBase
 */
trait ServiceTestTrait {

  /**
   * The name of the service as defined in services.yml.
   */
  protected $serviceId;

  /**
   * Instance of the service being tested, instantiated by the container.
   *
   * @var mixed
   */
  protected $service;

  /**
   * Set up the test.
   */
  function setUp() {
    parent::setUp();
    $this->initializeService();
  }

  /**
   * Initialize the requested service as $this->$variable (or reload).
   *
   * @param string $variable
   *   The place to put the loaded/reloaded service, defaults to $this->service.
   * @param string $service
   *   The name of the service to load, defaults to $this->serviceId.
   */
  protected function initializeService($variable = 'service', $service = NULL) {
    if (is_null($this->$variable)) {
      if (is_null($service)) {
        $this->$variable = $this->container->get($this->serviceId);
      }
      else {
        $this->$variable = $this->container->get($service);
      }
    }
    if ($this->$variable instanceof ServiceInterface) {
      $this->$variable->reload();
    }
  }

  /**
   * Test for \Drupal\purge\ServiceBase and \Drupal\purge\ServiceInterface.
   *
   * Services not derived from \Drupal\purge\ServiceInterface, should overload
   * this test. This applies to plugin managers for instance.
   */
  public function testCodeContract() {
    $this->assertTrue($this->service instanceof ServiceInterface);
    $this->assertTrue($this->service instanceof ServiceBase);
  }

}
