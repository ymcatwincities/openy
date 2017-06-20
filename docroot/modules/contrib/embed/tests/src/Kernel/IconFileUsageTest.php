<?php

/**
 * @file
 * Contains \Drupal\embed\Tests\IconFileUsageTest.
 */

namespace Drupal\Tests\embed\Kernel;

use Drupal\embed\Entity\EmbedButton;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests embed button icon file usage.
 *
 * @group embed
 */
class IconFileUsageTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['embed', 'embed_test'];

  /**
   * Tests the embed_button and file usage integration.
   */
  public function testEmbedButtonIconUsage() {
    $this->enableModules(['system', 'user', 'file']);

    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['system']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('embed_button');

    $file1 = file_save_data(file_get_contents('core/misc/druplicon.png'));
    $file1->setTemporary();
    $file1->save();

    $file2 = file_save_data(file_get_contents('core/misc/druplicon.png'));
    $file2->setTemporary();
    $file2->save();

    $button = array(
      'id' => 'test_button',
      'label' => 'Testing embed button instance',
      'type_id' => 'embed_test_default',
      'icon_uuid' => $file1->uuid(),
    );

    $entity = EmbedButton::create($button);
    $entity->save();
    $this->assertTrue(File::load($file1->id())->isPermanent());

    // Delete the icon from the button.
    $entity->icon_uuid = NULL;
    $entity->save();
    $this->assertTrue(File::load($file1->id())->isTemporary());

    $entity->icon_uuid = $file1->uuid();
    $entity->save();
    $this->assertTrue(File::load($file1->id())->isPermanent());

    $entity->icon_uuid = $file2->uuid();
    $entity->save();
    $this->assertTrue(File::load($file1->id())->isTemporary());
    $this->assertTrue(File::load($file2->id())->isPermanent());

    $entity->delete();
    $this->assertTrue(File::load($file2->id())->isTemporary());
  }

}
