<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneImageStyleTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Create an image style and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneImageStyleTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'image'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'clone image_style entity',
    'administer image styles'
  ];

  /**
   * An administrative user with permission to configure image styles settings.
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

  public function testImageStyleEntityClone() {
    $edit = [
      'label' => 'Test image style for clone',
      'name' => 'test_image_style_for_clone',
    ];
    $this->drupalPostForm("admin/config/media/image-styles/add", $edit, t('Create new style'));

    $image_styles = \Drupal::entityTypeManager()
      ->getStorage('image_style')
      ->loadByProperties([
        'name' => $edit['name'],
      ]);
    $image_style = reset($image_styles);

    $edit = [
      'id' => 'test_iamge_style_cloned',
      'label' => 'Test image_style cloned',
    ];
    $this->drupalPostForm('entity_clone/image_style/' . $image_style->id(), $edit, t('Clone'));

    $image_styles = \Drupal::entityTypeManager()
      ->getStorage('image_style')
      ->loadByProperties([
        'name' => $edit['id'],
      ]);
    $image_style = reset($image_styles);
    $this->assertTrue($image_style, 'Test image style cloned found in database.');
  }

}

