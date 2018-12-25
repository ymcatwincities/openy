<?php

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge_ui\Tests\DashboardTestBase;

/**
 * Tests \Drupal\purge_ui\Controller\DashboardController::buildDiagnosticReport().
 *
 * @group purge_ui
 */
class DashboardDiagnosticsTest extends DashboardTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'purge_check_test',
    'purge_check_error_test',
    'purge_check_warning_test',
  ];

  /**
   * Test the visual status report.
   *
   * @see \Drupal\purge_ui\Controller\DashboardController::buildDiagnosticReport
   */
  public function testDiagnosticReport() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertRaw('system-status-report');
    $this->assertRaw('open="open"');
    $this->assertText('Status');
    $this->assertText('Capacity');
    $this->assertText('Queuers');
    $this->assertText('Always a warning');
    $this->assertText('Always informational');
    $this->assertText('Always ok');
    $this->assertText('Always an error');
  }

}
