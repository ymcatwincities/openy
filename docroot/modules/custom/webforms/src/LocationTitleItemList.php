<?php

namespace Drupal\webforms;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

class LocationTitleItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    /** @var \Drupal\contact\Entity\Message $entity */
    $entity = $this->getEntity();
    /** @var FieldItemList $location */
    $location = $entity->get('field_y_location_email');
    if (!$location->isEmpty()) {
      /** @var \Drupal\webforms\Plugin\Field\FieldType\OptionsEmailItem $optionsEmailItem */
      $optionsEmailItem = $location->get(0);
      $id = $optionsEmailItem->get('option_emails')->getValue();
      // @todo Load entity by ID and set it in the next line.
      $this->list[0] = $this->createItem(0, $location->name);
    }

    return "";
  }

}
