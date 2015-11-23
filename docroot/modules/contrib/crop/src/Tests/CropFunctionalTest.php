<?php

/**
 * @file
 * Definition of Drupal\crop\Tests\CropFunctionalTest.
 */

namespace Drupal\crop\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for crop API.
 *
 * @group crop
 */
class CropFunctionalTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['crop'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

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

    $this->adminUser = $this->drupalCreateUser(['administer crop types', 'administer image styles']);

    // Create test image style.
    $this->testStyle = $this->container->get('entity.manager')->getStorage('image_style')->create([
      'name' => 'test',
      'label' => 'Test image style',
      'effects' => [],
    ]);
    $this->testStyle->save();
  }

  /**
   * Tests crop type crud pages.
   */
  public function testCropTypeCrud() {
    // Anonymous users don't have access to crop type admin pages.
    $this->drupalGet('admin/structure/crop');
    $this->assertResponse(403);
    $this->drupalGet('admin/structure/crop/add');
    $this->assertResponse(403);

    // Can access pages if logged in and no crop types exist.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/crop');
    $this->assertResponse(200);
    $this->assertText(t('No crop types available.'));
    $this->assertLink(t('Add crop type'));

    // Can access add crop type form.
    $this->clickLink(t('Add crop type'));
    $this->assertResponse(200);
    $this->assertUrl('admin/structure/crop/add');

    // Create crop type.
    $edit = [
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomMachineName(),
      'description' => $this->randomGenerator->sentences(10),
    ];
    $this->drupalPostForm('admin/structure/crop/add', $edit, t('Save crop type'));
    $this->assertRaw(t('The crop type %name has been added.', ['%name' => $edit['label']]));
    $this->assertUrl('admin/structure/crop');
    $label = $this->xpath("//td[contains(concat(' ',normalize-space(@class),' '),' menu-label ')]");
    $this->assert(strpos($label[0]->asXML(), $edit['label']) !== FALSE, 'Crop type label found on listing page.');
    $this->assertText($edit['description']);

    // Check edit form.
    $this->clickLink(t('Edit'));
    $this->assertText(t('Edit @name crop type', ['@name' => $edit['label']]));
    $this->assertRaw($edit['id']);
    $this->assertFieldById('edit-label', $edit['label']);
    $this->assertRaw($edit['description']);

    // See if crop type appears on image effect configuration form.
    $this->drupalGet('admin/config/media/image-styles/manage/' . $this->testStyle->id() . '/add/crop_crop');
    $option = $this->xpath("//select[@id='edit-data-crop-type']/option");
    $this->assert(strpos($option[0]->asXML(), $edit['label']) !== FALSE, 'Crop type label found on image effect page.');
    $this->drupalPostForm('admin/config/media/image-styles/manage/' . $this->testStyle->id() . '/add/crop_crop', ['data[crop_type]' => $edit['id']], t('Add effect'));
    $this->assertText(t('The image effect was successfully applied.'));
    $this->assertText(t('Manual crop uses @name crop type', ['@name' => $edit['label']]));
    $this->testStyle = $this->container->get('entity.manager')->getStorage('image_style')->loadUnchanged($this->testStyle->id());
    $this->assertEqual($this->testStyle->getEffects()->count(), 1, 'One image effect added to test image style.');
    $effect_configuration = $this->testStyle->getEffects()->getIterator()->current()->getConfiguration();
    $this->assertEqual($effect_configuration['data'], ['crop_type' => $edit['id']], 'Manual crop effect uses correct image style.');

    // Try to access edit form as anonymous user.
    $this->drupalLogout();
    $this->drupalGet('admin/structure/crop/manage/' . $edit['id']);
    $this->assertResponse(403);
    $this->drupalLogin($this->adminUser);

    // Try to create crop type with same machine name.
    $this->drupalPostForm('admin/structure/crop/add', $edit, t('Save crop type'));
    $this->assertText(t('The machine-readable name is already in use. It must be unique.'));

    // Delete crop type.
    $this->drupalGet('admin/structure/crop');
    $this->clickLink(t('Delete'));
    $this->assertText(t('Are you sure you want to delete the crop type @name?', ['@name' => $edit['label']]));
    $this->drupalPostForm('admin/structure/crop/manage/' . $edit['id'] . '/delete', [], t('Delete'));
    $this->assertRaw(t('The crop type %name has been deleted.', ['%name' => $edit['label']]));
    $this->assertText(t('No crop types available.'));
  }

}
