<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneActionTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\Component\Utility\Crypt;
use Drupal\simpletest\WebTestBase;

/**
 * Create an action and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneActionTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'action'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer actions',
    'clone action entity'
  ];

  /**
   * An administrative user with permission to configure actions settings.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);
  }

  public function testActionEntityClone() {
    foreach (\Drupal::service('plugin.manager.action')->getDefinitions() as $id => $definition) {
      if (is_subclass_of($definition['class'], '\Drupal\Core\Plugin\PluginFormInterface') && $definition['label'] == 'Send email') {
        $action_key = Crypt::hashBase64($id);
        break;
      }
    }

    $edit = [
      'label' => 'Test send email action for clone',
      'id' => 'test_send_email_for_clone',
      'recipient' => 'test@recipient.com',
      'subject' => 'test subject',
      'message' => 'test message',
    ];
    $this->drupalPostForm("admin/config/system/actions/add/$action_key", $edit, t('Save'));

    $actions = \Drupal::entityTypeManager()
      ->getStorage('action')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $action = reset($actions);

    $edit = [
      'label' => 'Test send email action cloned',
      'id' => 'test_send_email_cloned',
    ];
    $this->drupalPostForm('entity_clone/action/' . $action->id(), $edit, t('Clone'));

    $actions = \Drupal::entityTypeManager()
      ->getStorage('action')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $action = reset($actions);
    $this->assertTrue($action, 'Test action cloned found in database.');
  }

}

