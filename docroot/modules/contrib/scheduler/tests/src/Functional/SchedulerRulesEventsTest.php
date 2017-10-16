<?php

namespace Drupal\Tests\scheduler\Functional;

use Drupal\rules\Context\ContextConfig;

/**
 * Tests the six events that Scheduler provides for use in Rules module.
 *
 * @group scheduler
 */
class SchedulerRulesEventsTest extends SchedulerBrowserTestBase {

  /**
   * Additional modules required.
   *
   * @var array
   */
  public static $modules = ['scheduler_rules_integration'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->rulesStorage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');
    $this->expressionManager = $this->container->get('plugin.manager.rules_expression');

  }

  /**
   * Tests the six events provided by Scheduler.
   *
   * This class tests all six events provided by Scheduler, by creating six
   * rules which are all active throughout the test. They are all checked in
   * this one test class to make the tests stronger, as this will show not only
   * that the correct events are triggered in the right places, but also
   * that they are not triggered in the wrong places.
   */
  public function testRulesEvents() {

    // Create six reaction rules, one for each event that Scheduler triggers.
    $rule_data = [
      1 => ['scheduler_new_node_is_scheduled_for_publishing_event', 'A new node is created and is scheduled for publishing.'],
      2 => ['scheduler_existing_node_is_scheduled_for_publishing_event', 'An existing node is saved and is scheduled for publishing.'],
      3 => ['scheduler_has_published_this_node_event', 'Scheduler has published this node during cron.'],
      4 => ['scheduler_new_node_is_scheduled_for_unpublishing_event', 'A new node is created and is scheduled for unpublishing.'],
      5 => ['scheduler_existing_node_is_scheduled_for_unpublishing_event', 'An existing node is saved and is scheduled for unpublishing.'],
      6 => ['scheduler_has_unpublished_this_node_event', 'Scheduler has unpublished this node during cron.'],
    ];
    foreach ($rule_data as $i => list($event_name, $description)) {
      $rule[$i] = $this->expressionManager->createRule();
      $message[$i] = 'RULES message ' . $i . '. ' . $description;
      $rule[$i]->addAction('rules_system_message', ContextConfig::create()
        ->setValue('message', $message[$i])
        ->setValue('type', 'status')
        );
      $config_entity = $this->rulesStorage->create([
        'id' => 'rule' . $i,
        'events' => [['event_name' => $event_name]],
        'expression' => $rule[$i]->getConfiguration(),
      ]);
      $config_entity->save();
    }

    $this->drupalLogin($this->schedulerUser);

    // Create a node without any scheduled dates, using node/add/ not
    // drupalCreateNode(), and check that no events are triggered.
    $edit = [
      'title[0][value]' => 'Test for no events',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalPostForm('node/add/' . $this->type, $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNoText($message[1], '"' . $message[1] . '" is not shown');
    $this->assertNoText($message[2], '"' . $message[2] . '" is not shown');
    $this->assertNoText($message[3], '"' . $message[3] . '" is not shown');
    $this->assertNoText($message[4], '"' . $message[4] . '" is not shown');
    $this->assertNoText($message[5], '"' . $message[5] . '" is not shown');
    $this->assertNoText($message[6], '"' . $message[6] . '" is not shown');

    // Edit the node and check that no events are triggered.
    $edit = [
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertNoText($message[1], '"' . $message[1] . '" is not shown');
    $this->assertNoText($message[2], '"' . $message[2] . '" is not shown');
    $this->assertNoText($message[3], '"' . $message[3] . '" is not shown');
    $this->assertNoText($message[4], '"' . $message[4] . '" is not shown');
    $this->assertNoText($message[5], '"' . $message[5] . '" is not shown');
    $this->assertNoText($message[6], '"' . $message[6] . '" is not shown');

    // Create a new node with a publish-on date, and check that only event 1 is
    // triggered.
    $edit = [
      'title[0][value]' => 'Create node with publish-on date',
      'publish_on[0][value][date]' => date('Y-m-d', time() + 3),
      'publish_on[0][value][time]' => date('H:i:s', time() + 3),
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalPostForm('node/add/' . $this->type, $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertText($message[1], '"' . $message[1] . '" IS shown');
    $this->assertNoText($message[2], '"' . $message[2] . '" is not shown');
    $this->assertNoText($message[3], '"' . $message[3] . '" is not shown');
    $this->assertNoText($message[4], '"' . $message[4] . '" is not shown');
    $this->assertNoText($message[5], '"' . $message[5] . '" is not shown');
    $this->assertNoText($message[6], '"' . $message[6] . '" is not shown');

    // Edit this node and check that only event 2 is triggered.
    $edit = [
      'title[0][value]' => 'Edit node with publish-on date',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertNoText($message[1], '"' . $message[1] . '" is not shown');
    $this->assertText($message[2], '"' . $message[2] . '" IS shown');
    $this->assertNoText($message[3], '"' . $message[3] . '" is not shown');
    $this->assertNoText($message[4], '"' . $message[4] . '" is not shown');
    $this->assertNoText($message[5], '"' . $message[5] . '" is not shown');
    $this->assertNoText($message[6], '"' . $message[6] . '" is not shown');

    // Delay to ensure that the date entered is now in the past so that the node
    // will be processed during cron, and assert that event 3 is triggered.
    sleep(5);
    $this->cronRun();
    $this->drupalGet('admin/reports/dblog');
    $this->assertNoText($message[1], '"' . $message[1] . '" is not shown');
    $this->assertNoText($message[2], '"' . $message[2] . '" is not shown');
    $this->assertText($message[3], '"' . $message[3] . '" IS shown');
    $this->assertNoText($message[4], '"' . $message[4] . '" is not shown');
    $this->assertNoText($message[5], '"' . $message[5] . '" is not shown');
    $this->assertNoText($message[6], '"' . $message[6] . '" is not shown');

    // Create a new node with an unpublish-on date, and check that only event 4
    // is triggered.
    $edit = [
      'title[0][value]' => 'Create node with unpublish-on date',
      'unpublish_on[0][value][date]' => date('Y-m-d', time() + 3),
      'unpublish_on[0][value][time]' => date('H:i:s', time() + 3),
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalPostForm('node/add/' . $this->type, $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNoText($message[1], '"' . $message[1] . '" is not shown');
    $this->assertNoText($message[2], '"' . $message[2] . '" is not shown');
    $this->assertNoText($message[3], '"' . $message[3] . '" is not shown');
    $this->assertText($message[4], '"' . $message[4] . '" IS shown');
    $this->assertNoText($message[5], '"' . $message[5] . '" is not shown');
    $this->assertNoText($message[6], '"' . $message[6] . '" is not shown');

    // Edit this node and check that only event 5 is triggered.
    $edit = [
      'title[0][value]' => 'Edit node with unpublish-on date',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertNoText($message[1], '"' . $message[1] . '" is not shown');
    $this->assertNoText($message[2], '"' . $message[2] . '" is not shown');
    $this->assertNoText($message[3], '"' . $message[3] . '" is not shown');
    $this->assertNoText($message[4], '"' . $message[4] . '" is not shown');
    $this->assertText($message[5], '"' . $message[5] . '" IS shown');
    $this->assertNoText($message[6], '"' . $message[6] . '" is not shown');

    // Delay to ensure that the date entered is now in the past so that the node
    // will be processed during cron, and assert that event 6 is triggered.
    sleep(5);
    $this->cronRun();
    $this->drupalGet('admin/reports/dblog');
    $this->assertNoText($message[1], '"' . $message[1] . '" is not shown');
    $this->assertNoText($message[2], '"' . $message[2] . '" is not shown');
    $this->assertNoText($message[3], '"' . $message[3] . '" is not shown');
    $this->assertNoText($message[4], '"' . $message[4] . '" is not shown');
    $this->assertNoText($message[5], '"' . $message[5] . '" is not shown');
    $this->assertText($message[6], '"' . $message[6] . '" IS shown');

    // Create a new node with both publish-on and unpublish-on dates, and check
    // that events 1 and event 4 are both triggered.
    $edit = [
      'title[0][value]' => 'Create node with both dates',
      'publish_on[0][value][date]' => date('Y-m-d', time() + 3),
      'publish_on[0][value][time]' => date('H:i:s', time() + 3),
      'unpublish_on[0][value][date]' => date('Y-m-d', time() + 4),
      'unpublish_on[0][value][time]' => date('H:i:s', time() + 4),
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalPostForm('node/add/' . $this->type, $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertText($message[1], '"' . $message[1] . '" IS shown');
    $this->assertNoText($message[2], '"' . $message[2] . '" is not shown');
    $this->assertNoText($message[3], '"' . $message[3] . '" is not shown');
    $this->assertText($message[4], '"' . $message[4] . '" IS shown');
    $this->assertNoText($message[5], '"' . $message[5] . '" is not shown');
    $this->assertNoText($message[6], '"' . $message[6] . '" is not shown');

    // Edit this node and check that events 2 and 5 are triggered.
    $edit = [
      'title[0][value]' => 'Edit node with both dates',
      'body[0][value]' => $this->randomString(30),
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNoText($message[1], '"' . $message[1] . '" is not shown');
    $this->assertText($message[2], '"' . $message[2] . '" IS shown');
    $this->assertNoText($message[3], '"' . $message[3] . '" is not shown');
    $this->assertNoText($message[4], '"' . $message[4] . '" is not shown');
    $this->assertText($message[5], '"' . $message[5] . '" IS shown');
    $this->assertNoText($message[6], '"' . $message[6] . '" is not shown');

    // Delay to ensure that the dates are now in the past so that the node will
    // be processed during cron, and assert that events 3, 5 & 6 are triggered.
    sleep(6);
    $this->cronRun();
    $this->drupalGet('admin/reports/dblog');
    $this->assertNoText($message[1], '"' . $message[1] . '" is not shown');
    $this->assertNoText($message[2], '"' . $message[2] . '" is not shown');
    $this->assertText($message[3], '"' . $message[3] . '" IS shown');
    $this->assertNoText($message[4], '"' . $message[4] . '" is not shown');
    $this->assertText($message[5], '"' . $message[5] . '" IS shown');
    $this->assertText($message[6], '"' . $message[6] . '" IS shown');

  }

}
