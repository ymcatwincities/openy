<?php

namespace Drupal\openy_focal_point\Plugin\Field\FieldWidget;

use Drupal\entity_browser\Plugin\Field\FieldWidget\EntityReferenceBrowserWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference' widget for entity browser.
 *
 * @FieldWidget(
 *   id = "openy_focal_point_entity_browser_entity_reference",
 *   label = @Translation("OpenY Entity browser"),
 *   description = @Translation("Uses entity browser to select entities. Pass paragraph type information down so it can be used by focal point"),
 *   multiple_values = TRUE,
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class OpenYFocalPointEntityReferenceBrowserWidget extends EntityReferenceBrowserWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    foreach ($element['current']['items'] as &$item) {
      $item['edit_button']['#ajax']['options']['query'] += [
        'paragraph_type' => $items->getEntity()->bundle(),
        'field_name' => $items->getName(),
      ];
    }

    return $element;
  }

}
