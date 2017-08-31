<?php

namespace Drupal\Tests\scheduler\Functional;

use Drupal\Component\Utility\SafeMarkup;

/**
 * Tests the options and processing when dates are entered in the past.
 *
 * @group scheduler
 */
class SchedulerPastDatesTest extends SchedulerBrowserTestBase {

  /**
   * Test the different options for past publication dates.
   */
  public function testSchedulerPastDates() {
    // Log in.
    $this->drupalLogin($this->schedulerUser);

    // Ensure that neither of the scheduling dates are set to be required.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_required', FALSE)
      ->setThirdPartySetting('scheduler', 'unpublish_required', FALSE)->save();

    // Create an unpublished page node.
    $node = $this->drupalCreateNode(['type' => $this->type, 'status' => FALSE]);

    // Test the default behavior: an error message should be shown when the user
    // enters a publication date that is in the past.
    $edit = [
      'title[0][value]' => 'Past ' . $this->randomString(10),
      'publish_on[0][value][date]' => \Drupal::service('date.formatter')->format(strtotime('-1 day', REQUEST_TIME), 'custom', 'Y-m-d'),
      'publish_on[0][value][time]' => \Drupal::service('date.formatter')->format(strtotime('-1 day', REQUEST_TIME), 'custom', 'H:i:s'),
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertRaw(t("The 'publish on' date must be in the future"), 'An error message is shown by default when the publication date is in the past.');

    // Test the 'error' behavior explicitly.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_past_date', 'error')->save();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertRaw(t("The 'publish on' date must be in the future"), 'An error message is shown when the publication date is in the past and the "error" behavior is chosen.');

    // Test the 'publish' behavior: the node should be published immediately.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_past_date', 'publish')->save();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertNoText(t("The 'publish on' date must be in the future"), 'No error message is shown when the publication date is in the past and the "publish" behavior is chosen.');
    $this->assertText(sprintf('%s %s has been updated.', $this->typeName, SafeMarkup::checkPlain($edit['title[0][value]'])), 'The node is saved successfully when the publication date is in the past and the "publish" behavior is chosen.');

    // Reload the changed node and check that it is published.
    $this->nodeStorage->resetCache([$node->id()]);

    /** @var NodeInterface $node */
    $node = $this->nodeStorage->load($node->id());
    $this->assertTrue($node->isPublished(), 'The node has been published immediately when the publication date is in the past and the "publish" behavior is chosen.');
    $this->assertNull($node->publish_on->value, 'The node publish_on date has been removed after publishing when the "publish" behavior is chosen.');

    // Test the 'schedule' behavior: the node should be unpublished and become
    // published on the next cron run.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_past_date', 'schedule')->save();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertNoText(t("The 'publish on' date must be in the future"), 'No error message is shown when the publication date is in the past and the "schedule" behavior is chosen.');
    $this->assertText(sprintf('%s %s has been updated.', $this->typeName, SafeMarkup::checkPlain($edit['title[0][value]'])), 'The node is saved successfully when the publication date is in the past and the "schedule" behavior is chosen.');
    $this->assertText('will be published', 'The node is scheduled to be published when the publication date is in the past and the "schedule" behavior is chosen.');

    // Reload the node and check that it is unpublished but scheduled correctly.
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertFalse($node->isPublished(), 'The node has been unpublished when the publication date is in the past and the "schedule" behavior is chosen.');
    $this->assertEqual($node->publish_on->value, strtotime('-1 day', REQUEST_TIME), 'The node has the correct publish_on date stored.');

    // Simulate a cron run and check that the node is published.
    scheduler_cron();
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    $this->assertTrue($node->isPublished(), 'The node with publication date in the past and the "schedule" behavior has now been published by cron.');

    // Check that an Unpublish date in the past fails validation.
    $edit = [
      'title[0][value]' => 'Unpublish in the past ' . $this->randomString(10),
      'unpublish_on[0][value][date]' => \Drupal::service('date.formatter')->format(REQUEST_TIME - 3600, 'custom', 'Y-m-d'),
      'unpublish_on[0][value][time]' => \Drupal::service('date.formatter')->format(REQUEST_TIME - 3600, 'custom', 'H:i:s'),
    ];
    $this->drupalPostForm('node/add/' . $this->type, $edit, t('Save'));
    $this->assertRaw(t("The 'unpublish on' date must be in the future"), 'An error message is shown when the unpublish date is in the past.');
  }

}
