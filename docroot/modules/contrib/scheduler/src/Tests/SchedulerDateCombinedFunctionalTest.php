<?php

/**
 * @file
 * Contains \Drupal\scheduler\Tests\ScedulerDateCombineFunctionalTest
 */

namespace Drupal\scheduler\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the components of the Scheduler interface which use the Date module.
 *
 * @group scheduler
 */
class SchedulerDateCombinedFunctionalTest extends SchedulerTestBase {

  /**
   * The modules to be loaded for these tests.
   */
  public static $modules = array('node', 'scheduler', 'datetime');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a 'Basic Page' content type.
    $this->drupalCreateContentType(['type' => 'page', 'name' => t('Basic page')]);
    ### @TODO the string 'page' is hard-coded eleven times in this file (so far)
    ### @TODO Could make it a variable, which would allow future testing of other entity types?

    // Add scheduler functionality to the page node type.
    /** @var NodeTypeInterface $node_type */
    $node_type = NodeType::load('page');
    $node_type->setThirdPartySetting('scheduler', 'publish_enable', TRUE);
    $node_type->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE);
    $node_type->save();

    // Create an administrator user.
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer scheduler',
      'create page content',
      'edit own page content',
      'delete own page content',
      'view own unpublished content',
      'administer nodes',
      'schedule (un)publishing of nodes',
    ]);
  }

  /**
   * Test the default time functionality.
   */
  public function testDefaultTime() {
    $this->drupalLogin($this->adminUser);

    // Check that the correct default time is added to the scheduled date.
    // For testing we use an offset of 6 hours 30 minutes (23400 seconds).
    $edit = array(
      'date_format' => 'Y-m-d H:i:s',
      'allow_date_only' => TRUE,
      'default_time' => '6:30',
    );
    $this->drupalPostForm('admin/config/content/scheduler', $edit, t('Save configuration'));
    $this->assertDefaultTime();

    // Check that it is not possible to enter a date format without a time if
    // the 'date only' option is not enabled.
    $edit = array(
      'date_format' => 'Y-m-d',
      'allow_date_only' => FALSE,
    );
    $this->drupalPostForm('admin/config/content/scheduler', $edit, t('Save configuration'));
    $this->assertRaw(t('You must either include a time within the date format or enable the date-only option.'), 'It is not possible to enter a date format without a time if the "date only" option is not enabled.');
  }

  /**
   * Asserts that the default time works as expected.
   */
  protected function assertDefaultTime() {
    // We cannot easily test the exact validation messages as they contain the
    // REQUEST_TIME of the POST request, which can be one or more seconds in the
    // past. Best we can do is check the fixed part of the message as it is when
    // passed to t(). This will only work in English.
    $publish_validation_message = "The 'publish on' value does not match the expected format of";
    $unpublish_validation_message = "The 'unpublish on' value does not match the expected format of";

    // First test with the "date only" functionality disabled.
    $this->drupalPostForm('admin/config/content/scheduler', array('allow_date_only' => FALSE), t('Save configuration'));

    // Test if entering a time is required.
    $edit = array(
      'title[0][value]' => t('No time') . ' ' . $this->randomString(15),
      'publish_on[0][value][date]' => date('Y-m-d', strtotime('+1 day', REQUEST_TIME)),
      'unpublish_on[0][value][date]' => date('Y-m-d', strtotime('+2 day', REQUEST_TIME)),
    );
    $this->drupalPostForm('node/add/page', $edit, t('Save and publish'));

    $this->assertRaw($publish_validation_message, 'By default it is required to enter a time when scheduling content for publication.');
    $this->assertRaw($unpublish_validation_message, 'By default it is required to enter a time when scheduling content for unpublication.');

    // Allow the user to enter only the date and repeat the test.
    $this->drupalPostForm('admin/config/content/scheduler', array('allow_date_only' => TRUE), t('Save configuration'));

    $this->drupalPostForm('node/add/page', $edit, t('Save and publish'));
    $this->assertNoRaw("The 'publish on' value does not match the expected format of", 'If the default time option is enabled the user can skip the time when scheduling content for publication.');
    $this->assertNoRaw("The 'unpublish on' value does not match the expected format of", 'If the default time option is enabled the user can skip the time when scheduling content for unpublication.');
    $publish_time = date('Y-m-d H:i:s', strtotime('tomorrow', REQUEST_TIME) + 23400);
    debug($publish_time, '$publish_time');
    $args = array('@publish_time' => $publish_time);
    $this->assertRaw(t('This post is unpublished and will be published @publish_time.', $args), 'The user is informed that the content will be published on the requested date, on the default time.');

    // Check that the default time has been added to the scheduler form fields.
    $this->clickLink(t('Edit'));
    $this->assertFieldByName('publish_on', date('Y-m-d H:i:s', strtotime('tomorrow', REQUEST_TIME) + 23400), 'The default time offset has been added to the date field when scheduling content for publication.');
    $this->assertFieldByName('unpublish_on', date('Y-m-d H:i:s', strtotime('tomorrow +1 day', REQUEST_TIME) + 23400), 'The default time offset has been added to the date field when scheduling content for unpublication.');
  }

}
