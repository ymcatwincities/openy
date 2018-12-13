<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneResponsiveImageStyleTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Create a responsive image style and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneResponsiveImageStyleTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'responsive_image'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'clone responsive_image_style entity',
    'administer responsive images'
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

  public function testResponsiveImageStyleEntityClone() {
    $edit = [
      'label' => 'Test responsive image style for clone',
      'id' => 'test_responsive_image_style_for_clone',
      'breakpoint_group' => 'responsive_image',
      'fallback_image_style' => 'large',
    ];
    $this->drupalPostForm("admin/config/media/responsive-image-style/add", $edit, t('Save'));

    $responsive_image_styles = \Drupal::entityTypeManager()
      ->getStorage('responsive_image_style')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $responsive_image_style = reset($responsive_image_styles);

    $edit = [
      'id' => 'test_responsive_image_style_cloned',
      'label' => 'Test responsive image style cloned',
    ];
    $this->drupalPostForm('entity_clone/responsive_image_style/' . $responsive_image_style->id(), $edit, t('Clone'));

    $responsive_image_styles = \Drupal::entityTypeManager()
      ->getStorage('responsive_image_style')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $responsive_image_style = reset($responsive_image_styles);
    $this->assertTrue($responsive_image_style, 'Test responsive image style cloned found in database.');
  }

}

