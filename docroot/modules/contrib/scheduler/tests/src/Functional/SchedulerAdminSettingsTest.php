<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests the admin settings page of Scheduler.
 *
 * @group scheduler
 */
class SchedulerAdminSettingsTest extends SchedulerBrowserTestBase {

  /**
   * Test the admin settings page.
   */
  public function testAdminSettings() {
    $this->drupalLogin($this->adminUser);

    // Check that the correct default time is added to the scheduled date.
    // For testing we use an offset of 6 hours 30 minutes (23400 seconds).
    $this->seconds = 23400;
    // If the test happens to be run at a time when '+1 day' puts the calculated
    // publishing date into a different daylight-saving period then formatted
    // time can be an hour different. To avoid these failures we use a fixed
    // string when asserting the message and looking for field values.
    // @see https://www.drupal.org/node/2809627
    $this->seconds_formatted = '06:30:00';
    // In $edit use '6:30' not '06:30:00' to test flexibility.
    $settings = [
      'allow_date_only' => TRUE,
      'default_time' => '6:30',
    ];
    $this->drupalPostForm('admin/config/content/scheduler', $settings, t('Save configuration'));

    // Verify that the values have been saved correctly.
    $this->assertTrue($this->config('scheduler.settings')->get('allow_date_only'), 'The config setting for allow_date_only is stored correctly.');
    $this->assertEqual($this->config('scheduler.settings')->get('default_time'), $this->seconds_formatted, 'The config setting for default_time is stored correctly.');

    // Try to save an invalid time value.
    $settings = [
      'allow_date_only' => TRUE,
      'default_time' => '123',
    ];
    $this->drupalPostForm('admin/config/content/scheduler', $settings, t('Save configuration'));
    // Verify that an error is displayed and the value has not been saved.
    $this->assertEqual($this->config('scheduler.settings')->get('default_time'), $this->seconds_formatted, 'The config setting for default_time has not changed.');
    $this->assertText('The default time should be in the format HH:MM:SS', 'When an invalid default time is entered the correct error message is displayed.');

    // Show the status report, which includes the Scheduler timecheck.
    $this->drupalGet('admin/reports/status');
  }

}
