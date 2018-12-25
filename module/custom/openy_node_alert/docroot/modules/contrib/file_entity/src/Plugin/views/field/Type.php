<?php

namespace Drupal\file_entity\Plugin\views\field;

use Drupal\file_entity\Entity\FileType;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field Handler to show the type of a field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("file_entity_type")
 */
class Type extends FieldPluginBase {

  /**
   * Renders the type of the field.
   *
   * @param ResultRow $values
   *   Row Result Values.
   *
   * @return string
   *   Returns the type of the field.
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);

    if ($file_entity = FileType::load($value)) {
      return $file_entity->label();
    }
    else {
      return t('Undefined');
    }
  }

}
