<?php

namespace Drupal\Tests\media_entity\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media_entity\Entity\Media;

/**
 * Tests the integration between Inline Entity Form and Media Entity.
 *
 * @group media_entity
 */
class MediaIefIntegrationTest extends MediaEntityJavascriptTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['inline_entity_form'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\media_entity\MediaBundleInterface $media_bundle */
    $media_bundle = $this->drupalCreateMediaBundle();

    // Create a new content type.
    $values = [
      'name' => 'Media entity CT',
      'title_label' => 'An example Custom Content type.',
      'type' => 'media_entity_ct',
    ];
    $content_type = $this->createContentType($values);
    // Create an entity_reference field.
    FieldStorageConfig::create([
      'field_name' => 'ref_media_entities',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'media',
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => 'ref_media_entities',
      'field_type' => 'entity_reference',
      'entity_type' => 'node',
      'bundle' => $content_type->id(),
      'label' => 'Media referenced',
      'settings' => [
        'handler' => 'default:media',
        'handler_settings' => [
          'target_bundles' => [
            $media_bundle->id() => $media_bundle->id(),
          ],
          'sort' => [
            'field' => '_none',
          ],
          'auto_create' => FALSE,
          'auto_create_bundle' => $media_bundle->id(),
        ],
      ],
    ])->save();

    // Set widget to inline_entity_form.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('node.media_entity_ct.default');
    $form_display->setComponent('ref_media_entities', [
      'type' => 'inline_entity_form_complex',
      'settings' => [],
    ])->save();

  }

  /**
   * Tests inline_entity_form integration with media entities.
   */
  public function testMediaIefIntegration() {

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Open up a node form and check the IEF widget.
    $this->drupalGet('/node/add/media_entity_ct');
    $assert_session->buttonExists('edit-ref-media-entities-actions-ief-add');
    $page->pressButton('edit-ref-media-entities-actions-ief-add');
    $assert_session->assertWaitOnAjaxRequest();

    // Check the presence of the entity's label field.
    $page->findField('ref_media_entities[form][inline_entity_form][name][0][value]')->isVisible();

    // Submit the form to create a media entity and verify that it is correctly
    // created.
    $media_name = $this->randomMachineName();
    $page->fillField('ref_media_entities[form][inline_entity_form][name][0][value]', $media_name);
    $page->fillField('ref_media_entities[form][inline_entity_form][uid][0][target_id]', $this->adminUser->getDisplayName() . ' (' . $this->adminUser->id() . ')');
    $page->pressButton('Create media');
    $assert_session->assertWaitOnAjaxRequest();

    // We need to save the node in order for IEF to do its thing.
    $page->fillField('title[0][value]', $this->randomString());
    $page->pressButton('Save');

    $media_id = $this->container->get('entity.query')->get('media')->execute();
    $media_id = reset($media_id);
    /** @var \Drupal\media_entity\MediaInterface $media */
    $media = Media::load($media_id);
    $this->assertEquals($media_name, $media->label(), 'A media inside IEF was correctly created.');

  }

}
