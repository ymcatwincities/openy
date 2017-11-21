<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests the default time functionality.
 *
 * @group scheduler
 */
class SchedulerDefaultTimeTest extends SchedulerBrowserTestBase {

  /**
   * Test the default time functionality during content creation and edit.
   */
  public function testDefaultTime() {
    $this->drupalLogin($this->schedulerUser);
    $config = $this->config('scheduler.settings');
    $date_formatter = \Drupal::service('date.formatter');

    // For this test we use an offset of 6 hours 30 minutes (23400 seconds).
    $seconds = 23400;

    // If the test happens to be run at a time when '+1 day' puts the calculated
    // publishing date into a different daylight-saving period then formatted
    // time can be an hour different. To avoid these failures we use a fixed
    // string when asserting the message and looking for field values.
    // @see https://www.drupal.org/node/2809627
    $seconds_formatted = '06:30:00';
    $config->set('default_time', $seconds_formatted)->save();

    // We cannot easily test the exact validation messages as they contain the
    // REQUEST_TIME, which can be one or more seconds in the past. Best we can
    // do is check the fixed part of the message as it is when passed to t() in
    // Datetime::validateDatetime. This will only work in English.
    $publish_validation_message = 'The Publish on date is invalid.';
    $unpublish_validation_message = 'The Unpublish on date is invalid.';

    // First test with the "date only" functionality disabled.
    $config->set('allow_date_only', FALSE)->save();

    // Test that entering a time is required.
    $edit = [
      'title[0][value]' => 'No time ' . $this->randomString(15),
      'publish_on[0][value][date]' => $date_formatter->format(strtotime('+1 day', REQUEST_TIME), 'custom', 'Y-m-d'),
      'unpublish_on[0][value][date]' => $date_formatter->format(strtotime('+2 day', REQUEST_TIME), 'custom', 'Y-m-d'),
    ];
    // Create a node and check that the expected error messages are shown.
    $this->drupalPostForm('node/add/' . $this->type, $edit, t('Save'));
    $this->assertSession()->pageTextContains($publish_validation_message, 'By default it is required to enter a time when scheduling content for publication.');
    $this->assertSession()->pageTextContains($unpublish_validation_message, 'By default it is required to enter a time when scheduling content for unpublication.');

    // Allow the user to enter only a date with no time.
    $config->set('allow_date_only', TRUE)->save();

    // Create a node and check that the expected error messages are not shown.
    $this->drupalPostForm('node/add/' . $this->type, $edit, t('Save'));
    $this->assertSession()->pageTextNotContains($publish_validation_message, 'If the default time option is enabled the user can skip the time when scheduling content for publication.');
    $this->assertSession()->pageTextNotContains($unpublish_validation_message, 'If the default time option is enabled the user can skip the time when scheduling content for unpublication.');

    // Check that the scheduled information is shown after saving.
    $publish_time = strtotime('+1 day midnight', REQUEST_TIME) + $seconds;
    $unpublish_time = strtotime('+2 day midnight', REQUEST_TIME) + $seconds;
    $args = ['@publish_time' => $date_formatter->format($publish_time, 'long')];
    $this->assertRaw(t('This post is unpublished and will be published @publish_time.', $args), 'The user is informed that the content will be published on the requested date, on the default time.');

    // Protect in case the node was not created.
    if ($node = $this->drupalGetNodeByTitle($edit['title[0][value]'])) {
      // Check that the correct scheduled dates are stored in the node.
      $this->assertEqual($node->publish_on->value, $publish_time, 'The node publish_on value is stored correctly.');
      $this->assertEqual($node->unpublish_on->value, $unpublish_time, 'The node unpublish_on value is stored correctly.');

      // Check that the default time has been added to the form on edit.
      $this->drupalGet('node/' . $node->id() . '/edit');
      $this->assertFieldByName('publish_on[0][value][time]', $seconds_formatted, 'The default time offset has been added to the date field when scheduling content for publication.');
      $this->assertFieldByName('unpublish_on[0][value][time]', $seconds_formatted, 'The default time offset has been added to the date field when scheduling content for unpublication.');

    }
    else {
      $this->fail('The expected node was not found.');
    }
  }

}
