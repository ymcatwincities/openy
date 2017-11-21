<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests the modules primary function - publishing and unpublishing content.
 *
 * @group scheduler
 */
class SchedulerBasicTest extends SchedulerBrowserTestBase {

  /**
   * Tests basic scheduling of content.
   */
  public function testPublishingAndUnpublishing() {
    // Login is required here before creating the publish_on date and time
    // values so that date.formatter can utilise the current users timezone. The
    // constraints receive values which have been converted using the users
    // timezone so they need to be consistent.
    $this->drupalLogin($this->schedulerUser);

    // Create node values. Set time to one hour in the future.
    $edit = [
      'title[0][value]' => 'Publish This Node',
      'publish_on[0][value][date]' => \Drupal::service('date.formatter')->format(time() + 3600, 'custom', 'Y-m-d'),
      'publish_on[0][value][time]' => \Drupal::service('date.formatter')->format(time() + 3600, 'custom', 'H:i:s'),
    ];
    $this->helpTestScheduler($edit);

    // Remove publish_on and set unpublish_on, then run basic tests again.
    $edit['unpublish_on[0][value][date]'] = $edit['publish_on[0][value][date]'];
    $edit['unpublish_on[0][value][time]'] = $edit['publish_on[0][value][time]'];
    unset($edit['publish_on[0][value][date]']);
    unset($edit['publish_on[0][value][time]']);
    // Need a new title for the new node, as we identify the node by title.
    $edit['title[0][value]'] = 'Unpublish This Node';
    $this->helpTestScheduler($edit);
  }

  /**
   * Helper function for testPublishingAndUnpublishing().
   *
   * Schedules content, runs cron and asserts status.
   */
  protected function helpTestScheduler($edit) {
    $this->drupalPostForm('node/add/' . $this->type, $edit, t('Save'));
    // Verify that the node was created.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertTrue($node, sprintf('"%s" was created sucessfully.', $edit['title[0][value]']));
    if (empty($node)) {
      $this->assert(FALSE, 'Test halted because node was not created.');
      return;
    }

    // Assert that the node is correctly published or unpublished.
    if (isset($edit['publish_on[0][value][date]'])) {
      $key = 'publish_on';
      $this->assertFalse($node->isPublished(), 'Node is unpublished before Cron');
    }
    else {
      $key = 'unpublish_on';
      $this->assertTrue($node->isPublished(), 'Node is published before Cron');
    }

    // Modify the scheduler field data to a time in the past, then run cron.
    $node->$key = REQUEST_TIME - 1;
    $node->save();
    $this->cronRun();

    // Refresh the node cache and check the node status.
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());
    if ($key == 'publish_on') {
      $this->assertTrue($node->isPublished(), 'Node is published after Cron');
    }
    else {
      $this->assertFalse($node->isPublished(), 'Node is unpublished after Cron');
    }
  }

}
