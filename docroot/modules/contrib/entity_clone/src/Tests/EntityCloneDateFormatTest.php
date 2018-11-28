<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneDateFormatTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Create a date format and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneDateFormatTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'clone date_format entity',
    'administer site configuration'
  ];

  /**
   * An administrative user with permission to configure date formats settings.
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

  public function testDateFormatEntityClone() {
    $edit = [
      'label' => 'Test date format for clone',
      'id' => 'test_date_format_for_clone',
      'date_format_pattern' => 'Y m d',
    ];
    $this->drupalPostForm("admin/config/regional/date-time/formats/add", $edit, t('Add format'));

    $date_formats = \Drupal::entityTypeManager()
      ->getStorage('date_format')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $date_format = reset($date_formats);

    $edit = [
      'id' => 'test_date_format_cloned',
      'label' => 'Test date format cloned',
    ];
    $this->drupalPostForm('entity_clone/date_format/' . $date_format->id(), $edit, t('Clone'));

    $date_formats = \Drupal::entityTypeManager()
      ->getStorage('date_format')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $date_format = reset($date_formats);
    $this->assertTrue($date_format, 'Test date format cloned found in database.');
  }

}

