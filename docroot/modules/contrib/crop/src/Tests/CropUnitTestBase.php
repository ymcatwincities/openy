<?php

/**
 * @file
 * Contains \Drupal\crop\Tests\CropUnitTestBase.
 */

namespace Drupal\crop\Tests;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the crop entity CRUD operations.
 */
abstract class CropUnitTestBase extends KernelTestBase {

  /**
   * The crop storage.
   *
   * @var \Drupal\crop\CropStorageInterface.
   */
  protected $cropStorage;

  /**
   * The crop storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface.
   */
  protected $cropTypeStorage;

  /**
   * The image style storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface.
   */
  protected $imageStyleStorage;

  /**
   * Test image style.
   *
   * @var \Drupal\image\ImageStyleInterface
   */
  protected $testStyle;

  /**
   * Test crop type.
   *
   * @var \Drupal\crop\CropInterface
   */
  protected $cropType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\Core\Entity\EntityManagerInterface $entity_manager */
    $entity_manager = $this->container->get('entity.manager');
    $this->cropStorage = $entity_manager->getStorage('crop');
    $this->cropTypeStorage = $entity_manager->getStorage('crop_type');
    $this->imageStyleStorage = $entity_manager->getStorage('image_style');

    // Create DB schemas.
    $entity_manager->onEntityTypeCreate($entity_manager->getDefinition('user'));
    $entity_manager->onEntityTypeCreate($entity_manager->getDefinition('image_style'));
    $entity_manager->onEntityTypeCreate($entity_manager->getDefinition('crop'));
    $entity_manager->onEntityTypeCreate($entity_manager->getDefinition('file'));

    // Create test image style.
    $uuid = $this->container->get('uuid')->generate();
    $this->testStyle = $this->imageStyleStorage->create([
      'name' => 'test',
      'label' => 'Test image style',
      'effects' => [
        $uuid => [
          'id' => 'crop_crop',
          'data' => ['crop_type' => 'test_type'],
          'weight' => 0,
          'uuid' => $uuid,
        ]
      ],
    ]);
    $this->testStyle->save();

    // Create test crop type.
    $this->cropType = $this->cropTypeStorage->create([
      'id' => 'test_type',
      'label' => 'Test crop type',
      'description' => 'Some nice desc.',
    ]);
    $this->cropType->save();
  }

  /**
   * Creates and gets test image file.
   *
   * @return \Drupal\file\FileInterface
   *   File object.
   */
  protected function getTestFile() {
    file_unmanaged_copy(drupal_get_path('module', 'crop') . '/tests/files/sarajevo.png', PublicStream::basePath());
    return $this->container->get('entity.manager')->getStorage('file')->create([
      'uri' => 'public://sarajevo.png',
      'status' => FILE_STATUS_PERMANENT,
    ]);
  }

}
