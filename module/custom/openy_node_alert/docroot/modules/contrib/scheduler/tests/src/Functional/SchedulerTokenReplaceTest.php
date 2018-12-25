<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Generates text using placeholders to check scheduler token replacement.
 *
 * @group scheduler
 */
class SchedulerTokenReplaceTest extends SchedulerBrowserTestBase {

  /**
   * Creates a node, then tests the tokens generated from it.
   */
  public function testSchedulerTokenReplacement() {
    $this->drupalLogin($this->schedulerUser);
    $date_formatter = \Drupal::service('date.formatter');

    // Define timestamps for consistent use when repeated throughout this test.
    $publish_on_timestamp = REQUEST_TIME + 3600;
    $unpublish_on_timestamp = REQUEST_TIME + 7200;

    // Create an unpublished page with scheduled dates.
    $node = $this->drupalCreateNode([
      'type' => $this->type,
      'status' => FALSE,
      'publish_on' => $publish_on_timestamp,
      'unpublish_on' => $unpublish_on_timestamp,
    ]);
    // Show that the node is scheduled.
    $this->drupalGet('admin/content/scheduled');

    // Create array of test case data.
    // @TODO Convert this test to use @dataProvider instead of array and loop?
    $test_cases = [
      ['token_format' => '', 'date_format' => 'medium', 'custom' => ''],
      ['token_format' => ':long', 'date_format' => 'long', 'custom' => ''],
      ['token_format' => ':raw', 'date_format' => 'custom', 'custom' => 'U'],
      [
        'token_format' => ':custom:jS F g:ia e O',
        'date_format' => 'custom',
        'custom' => 'jS F g:ia e O',
      ],
    ];

    foreach ($test_cases as $test_data) {
      // Edit the node and set the body tokens to use the format being tested.
      $edit = [
        'body[0][value]' => 'Publish on: [node:scheduler-publish' . $test_data['token_format'] . ']. Unpublish on: [node:scheduler-unpublish' . $test_data['token_format'] . '].',
      ];
      $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
      $this->drupalGet('node/' . $node->id());

      // Refresh the node and get the body output value.
      $this->nodeStorage->resetCache([$node->id()]);
      $node = $this->nodeStorage->load($node->id());
      $body_output = \Drupal::token()->replace($node->body->value, ['node' => $node]);

      // Create the expected text for the body.
      $publish_on_date = $date_formatter->format($publish_on_timestamp, $test_data['date_format'], $test_data['custom']);
      $unpublish_on_date = $date_formatter->format($unpublish_on_timestamp, $test_data['date_format'], $test_data['custom']);
      $expected_output = 'Publish on: ' . $publish_on_date . '. Unpublish on: ' . $unpublish_on_date . '.';
      // Check that the actual text matches the expected value.
      $this->assertEqual($body_output, $expected_output, 'Scheduler tokens replaced correctly for ' . $test_data['token_format'] . ' format.');
    }
  }

}
