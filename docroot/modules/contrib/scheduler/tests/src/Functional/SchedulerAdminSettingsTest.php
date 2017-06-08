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
   *
   * The default time functionality is covered separately in the DefaultTime
   * test file.
   */
  public function testAdminSettings() {
    $this->drupalLogin($this->adminUser);
    $config = $this->config('scheduler.settings');
    $time_letters = $config->get('time_letters');
    $date_letters = $config->get('date_letters');

    // Save the form with no change from default values.
    $this->drupalPostForm('admin/config/content/scheduler', [], t('Save configuration'));
    $this->assertText(sprintf('The date part of the Scheduler format is %s and the time part is %s.', 'Y-m-d', 'H:i:s'), 'The save message correctly shows the default format date and time parts.');

    // Set a different but valid date and time format.
    $edit = ['date_format' => 'd.m.Y H:i'];
    $this->drupalPostForm('admin/config/content/scheduler', $edit, t('Save configuration'));
    $this->assertText(sprintf('The date part of the Scheduler format is %s and the time part is %s.', 'd.m.Y', 'H:i'), 'The save message correctly shows the changed format date and time parts.');

    // Set a date format with no time, and allow date only.
    $edit = ['date_format' => 'Y/m/d', 'allow_date_only' => TRUE];
    $this->drupalPostForm('admin/config/content/scheduler', $edit, t('Save configuration'));
    $this->assertText(sprintf('The date part of the Scheduler format is %s. There is no time part', 'Y/m/d'), 'The save message correctly shows the date part with no time part.');

    // Try to save a format with no time, without allowing date only.
    $edit = ['date_format' => 'Y/m/d', 'allow_date_only' => FALSE];
    $this->drupalPostForm('admin/config/content/scheduler', $edit, t('Save configuration'));
    $this->assertText(sprintf('You must either include a time within the date format or enable the date-only option.', $time_letters), 'The correct error message is shown when no time part is entered.');

    // Try to save a format with no date part.
    $edit = ['date_format' => 'H:i:s'];
    $this->drupalPostForm('admin/config/content/scheduler', $edit, t('Save configuration'));
    $this->assertText(sprintf('You must enter a valid date part for the format. Use the letters %s', $date_letters), 'The correct error message is shown when no date part is entered.');

    // Try to save with invalid date format letters.
    $edit = ['date_format' => 'd/m/ZY XH:i'];
    $this->drupalPostForm('admin/config/content/scheduler', $edit, t('Save configuration'));
    $this->assertText(sprintf('You may only use the letters %s for the date and %s for the time. Remove the extra characters %s', $date_letters, $time_letters, 'Z X'), 'The correct error message is shown when invalid letters are entered.');
  }

}
