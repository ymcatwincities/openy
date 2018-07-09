<?php

namespace Drupal\webforms;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Class LocationTitleItemList.
 *
 * @package Drupal\webforms
 */
class LocationTitleItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();

    $locationField = $entity->get('field_y_location_email');
    if ($locationField->isEmpty()) {
      return;
    }

    $optionsEmailItem = $locationField->get(0);
    $id = $optionsEmailItem->get('option_emails')->getValue();
    if (!$id) {
      return;
    }

    $loadedNode = \Drupal::entityTypeManager()->getStorage('node')->load($id);
    if ($loadedNode && $loadedNode->bundle() == 'location') {
      $this->list[0] = $this->createItem(0, $loadedNode->getTitle());
    }
  }

}
