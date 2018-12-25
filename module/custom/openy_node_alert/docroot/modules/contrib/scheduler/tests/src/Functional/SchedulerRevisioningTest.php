<?php

namespace Drupal\Tests\scheduler\Functional;

use Drupal\node\NodeInterface;

/**
 * Tests revision options when Scheduler publishes or unpublishes content.
 *
 * @group scheduler
 */
class SchedulerRevisioningTest extends SchedulerBrowserTestBase {

  /**
   * Simulates the scheduled (un)publication of a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to schedule.
   * @param string $action
   *   The action to perform: either 'publish' or 'unpublish'. Defaults to
   *   'publish'.
   *
   * @return \Drupal\node\NodeInterface
   *   The updated node, after scheduled (un)publication via a cron run.
   */
  protected function schedule(NodeInterface $node, $action = 'publish') {
    // Simulate scheduling by setting the (un)publication date in the past and
    // running cron.
    $node->{$action . '_on'} = strtotime('-1 day', REQUEST_TIME);
    $node->save();
    scheduler_cron();
    $this->nodeStorage->resetCache([$node->id()]);
    return $this->nodeStorage->load($node->id());
  }

  /**
   * Check if the latest revision log message of a node matches a given string.
   *
   * @param int $nid
   *   The node id of the node to check.
   * @param string $value
   *   The value with which the log message will be compared.
   * @param string $message
   *   The message to display along with the assertion.
   * @param string $group
   *   The type of assertion - examples are "Browser", "PHP".
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertRevisionLogMessage($nid, $value, $message = '', $group = 'Other') {
    // Retrieve the latest revision log message for this node.
    $log_message = $this->database->select('node_revision', 'r')
      ->fields('r', ['revision_log'])
      ->condition('nid', $nid)
      ->orderBy('vid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchColumn();

    return $this->assertEqual($log_message, $value, $message, $group);
  }

  /**
   * Check if the number of revisions for a node matches a given value.
   *
   * @param int $nid
   *   The node id of the node to check.
   * @param string $value
   *   The value with which the number of revisions will be compared.
   * @param string $message
   *   The message to display along with the assertion.
   * @param string $group
   *   The type of assertion - examples are "Browser", "PHP".
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertRevisionCount($nid, $value, $message = '', $group = 'Other') {
    $count = \Drupal::database()->select('node_revision', 'r')
      ->condition('nid', $nid)
      ->countQuery()
      ->execute()
      ->fetchColumn();

    return $this->assertEqual($count, $value, $message, $group);
  }

  /**
   * Tests the creation of new revisions on scheduling.
   */
  public function testRevisioning() {
    // Create a scheduled node that is not automatically revisioned.
    $created = strtotime('-2 day', REQUEST_TIME);
    $settings = [
      'type' => $this->type,
      'revision' => 0,
      'created' => $created,
    ];
    $node = $this->drupalCreateNode($settings);

    // Ensure nodes with past dates will be scheduled not published immediately.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_past_date', 'schedule')->save();

    // First test scheduled publication with revisioning disabled by default.
    $node = $this->schedule($node);
    $this->assertRevisionCount($node->id(), 1, 'No new revision is created by default when a node is published.');

    // Test scheduled unpublication.
    $node = $this->schedule($node, 'unpublish');
    $this->assertRevisionCount($node->id(), 1, 'No new revision is created by default when a node is unpublished.');

    // Enable revisioning.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_revision', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_revision', TRUE)
      ->save();

    // Test scheduled publication with revisioning enabled.
    $node = $this->schedule($node);
    $this->assertRevisionCount($node->id(), 2, 'A new revision was created when revisioning is enabled.');
    $expected_message = sprintf('Node published by Scheduler on %s. Previous creation date was %s.',
      \Drupal::service('date.formatter')->format(REQUEST_TIME, 'short'),
      \Drupal::service('date.formatter')->format($created, 'short'));
    $this->assertRevisionLogMessage($node->id(), $expected_message, 'The correct message was found in the node revision log after scheduled publishing.');

    // Test scheduled unpublication with revisioning enabled.
    $node = $this->schedule($node, 'unpublish');
    $this->assertRevisionCount($node->id(), 3, 'A new revision was created when a node was unpublished with revisioning enabled.');
    $expected_message = sprintf('Node unpublished by Scheduler on %s. Previous change date was %s.',
      \Drupal::service('date.formatter')->format(REQUEST_TIME, 'short'),
      \Drupal::service('date.formatter')->format(REQUEST_TIME, 'short'));
    $this->assertRevisionLogMessage($node->id(), $expected_message, 'The correct message was found in the node revision log after scheduled unpublishing.');
  }

  /**
   * Tests the 'touch' option to alter the node created date during publishing.
   */
  public function testAlterCreationDate() {
    // Ensure nodes with past dates will be scheduled not published immediately.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_past_date', 'schedule')->save();

    // Create a node with a 'created' date two days in the past.
    $created = strtotime('-2 day', REQUEST_TIME);
    $settings = [
      'type' => $this->type,
      'created' => $created,
      'status' => FALSE,
    ];
    $node = $this->drupalCreateNode($settings);
    // Show that the node is not published.
    $this->assertFalse($node->isPublished(), 'The node is not published.');

    // Schedule the node for publishing and run cron.
    $node = $this->schedule($node, 'publish');
    // Get the created date from the node and check that it has not changed.
    $created_after_cron = $node->created->value;
    $this->assertTrue($node->isPublished(), 'The node has been published.');
    $this->assertEqual($created, $created_after_cron, 'The node creation date is not changed by default.');

    // Set option to change the created date to match the publish_on date.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_touch', TRUE)->save();

    // Schedule the node again and run cron.
    $node = $this->schedule($node, 'publish');
    // Check that the created date has changed to match the publish_on date.
    $created_after_cron = $node->created->value;
    $this->assertEqual(strtotime('-1 day', REQUEST_TIME), $created_after_cron, "With 'touch' option set, the node creation date is changed to match the publishing date.");
  }

}
