<?php

namespace Drupal\file_browser\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\entity_browser\Plugin\Field\FieldWidget\FileBrowserWidget;

/**
 * Entity browser file widget.
 *
 * @FieldWidget(
 *   id = "file_browser",
 *   label = @Translation("File Browser"),
 *   provider = "file_browser",
 *   multiple_values = TRUE,
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class FileBrowser extends FileBrowserWidget {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // If the bundle this field belongs to uses File Browser in any existing
    // form display, we're still applicable. This lets users who are already
    // using this widget to continue their work normally, but prevents future
    // users from using this instead of Entity Browser's widget.
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    $bundle = $field_definition->getTargetBundle();
    $ids = \Drupal::entityQuery('entity_form_display')
      ->condition('bundle', $bundle)
      ->condition('targetEntityType', $entity_type_id)
      ->execute();
    /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay[] $displays */
    $displays = EntityFormDisplay::loadMultiple($ids);
    foreach ($displays as $display) {
      $configuration = $display->getComponent($field_definition->getName());
      if (isset($configuration['type']) && $configuration['type'] == 'file_browser') {
        return TRUE;
      }
    }
    return FALSE;
  }

}
