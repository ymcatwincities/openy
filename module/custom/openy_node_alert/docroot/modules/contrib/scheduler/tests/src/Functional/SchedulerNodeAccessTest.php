<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests that Scheduler cron has full access to the scheduled nodes.
 *
 * This test uses an additional test module 'scheduler_access_test' which uses
 * a custom node access definition to deny viewing of all nodes.
 *
 * @group scheduler
 */
class SchedulerNodeAccessTest extends SchedulerBrowserTestBase {

  /**
   * Additional modules required.
   *
   * @var array
   */
  public static $modules = ['scheduler_access_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // scheduler_access_test_install() sets node_access_needs_rebuild(TRUE) and
    // this works when testing the module interactively, but during simpletest
    // the node access table is not rebuilt. Hence do that here explicitly here.
    node_access_rebuild();
  }

  /**
   * Tests Scheduler cron functionality when access to the nodes is denied.
   */
  public function testNodeAccess() {

    // Create data to test publishing then unpublishing via loop.
    // @TODO Convert this test to use a @dataProvider function instead of this
    // array and the loop.
    $test_data = [
      'publish_on' => [
        'status' => FALSE,
        'before' => 'unpublished',
        'after' => 'published',
      ],
      'unpublish_on' => [
        'status' => TRUE,
        'before' => 'published',
        'after' => 'unpublished',
      ],
    ];

    foreach ($test_data as $field => $data) {
      // Create a node with the required scheduler date.
      $settings = [
        'type' => $this->type,
        'status' => $data['status'],
        'title' => 'Test node to be ' . $data['after'],
        $field => REQUEST_TIME + 1,
      ];
      $node = $this->drupalCreateNode($settings);
      $this->drupalGet('node/' . $node->id());
      $this->assertResponse(403, 'Before cron, viewing the ' . $data['before'] . '  node returns "403 Not Authorized"');

      // Delay so that the date entered is now in the past, then run cron.
      sleep(2);
      $this->cronRun();

      // Reload the node.
      $this->nodeStorage->resetCache([$node->id()]);
      $node = $this->nodeStorage->load($node->id());
      // Check that the node has been published or unpublished as required.
      $this->assertTrue($node->isPublished() === !$data['status'], 'Scheduler has ' . $data['after'] . ' the node via cron.');

      // Check the node is still not viewable.
      $this->drupalGet('node/' . $node->id());
      $this->assertResponse(403, 'After cron, viewing the ' . $data['after'] . '  node returns "403 Not Authorized"');
    }

    // Log in and show the dblog for info only.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/reports/dblog');
    $this->assertText('scheduled publishing', '"Scheduled publishing" message is shown in the dblog');
    $this->assertText('scheduled unpublishing', '"Scheduled unpublishing" message is shown in the dblog');
  }

}
