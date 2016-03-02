<?php

/**
 * @file
 * Contains \Drupal\scheduler\Tests\SchedulerTestBase
 */

namespace Drupal\scheduler\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Provides common helper methods for Scheduler module tests.
 */
abstract class SchedulerTestBase extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * A user with administration rights.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Helper function for testScheduler(). Schedules content and asserts status.
   */
  protected function helpTestScheduler($edit) {
    // Add a page, using randomMachineName for the body text, not randomString,
    // because assertText works better without difficult non-alpha characters.
    $body = $this->randomMachineName(30);

    $edit['body[0][value]'] = $body;
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('node/add/page', $edit, t('Save and publish'));
    // Show the site front page for an anonymous visitor, then assert that the
    // node is correctly published or unpublished.
    $this->drupalLogout();
    $this->drupalGet('node');
    if (isset($edit['publish_on[0][value][date]'])) {
      $key = 'publish_on';
      $this->assertNoText($body, t('Node is unpublished before Cron'));
    }
    else {
      $key = 'unpublish_on';
      $this->assertText($body, t('Node is published before Cron'));
    }

    // Modify the scheduler field data to a time in the past, then run cron.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    db_update('node_field_data')->fields(array($key => time() - 1))->condition('nid', $node->id())->execute();

    $this->cronRun();
    // Show the site front page for an anonymous visitor, then assert that the
    // node is correctly published or unpublished.
    $this->drupalGet('node');
    if (isset($edit['publish_on[0][value][date]'])) {
      $this->assertText($body, t('Node is published after Cron'));
    }
    else {
      $this->assertNoText($body, t('Node is unpublished after Cron'));
    }
  }

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
    $node_storage = $this->container->get('entity.manager')->getStorage('node');

    // Simulate scheduling by setting the (un)publication date in the past and
    // running cron.
    $node->{$action . '_on'} = strtotime('-1 day');
    $node->save();
    scheduler_cron();
    $node_storage->resetCache(array($node->id()));
    return $node_storage->load($node->id());
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
    $log_message = db_select('node_revision', 'r')
      ->fields('r', array('revision_log'))
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
    $count = db_select('node_revision', 'r')
      ->condition('nid', $nid)
      ->countQuery()
      ->execute()
      ->fetchColumn();

    return $this->assertEqual($count, $value, $message, $group);
  }

}
