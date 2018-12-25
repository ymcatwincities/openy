<?php

namespace Drupal\paragraphs\Tests\Experimental;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\paragraphs\Tests\Classic\ParagraphsTestBase;

/**
 * Base class for tests.
 */
abstract class ParagraphsExperimentalTestBase extends ParagraphsTestBase {

  use FieldUiTestTrait;

  /**
   * Adds a Paragraphs field to a given $entity_type.
   *
   * @param string $entity_type_name
   *   Entity type name to be used.
   * @param string $paragraphs_field_name
   *   Paragraphs field name to be used.
   * @param string $entity_type
   *   Entity type where to add the field.
   */
  protected function addParagraphsField($entity_type_name, $paragraphs_field_name, $entity_type) {
    // Add a paragraphs field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => $paragraphs_field_name,
      'entity_type' => $entity_type,
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $entity_type_name,
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ]);
    $field->save();

    $form_display = EntityFormDisplay::create([
      'targetEntityType' => $entity_type,
      'bundle' => $entity_type_name,
      'mode' => 'default',
      'status' => TRUE,
    ])
      ->setComponent($paragraphs_field_name, ['type' => 'paragraphs']);
    $form_display->save();

    $view_display = EntityViewDisplay::create([
      'targetEntityType' => $entity_type,
      'bundle' => $entity_type_name,
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent($paragraphs_field_name, ['type' => 'entity_reference_revisions_entity_view']);
    $view_display->save();
  }

  /**
   * Sets the Paragraphs widget add mode.
   *
   * @param string $content_type
   *   Content type name where to set the widget mode.
   * @param string $paragraphs_field
   *   Paragraphs field to change the mode.
   * @param string $mode
   *   Mode to be set. ('dropdown', 'select' or 'button').
   */
  protected function setAddMode($content_type, $paragraphs_field, $mode) {
    $form_display = EntityFormDisplay::load('node.' . $content_type . '.default')
      ->setComponent($paragraphs_field, [
        'type' => 'paragraphs',
        'settings' => ['add_mode' => $mode]
      ]);
    $form_display->save();
  }

  /**
   * Removes the default paragraph type.
   *
   * @param $content_type
   *   Content type name that contains the paragraphs field.
   */
  protected function removeDefaultParagraphType($content_type) {
    $this->drupalGet('node/add/' . $content_type);
    $this->drupalPostForm(NULL, [], 'Remove');
    $this->assertNoText('No paragraphs added yet.');
  }

}
