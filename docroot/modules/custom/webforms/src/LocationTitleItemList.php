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

    // Try to find the first "options_email_item".
    $entityManager = \Drupal::service('entity_field.manager');
    $fields = $entityManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());

    /** @var \Drupal\field\Entity\FieldConfig $field */
    foreach ($fields as $field) {
      $type = $field->getType();
      if ($type == 'options_email_item') {

        $locationField = $entity->get($field->getName());
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

        break;
      }
    }

  }

}
