<?php

namespace Drupal\file_entity\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to the file.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("file_entity_link")
 */
class Link extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if ($entity = $this->getEntity($values)) {
      return $this->renderLink($entity, $values);
    }
  }

}
