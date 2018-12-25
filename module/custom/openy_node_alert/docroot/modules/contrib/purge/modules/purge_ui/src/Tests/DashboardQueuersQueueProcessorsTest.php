<?php

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge_ui\Tests\DashboardTestBase;

/**
 * Tests \Drupal\purge_ui\Controller\DashboardController::buildQueuersQueueProcessors().
 *
 * @group purge_ui
 */
class DashboardQueuersQueueProcessorsTest extends DashboardTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_queuer_test', 'purge_processor_test'];

  /**
   * Test the queuers section of the dashboard.
   *
   * @see \Drupal\purge_ui\Controller\DashboardController::buildQueuersQueueProcessors
   */
  public function testQueuersSection() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertRaw('Queuers add items to the queue upon certain events, that processors process later on.');
    $this->assertRaw('Queuer A');
    $this->assertRaw('"/admin/config/development/performance/purge/queuer/a"');
    $this->assertNoRaw('"/admin/config/development/performance/purge/queuer/a/config/dialog"');
    $this->assertRaw('"/admin/config/development/performance/purge/queuer/a/delete"');
    $this->assertRaw('Queuer B');
    $this->assertRaw('"/admin/config/development/performance/purge/queuer/b"');
    $this->assertNoRaw('"/admin/config/development/performance/purge/queuer/b/config/dialog"');
    $this->assertRaw('"/admin/config/development/performance/purge/queuer/b/delete"');
    $this->assertNoRaw('Queuer C');
    $this->assertNoRaw('"/admin/config/development/performance/purge/queuer/c"');
    $this->assertNoRaw('"/admin/config/development/performance/purge/queuer/c/config/dialog"');
    $this->assertNoRaw('"/admin/config/development/performance/purge/queuer/c/delete"');
    $this->initializeQueuersService(['withform']);
    $this->drupalGet($this->route);
    $this->assertRaw('Queuer with form');
    $this->assertRaw('"/admin/config/development/performance/purge/queuer/withform"');
    $this->assertRaw('"/admin/config/development/performance/purge/queuer/withform/config/dialog"');
    $this->assertRaw('"/admin/config/development/performance/purge/queuer/withform/delete"');

    $this->assertRaw('Add queuer');
  }

  /**
   * Test the queue section of the dashboard.
   *
   * @see \Drupal\purge_ui\Controller\DashboardController::buildQueuersQueueProcessors
   */
  public function testQueueSection() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertRaw("Memory");
    $this->assertText("Inspect");
    $this->assertText("Change engine");
    $this->assertText("Empty");
    $this->assertRaw('"/admin/config/development/performance/purge/queue"');
    $this->assertRaw('"/admin/config/development/performance/purge/queue/browser"');
    $this->assertRaw('"/admin/config/development/performance/purge/queue/change"');
    $this->assertRaw('"/admin/config/development/performance/purge/queue/empty"');
  }

  /**
   * Test the processors section of the dashboard.
   *
   * @see \Drupal\purge_ui\Controller\DashboardController::buildQueuersQueueProcessors
   */
  public function testProcessorsSection() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertRaw('Processors are responsible for emptying the queue and putting the purgers to work each time they process. Processors can work the queue constantly or at timed intervals, it is up to you to configure a policy that makes sense for the traffic nature of your website. Multiple processors will not lead to any parallel-processing or conflicts, instead it simply means the queue is checked more often.');
    $this->assertRaw('Processor A');
    $this->assertRaw('"/admin/config/development/performance/purge/processor/a"');
    $this->assertNoRaw('"/admin/config/development/performance/purge/processor/a/config/dialog"');
    $this->assertRaw('"/admin/config/development/performance/purge/processor/a/delete"');
    $this->assertRaw('Processor B');
    $this->assertRaw('"/admin/config/development/performance/purge/processor/b"');
    $this->assertNoRaw('"/admin/config/development/performance/purge/processor/b/config/dialog"');
    $this->assertRaw('"/admin/config/development/performance/purge/processor/b/delete"');
    $this->assertNoRaw('Processor C');
    $this->assertNoRaw('"/admin/config/development/performance/purge/processor/c"');
    $this->assertNoRaw('"/admin/config/development/performance/purge/processor/c/config/dialog"');
    $this->assertNoRaw('"/admin/config/development/performance/purge/processor/c/delete"');
    $this->initializeProcessorsService(['withform']);
    $this->drupalGet($this->route);
    $this->assertRaw('Processor with form');
    $this->assertRaw('"/admin/config/development/performance/purge/processor/withform"');
    $this->assertRaw('"/admin/config/development/performance/purge/processor/withform/config/dialog"');
    $this->assertRaw('"/admin/config/development/performance/purge/processor/withform/delete"');

    $this->assertRaw('Add processor');
  }

}
