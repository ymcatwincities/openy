<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests the components of the Scheduler interface which use the Date module.
 *
 * @group scheduler
 */
class SchedulerDefaultTimeTest extends SchedulerBrowserTestBase {

  /**
   * Test the default time functionality.
   */
  public function testDefaultTime() {
    $this->drupalLogin($this->adminUser);
    // Show the timecheck report.
    $this->drupalGet('admin/reports/status');

    // Check that the correct default time is added to the scheduled date.
    // For testing we use an offset of 6 hours 30 minutes (23400 seconds).
    $this->seconds = 23400;
    // If the test happens to be run at a time when '+1 day' puts the calculated
    // publishing date into a different daylight-saving period then formatted
    // time can be an hour different. To avoid these failures we use a fixed
    // string when asserting the message and looking for field values.
    // @see https://www.drupal.org/node/2809627
    $this->seconds_formatted = '06:30:00';
    $edit = [
      'date_format' => 'Y-m-d H:i:s',
      'allow_date_only' => TRUE,
    // Use '6:30' not '06:30:00' to test flexibility.
      'default_time' => '6:30',
    ];
    $this->drupalPostForm('admin/config/content/scheduler', $edit, t('Save configuration'));
    // @TODO Function assertDefaultTime() is only called once. Is there any
    // benefit in having it separate? Why not move the code back into here?
    $this->assertDefaultTime();

    // Check that it is not possible to enter a date format without a time if
    // the 'date only' option is not enabled.
    $edit = [
      'date_format' => 'Y-m-d',
      'allow_date_only' => FALSE,
    ];
    $this->drupalPostForm('admin/config/content/scheduler', $edit, t('Save configuration'));
    $this->assertRaw(t('You must either include a time within the date format or enable the date-only option.'), 'It is not possible to enter a date format without a time if the "date only" option is not enabled.');
  }

  /**
   * Asserts that the default time works as expected.
   */
  protected function assertDefaultTime() {
    // We cannot easily test the exact validation messages as they contain the
    // REQUEST_TIME, which can be one or more seconds in the past. Best we can
    // do is check the fixed part of the message as it is when passed to t() in
    // Datetime::validateDatetime. This will only work in English.
    $publish_validation_message = 'The Publish on date is invalid. Please enter a date in the format';
    $unpublish_validation_message = 'The Unpublish on date is invalid. Please enter a date in the format';

    // First test with the "date only" functionality disabled.
    $this->drupalPostForm('admin/config/content/scheduler', ['allow_date_only' => FALSE], t('Save configuration'));

    // Test if entering a time is required.
    $edit = [
      'title[0][value]' => 'No time ' . $this->randomString(15),
      'publish_on[0][value][date]' => date('Y-m-d', strtotime('+1 day', REQUEST_TIME)),
      'unpublish_on[0][value][date]' => date('Y-m-d', strtotime('+2 day', REQUEST_TIME)),
    ];
    // @todo Use \Drupal::service('date.formatter') instead of calling date()
    // and format_date()
    // Create a node and check that the expected error messages are shown.
    $this->drupalPostForm('node/add/page', $edit, t('Save and publish'));
    $this->assertText($publish_validation_message, 'By default it is required to enter a time when scheduling content for publication.');
    $this->assertText($unpublish_validation_message, 'By default it is required to enter a time when scheduling content for unpublication.');

    // Allow the user to enter only a date.
    $this->drupalPostForm('admin/config/content/scheduler', ['allow_date_only' => TRUE], t('Save configuration'));

    // Create a node and check that the expected error messages are not shown.
    $this->drupalPostForm('node/add/page', $edit, t('Save and publish'));
    $this->assertNoText($publish_validation_message, 'If the default time option is enabled the user can skip the time when scheduling content for publication.');
    $this->assertNoText($unpublish_validation_message, 'If the default time option is enabled the user can skip the time when scheduling content for unpublication.');

    // Check that the publish-on information is shown after saving.
    $publish_time = $edit['publish_on[0][value][date]'] . ' ' . $this->seconds_formatted;
    $args = ['@publish_time' => $publish_time];
    $this->assertRaw(t('This post is unpublished and will be published @publish_time.', $args), 'The user is informed that the content will be published on the requested date, on the default time.');

    // Check that the default time has been added to the scheduler form on edit.
    // Protect in case the node was not created. The tests will fail anyway.
    if ($node = $this->drupalGetNodeByTitle($edit['title[0][value]'])) {
      $this->drupalGet('node/' . $node->id() . '/edit');
    }
    $this->assertFieldByName('publish_on[0][value][time]', $this->seconds_formatted, 'The default time offset has been added to the date field when scheduling content for publication.');
    $this->assertFieldByName('unpublish_on[0][value][time]', $this->seconds_formatted, 'The default time offset has been added to the date field when scheduling content for unpublication.');
  }

}
