<?php

namespace Drupal\openy_campaign_reference_with_text\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Plugin implementation of the 'entity_reference_with_text_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_with_text_formatter",
 *   label = @Translation("Entity Reference With Text formatter"),
 *   field_types = {
 *     "entity_reference_with_text"
 *   }
 * )
 */
class EntityReferenceWithTextFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

}
