<?php

/**
 * @file
 * Contains \Drupal\purge_ui\Tests\DashboardEmptyTest.
 */

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge_ui\Tests\DashboardTestBase;

/**
 * Tests \Drupal\purge_ui\Controller\DashboardController in almost default (no modules state).
 *
 * @group purge_ui
 */
class DashboardEmptyTest extends DashboardTestBase {

  /**
   * Test the visual status report.
   *
   * @see \Drupal\purge_ui\Controller\DashboardController::buildDiagnosticReport
   */
  public function testFormDiagnosticReport() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertRaw('You have no queuers populating the queue!');
    $this->assertRaw('There is no purging capacity available.');
    $this->assertRaw('There is no purger loaded which means that you need a module enabled to provide a purger plugin to clear your external cache or CDN.');
    $this->assertRaw('You have no processors, the queue can now build up because of this.');
  }

  /**
   * Test that a unconfigured pipeline results in 'nothing available' messages.
   */
  public function testMissingMessages() {
    $this->assertRaw('Please install a module to add at least one queuer.');
    $this->assertNoRaw('Add queuer');
    $this->assertRaw('Please install a module to add at least one purger.');
    $this->assertNoRaw('Add purger');
    $this->assertRaw('Please install a module to add at least one processor.');
    $this->assertNoRaw('Add processor');
  }

}
