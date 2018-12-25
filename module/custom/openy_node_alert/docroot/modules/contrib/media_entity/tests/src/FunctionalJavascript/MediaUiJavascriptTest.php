<?php

namespace Drupal\Tests\media_entity\FunctionalJavascript;

use Drupal\media_entity\Entity\Media;

/**
 * Ensures that media UI works correctly.
 *
 * @group media_entity
 */
class MediaUiJavascriptTest extends MediaEntityJavascriptTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'media_entity_test_type',
  ];

  /**
   * The test media bundle.
   *
   * @var \Drupal\media_entity\MediaBundleInterface
   */
  protected $testBundle;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests a media bundle administration.
   */
  public function testMediaBundles() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Test the creation of a media bundle using the UI.
    $name = $this->randomMachineName();
    $description = $this->randomMachineName();
    $this->drupalGet('admin/structure/media/add');
    $page->fillField('label', $name);
    $session->wait(2000);
    $page->selectFieldOption('type', 'generic');
    $page->fillField('description', $description);
    $page->pressButton('Save media bundle');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('The media bundle ' . $name . ' has been added.');
    $this->drupalGet('admin/structure/media');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains($name);
    $assert_session->pageTextContains($description);

    /** @var \Drupal\media_entity\MediaBundleInterface $bundle_storage */
    $bundle_storage = $this->container->get('entity_type.manager')->getStorage('media_bundle');
    $this->testBundle = $bundle_storage->load(strtolower($name));

    // Check if all action links exist.
    $assert_session->linkByHrefExists('admin/structure/media/add');
    $assert_session->linkByHrefExists('admin/structure/media/manage/' . $this->testBundle->id());
    $assert_session->linkByHrefExists('admin/structure/media/manage/' . $this->testBundle->id() . '/fields');
    $assert_session->linkByHrefExists('admin/structure/media/manage/' . $this->testBundle->id() . '/form-display');
    $assert_session->linkByHrefExists('admin/structure/media/manage/' . $this->testBundle->id() . '/display');

    // Assert that fields have expected values before editing.
    $page->clickLink('Edit');
    $assert_session->fieldValueEquals('label', $name);
    $assert_session->fieldValueEquals('description', $description);
    $assert_session->fieldValueEquals('type', 'generic');
    $assert_session->fieldValueEquals('label', $name);
    $assert_session->checkboxNotChecked('edit-options-new-revision');
    $assert_session->checkboxChecked('edit-options-status');
    $assert_session->checkboxNotChecked('edit-options-queue-thumbnail-downloads');
    $assert_session->pageTextContains('Create new revision');
    $assert_session->pageTextContains('Automatically create a new revision of media entities. Users with the Administer media permission will be able to override this option.');
    $assert_session->pageTextContains('Download thumbnails via a queue.');
    $assert_session->pageTextContains('Entities will be automatically published when they are created.');
    $assert_session->pageTextContains("This type provider doesn't need configuration.");
    $assert_session->pageTextContains('No metadata fields available.');
    $assert_session->pageTextContains('Media type plugins can provide metadata fields such as title, caption, size information, credits, ... Media entity can automatically save this metadata information to entity fields, which can be configured below. Information will only be mapped if the entity field is empty.');

    // Try to change media type and check if new configuration sub-form appears.
    $page->selectFieldOption('type', 'test_type');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldExists('Test config value');
    $assert_session->fieldValueEquals('Test config value', 'This is default value.');
    $assert_session->fieldExists('Field 1');
    $assert_session->fieldExists('Field 2');

    // Test if the edit machine name is not editable.
    $assert_session->fieldDisabled('Machine-readable name');

    // Edit and save media bundle form fields with new values.
    $new_name = $this->randomMachineName();
    $new_description = $this->randomMachineName();
    $page->fillField('label', $new_name);
    $page->fillField('description', $new_description);
    $page->selectFieldOption('type', 'test_type');
    $page->fillField('Test config value', 'This is new config value.');
    $page->selectFieldOption('field_mapping[field_1]', 'name');
    $page->checkField('options[new_revision]');
    $page->uncheckField('options[status]');
    $page->checkField('options[queue_thumbnail_downloads]');
    $page->pressButton('Save media bundle');
    $assert_session->statusCodeEquals(200);

    // Test if edit worked and if new field values have been saved as expected.
    $this->drupalGet('admin/structure/media/manage/' . $this->testBundle->id());
    $assert_session->fieldValueEquals('label', $new_name);
    $assert_session->fieldValueEquals('description', $new_description);
    $assert_session->fieldValueEquals('type', 'test_type');
    $assert_session->checkboxChecked('options[new_revision]');
    $assert_session->checkboxNotChecked('options[status]');
    $assert_session->checkboxChecked('options[queue_thumbnail_downloads]');
    $assert_session->fieldValueEquals('Test config value', 'This is new config value.');
    $assert_session->fieldValueEquals('Field 1', 'name');
    $assert_session->fieldValueEquals('Field 2', '_none');

    /** @var \Drupal\media_entity\MediaBundleInterface $loaded_bundle */
    $loaded_bundle = $this->container->get('entity_type.manager')
      ->getStorage('media_bundle')
      ->load($this->testBundle->id());
    $this->assertEquals($loaded_bundle->id(), $this->testBundle->id());
    $this->assertEquals($loaded_bundle->label(), $new_name);
    $this->assertEquals($loaded_bundle->getDescription(), $new_description);
    $this->assertEquals($loaded_bundle->getType()->getPluginId(), 'test_type');
    $this->assertEquals($loaded_bundle->getType()->getConfiguration()['test_config_value'], 'This is new config value.');
    $this->assertTrue($loaded_bundle->shouldCreateNewRevision());
    $this->assertTrue($loaded_bundle->getQueueThumbnailDownloads());
    $this->assertFalse($loaded_bundle->getStatus());
    $this->assertEquals($loaded_bundle->field_map, ['field_1' => 'name']);

    // Test that a media being created with default status to "FALSE" will be
    // created unpublished.
    /** @var \Drupal\media_entity\MediaInterface $unpublished_media */
    $unpublished_media = Media::create(['name' => 'unpublished test media', 'bundle' => $loaded_bundle->id()]);
    $this->assertFalse($unpublished_media->isPublished());
    $unpublished_media->delete();

    // Tests media bundle delete form.
    $page->clickLink('Delete');
    $assert_session->addressEquals('admin/structure/media/manage/' . $this->testBundle->id() . '/delete');
    $page->pressButton('Delete');
    $assert_session->addressEquals('admin/structure/media');
    $assert_session->pageTextContains('The media bundle ' . $new_name . ' has been deleted.');

    // Test bundle delete prevention when there is existing media.
    $bundle2 = $this->drupalCreateMediaBundle();
    $label2 = $bundle2->label();
    $media = Media::create(['name' => 'lorem ipsum', 'bundle' => $bundle2->id()]);
    $media->save();
    $this->drupalGet('admin/structure/media/manage/' . $bundle2->id());
    $page->clickLink('Delete');
    $assert_session->addressEquals('admin/structure/media/manage/' . $bundle2->id() . '/delete');
    $assert_session->fieldNotExists('edit-submit');
    $assert_session->pageTextContains("$label2 is used by 1 piece of content on your site. You can not remove this content type until you have removed all of the $label2 content.");

  }

}
