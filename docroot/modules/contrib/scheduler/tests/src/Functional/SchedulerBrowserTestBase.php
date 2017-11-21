<?php

namespace Drupal\Tests\scheduler\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class to provide common test setup.
 *
 * Extends the preferred BrowserTestBase instead of the old WebTestBase.
 */
abstract class SchedulerBrowserTestBase extends BrowserTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * The standard modules to be loaded for all tests.
   *
   * @var array
   */
  public static $modules = ['scheduler', 'dblog'];

  /**
   * A user with administration rights.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A user with permission to schedule content.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $schedulerUser;

  /**
   * The internal name of the standard content type created for testing.
   *
   * @var string
   */
  protected $type;

  /**
   * The readable name of the standard content type created for testing.
   *
   * @var string
   */
  protected $typeName;

  /**
   * The node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodetype;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * The Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a 'Basic Page' content type, with 'page' as the identifier.
    // The test files should use $this->type and $this->typeName and not use
    // $this->nodetype->get('type') or $this->nodetype->get('name'), nor have
    // the hard-coded strings 'page' and 'Basic page'.
    $this->type = 'page';
    $this->typeName = 'Basic page';
    /** @var NodeTypeInterface $nodetype */
    $this->nodetype = $this->drupalCreateContentType([
      'type' => $this->type,
      'name' => $this->typeName,
    ]);

    // Add scheduler functionality to the node type.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();

    // Define nodeStorage for use in many tests.
    /** @var EntityStorageInterface $nodeStorage */
    $this->nodeStorage = $this->container->get('entity.manager')->getStorage('node');

    // Create an administrator user having the main admin permissions, full
    // rights on the 'page' content type and all of the Scheduler permissions.
    // 'access site reports' is required for admin/reports/dblog.
    // 'administer site configuration' is required for admin/reports/status.
    $this->adminUser = $this->drupalCreateUser([
      'administer nodes',
      'access content',
      'access content overview',
      'access site reports',
      'administer site configuration',
      'create ' . $this->type . ' content',
      'edit own ' . $this->type . ' content',
      'delete own ' . $this->type . ' content',
      'view own unpublished content',
      'administer scheduler',
      'schedule publishing of nodes',
      'view scheduled content',
    ]);

    // Create an ordinary Scheduler user, with permission to create and schedule
    // content but not with administrator permissions.
    $this->schedulerUser = $this->drupalCreateUser([
      'create ' . $this->type . ' content',
      'edit own ' . $this->type . ' content',
      'delete own ' . $this->type . ' content',
      'view own unpublished content',
      'schedule publishing of nodes',
      'view scheduled content',
    ]);

    // Store the database connection for re-use in the actual tests.
    $this->database = $this->container->get('database');

  }

  /**
   * Run standard Drupal cron.
   *
   * This replicates the standard cronRun function to allow the Scheduler tests
   * that use this to be converted from WebTestBase to BrowserTestBase.
   *
   * @TODO Delete this function after it has been added to BrowserTestBase.
   *
   * Update Jan 2017: cronRun() has been committed to core 8.4 and 8.3 but will
   * not be added to 8.2. Therefore we still need it here whilst we are using
   * 8.2 for testing.
   * @see https://www.drupal.org/node/2795037
   */
  public function cronRun() {
    $this->drupalGet('cron/' . \Drupal::state()->get('system.cron_key'));
  }

}
