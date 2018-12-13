<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneFilterFormatTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Create a filter format and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneFilterFormatTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'filter'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'clone filter_format entity',
    'administer filters'
  ];

  /**
   * An administrative user with permission to configure filter formats settings.
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

  public function testFilterFormatEntityClone() {
    $edit = [
      'name' => 'Test filter format for clone',
      'format' => 'test_filter_format_for_clone',
    ];
    $this->drupalPostForm("admin/config/content/formats/add", $edit, t('Save configuration'));

    $filter_formats = \Drupal::entityTypeManager()
      ->getStorage('filter_format')
      ->loadByProperties([
        'format' => $edit['format'],
      ]);
    $filter_format = reset($filter_formats);

    $edit = [
      'id' => 'test_filter_format_cloned',
      'label' => 'Test filter format cloned',
    ];
    $this->drupalPostForm('entity_clone/filter_format/' . $filter_format->id(), $edit, t('Clone'));

    $filter_formats = \Drupal::entityTypeManager()
      ->getStorage('filter_format')
      ->loadByProperties([
        'format' => $edit['id'],
      ]);
    $filter_format = reset($filter_formats);
    $this->assertTrue($filter_format, 'Test filter format cloned found in database.');
  }

}

