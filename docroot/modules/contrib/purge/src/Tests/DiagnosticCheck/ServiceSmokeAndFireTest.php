<?php

namespace Drupal\purge\Tests\DiagnosticCheck;

use Drupal\purge\Tests\KernelServiceTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService
 * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface
 */
class ServiceSmokeAndFireTest extends KernelServiceTestBase {
  protected $serviceId = 'purge.diagnostics';
  public static $modules = ['purge_purger_test', 'purge_processor_test'];

  /**
   * Set up the test.
   */
  public function setUp() {

    // Skip parent::setUp() as we don't want the service initialized here.
    KernelServiceTestBase::setUp();
    $this->installConfig(['purge_processor_test']);
  }

  /**
   * Tests:
   *   - \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService::isSystemOnFire()
   *   - \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService::isSystemShowingSmoke()
   */
  public function testIsSystemOnFireOrShowingSmoke() {
    $this->initializePurgersService(['ida' => 'a']);
    $this->initializeService();
    $this->assertTrue(is_object($this->service->isSystemOnFire()));
    $this->assertTrue(is_object($this->service->isSystemShowingSmoke()));
  }

}
