<?php

namespace Drupal\purge\Tests\DiagnosticCheck;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Tests\KernelServiceTestBase;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;

/**
 * Tests \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService
 * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface
 */
class ServiceTest extends KernelServiceTestBase {
  protected $serviceId = 'purge.diagnostics';
  public static $modules = [
    'purge_purger_test',
    'purge_processor_test',
    'purge_check_test',
    'purge_check_error_test',
    'purge_check_warning_test',
  ];

  /**
   * The supported test severities.
   *
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_INFO
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_OK
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_WARNING
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_ERROR
   */
  protected $severities = [
    DiagnosticCheckInterface::SEVERITY_INFO,
    DiagnosticCheckInterface::SEVERITY_OK,
    DiagnosticCheckInterface::SEVERITY_WARNING,
    DiagnosticCheckInterface::SEVERITY_ERROR,
  ];

  /**
   * The hook_requirements() severities from install.inc.
   *
   * @see REQUIREMENT_INFO
   * @see REQUIREMENT_OK
   * @see REQUIREMENT_WARNING
   * @see REQUIREMENT_ERROR
   */
  protected $requirementSeverities = [];

  /**
   * Set up the test.
   */
  public function setUp() {

    // Skip parent::setUp() as we don't want the service initialized here.
    KernelServiceTestBase::setUp();
    $this->installConfig(['purge_processor_test']);
  }

  /**
   * Include install.inc and initialize $this->requirementSeverities.
   */
  protected function initializeRequirementSeverities() {
    if (empty($this->requirementSeverities)) {
      include_once DRUPAL_ROOT . '/core/includes/install.inc';
      $this->requirementSeverities[] = REQUIREMENT_INFO;
      $this->requirementSeverities[] = REQUIREMENT_OK;
      $this->requirementSeverities[] = REQUIREMENT_WARNING;
      $this->requirementSeverities[] = REQUIREMENT_ERROR;
    }
  }

  /**
   * Tests lazy loading of the 'purge.purger' and 'purge.queue' services.
   *
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService::__construct
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService::initializeChecks
   */
  public function testLazyLoadingOfDependencies() {
    $this->assertFalse($this->container->initialized('purge.purgers'));
    $this->assertFalse($this->container->initialized('purge.queue'));
    $this->initializeService();
    $this->assertFalse($this->container->initialized('purge.purgers'));
    $this->assertFalse($this->container->initialized('purge.queue'));

    // All the helpers on the service - except the constructor - lazy load the
    // services, but only when any of the check plugins require them. In this
    // case the 'memoryqueuewarning' plugin will trigger the queue and the
    // 'capacity' and 'purgersavailable' plugins will load 'purge.purgers'.
    $this->service->isSystemOnFire();
    $this->assertTrue($this->container->initialized('purge.purgers'));
    $this->assertTrue($this->container->initialized('purge.queue'));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService::count
   */
  public function testCount() {
    $this->initializeService();
    $this->assertTrue($this->service instanceof \Countable);
    $this->assertEqual(11, count($this->service));
  }

  /**
   * Tests the \Iterator implementation.
   *
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService::current
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService::key
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService::next
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService::rewind
   * @see \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService::valid
   */
  public function testIteration() {
    $this->initializeService();
    $this->assertIterator('\Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface',
      [
        'queuersavailable',
        'purgersavailable',
        'maxage',
        'capacity',
        'processorsavailable',
        'memoryqueuewarning',
        'page_cache',
        'alwaysok',
        'alwaysinfo',
        'alwayserror',
        'alwayswarning',
      ]
    );
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService::getHookRequirementsArray
   */
  public function testGetHookRequirementsArray() {
    $this->initializeRequirementSeverities();
    $this->initializeService();
    $requirements = $this->service->getHookRequirementsArray();
    $this->assertEqual(11, count($requirements));
    foreach ($requirements as $id => $requirement) {
      $this->assertTrue(is_string($id));
      $this->assertFalse(empty($id));
      $this->assertTrue(is_string($requirement['title']) || ($requirement['title'] instanceof TranslatableMarkup));
      $this->assertFalse(empty($requirement['title']));
      $this->assertTrue((is_string($requirement['description']) || $requirement['description'] instanceof TranslatableMarkup));
      $this->assertFalse(empty($requirement['description']));
      $this->assertTrue(in_array($requirement['severity'], $this->requirementSeverities));
    }
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService::isSystemOnFire.
   */
  public function testIsSystemOnFire() {
    $this->initializePurgersService(['ida' => 'a']);
    $this->service->reload();
    $this->assertTrue($this->service->isSystemOnFire() instanceof DiagnosticCheckInterface);
    $possibilities = ['alwayserror', 'maxage'];
    $this->assertTrue(in_array($this->service->isSystemOnFire()->getPluginId(), $possibilities));
  }

  /**
   * Tests \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService::isSystemShowingSmoke.
   */
  public function testIsSystemShowingSmoke() {
    $this->assertTrue($this->service->isSystemShowingSmoke() instanceof DiagnosticCheckInterface);
    $possibilities = ['alwayswarning', 'capacity', 'queuersavailable', 'page_cache'];
    $this->assertTrue(in_array($this->service->isSystemShowingSmoke()->getPluginId(), $possibilities));
  }

}
