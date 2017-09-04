<?php

namespace Drupal\lazyloader\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\simpletest\WebTestBase;

/**
 * Test case for typical lazyloader tests.
 */
abstract class LazyloaderTestBase extends WebTestBase {

  protected $user;
  protected $node;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['image', 'lazyloader', 'path', 'node', 'field'];

  public function setUp() {
    parent::setUp();

    $this->createContentType([
      'type' => 'page',
    ]);

    $user = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
      'administer lazyloader',
      'administer url aliases',
      'create url aliases'
    ]);
    $this->drupalLogin($user);

    // Add unlimited image field.
    $field_storage = FieldStorageConfig::create([
      'type' => 'image',
      'field_name' => 'field_images',
      'cardinality' => -1,
      'entity_type' => 'node',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'field_images',
      'entity_type' => 'node',
      'bundle' => 'page',
    ]);
    $field->save();

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = entity_get_display('node', 'page', 'full');
    $display->setComponent('field_images', [
      'type' => 'image',
      'settings' => [
        'image_style' => 'medium',
      ],
    ]);
    $display->save();

    $images = $this->drupalGetTestFiles('image');
    foreach ($images as $key => $image) {
      $file = File::create((array) $image);
      $file->save();
      $images[$key] = $file->id();
    }

    $settings = [
      'type' => 'page',
      'field_images' => $images,
      'path' => [
        'alias' => '/' . $this->randomMachineName(),
      ],
    ];

    $this->node = $this->drupalCreateNode($settings);
  }

  /**
   * Asserts if lazyloader is enabled on the page.
   * @param bool|TRUE $enabled
   * @param string $message
   */
  protected function assertLazyloaderEnabled($enabled = TRUE, $message = '') {
    if ($message === '') {
      $message = $enabled ? 'Lazyloader is enabled' : 'Lazyloader is disabled';
    }
    $image_count = count($this->node->field_images[$this->node->language]);
    $images = $this->xpath('//img[@data-echo]');

    if ($enabled) {
      $this->assertEqual(count($images), $image_count, $message);
    }
    else {
      $this->assertFalse(count($images), $message);
    }
  }
}
