<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests the Scheduler section of the status report.
 *
 * @group scheduler
 */
class SchedulerStatusReportTest extends SchedulerBrowserTestBase {

  /**
   * Tests that the Scheduler Time Check report is shown.
   */
  public function testStatusReport() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/reports/status');

    $this->assertText('Time Check');
    $this->assertText('In most cases the server time matches Coordinated Universal Time (UTC)');

    $admin_regional_settings = \Drupal::url('system.regional_settings');
    $this->assertLink('changed by admin users');
    $this->assertLinkByHref($admin_regional_settings);

    $account_edit = \Drupal::url('entity.user.edit_form', ['user' => $this->adminUser->id()]);
    $this->assertLink('user account');
    $this->assertLinkByHref($account_edit);
  }

}
