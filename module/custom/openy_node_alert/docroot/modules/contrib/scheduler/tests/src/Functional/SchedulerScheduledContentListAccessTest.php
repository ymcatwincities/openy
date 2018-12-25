<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests access to the scheduled content overview page and user tab.
 *
 * @group scheduler
 */
class SchedulerScheduledContentListAccessTest extends SchedulerBrowserTestBase {

  /**
   * Additional modules required.
   *
   * @var array
   */
  public static $modules = ['views'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $base_permissions = [
      'access content',
      'create ' . $this->type . ' content',
      'view own unpublished content',
    ];

    $this->editorUser = $this->drupalCreateUser(array_merge($base_permissions, ['access content overview']));
    $this->schedulerUser = $this->drupalCreateUser(array_merge($base_permissions, ['schedule publishing of nodes']));
    $this->schedulerManager = $this->drupalCreateUser(array_merge($base_permissions, ['view scheduled content']));

    // Create nodes scheduled for publishing and for unpublishing.
    $this->node1 = $this->drupalCreateNode([
      'title' => 'Node created by Scheduler User for publishing',
      'uid' => $this->schedulerUser->id(),
      'status' => FALSE,
      'type' => $this->type,
      'publish_on' => strtotime('+1 week'),
    ]);
    $this->node2 = $this->drupalCreateNode([
      'title' => 'Node created by Scheduler User for unpublishing',
      'uid' => $this->schedulerUser->id(),
      'status' => TRUE,
      'type' => $this->type,
      'unpublish_on' => strtotime('+1 week'),
    ]);
    $this->node3 = $this->drupalCreateNode([
      'title' => 'Node created by Scheduler Manager for publishing',
      'uid' => $this->schedulerManager->id(),
      'status' => FALSE,
      'type' => $this->type,
      'publish_on' => strtotime('+1 week'),
    ]);
    $this->node4 = $this->drupalCreateNode([
      'title' => 'Node created by Scheduler Manager for unpublishing',
      'uid' => $this->schedulerManager->id(),
      'status' => TRUE,
      'type' => $this->type,
      'unpublish_on' => strtotime('+1 week'),
    ]);
  }

  /**
   * Tests the scheduled content tab on the user page.
   */
  public function testViewScheduledContentUser() {
    // Access a scheduled content user tab as an anonymous visitor.
    $this->drupalGet("user/{$this->schedulerUser->id()}/scheduled");
    $this->assertResponse(403, 'An anonymous visitor cannot access a user\'s scheduled content tab.');

    // Access a users own scheduled content tab as "Editor" without any
    // scheduler permissions.
    $this->drupalLogin($this->editorUser);
    $this->drupalGet("user/{$this->editorUser->id()}/scheduled");
    $this->assertResponse(403, '"Editor" cannot access a scheduled content user tab.');

    // Access a users own scheduled content tab as "Scheduler User" with only
    // 'schedule publishing of nodes' permission.
    $this->drupalLogin($this->schedulerUser);
    $this->drupalGet("user/{$this->schedulerUser->id()}/scheduled");
    $this->assertResponse(200, '"Scheduler User" can access their scheduled content user tab.');
    $this->assertText('Node created by Scheduler User for publishing');
    $this->assertText('Node created by Scheduler User for unpublishing');
    $this->assertNoText('Node created by Scheduler Manager for unpublishing');

    // Access another users scheduled content tab as "Scheduler User".
    $this->drupalGet("user/{$this->schedulerManager->id()}/scheduled");
    $this->assertResponse(403, '"Scheduler User" cannot access the scheduled content user tab for "Scheduler Manager"');

    // Access the users own scheduled content tab as "Scheduler Manager" with
    // only 'view scheduled content' permission.
    $this->drupalLogin($this->schedulerManager);
    $this->drupalGet("user/{$this->schedulerManager->id()}/scheduled");
    $this->assertResponse(200, 'Scheduler Manager can access their own scheduled content user tab.');
    $this->assertText('Node created by Scheduler Manager for publishing');
    $this->assertText('Node created by Scheduler Manager for unpublishing');
    $this->assertNoText('Node created by Scheduler User for unpublishing');

    // Access another users scheduled content tab as "Scheduler Manager".
    // The published and unpublished content should be listed.
    $this->drupalGet("user/{$this->schedulerUser->id()}/scheduled");
    $this->assertResponse(200, '"Scheduler Manager" can access the scheduled content user tab for "Scheduler User"');
    $this->assertText('Node created by Scheduler User for publishing');
    $this->assertText('Node created by Scheduler User for unpublishing');
  }

  /**
   * Tests the scheduled content overview.
   */
  public function testViewScheduledContentOverview() {
    // Access the scheduled content overview as anonymous visitor.
    $this->drupalGet('admin/content/scheduled');
    $this->assertResponse(403, 'An anonymous visitor cannot access the scheduled content overview.');

    // Access the scheduled content overview as "Editor" without any
    // scheduler permissions.
    $this->drupalLogin($this->editorUser);
    $this->drupalGet('admin/content/scheduled');
    $this->assertResponse(403, '"Editor" cannot access the scheduled content overview.');

    // Access the scheduled content overview as "Scheduler User" with only
    // 'schedule publishing of nodes' permission.
    $this->drupalLogin($this->schedulerUser);
    $this->drupalGet('admin/content/scheduled');
    $this->assertResponse(403, '"Scheduler User" cannot access the scheduled content overview.');

    // Access the scheduled content overview as "Scheduler Manager" with only
    // 'view scheduled content' permission. They should be able to see the
    // scheduled published and unpublished content by all users.
    $this->drupalLogin($this->schedulerManager);
    $this->drupalGet('admin/content/scheduled');
    $this->assertResponse(200, 'Scheduler Manager can access the scheduled content overview.');
    $this->assertText('Node created by Scheduler User for publishing');
    $this->assertText('Node created by Scheduler User for unpublishing');
    $this->assertText('Node created by Scheduler Manager for publishing');
    $this->assertText('Node created by Scheduler Manager for unpublishing');
  }

}
