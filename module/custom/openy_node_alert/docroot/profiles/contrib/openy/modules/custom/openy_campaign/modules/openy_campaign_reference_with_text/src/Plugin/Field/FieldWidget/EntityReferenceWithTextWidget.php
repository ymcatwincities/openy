<?php

namespace Drupal\openy_campaign_reference_with_text\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference_with_text' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_with_text_widget",
 *   label = @Translation("Check boxes/radio buttons with text field"),
 *   field_types = {
 *     "entity_reference_with_text"
 *   },
 *   multiple_values = TRUE
 * )
 */
class EntityReferenceWithTextWidget extends OptionsWidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $selectedValues = [];

    foreach ($items as $item) {
      $selectedValues[$item->target_id] = $item->value;
    }

    $options = $this->getOptions($items->getEntity());

    $header = [
      'branch' => $this->t('Branch'),
      'target' => $this->t('Total Members'),
    ];

    foreach ($options as $id => $option) {

      $data[$id] =
        [
          'tid' => $id,
          'branch' => $option,
          'target' => [
            'data' => [
              '#type' => 'textfield',
              '#default_value' => !empty($selectedValues[$id]) ?
              $selectedValues[$id] : NULL,
              '#value' => !empty($selectedValues[$id]) ?
              $selectedValues[$id] : NULL,
              '#placeholder' => $this->t('Total Members'),
              '#name' => 'branch_target_for_' . $id,
              '#size' => 15,
            ],
          ],
        ];
    }

    $element['branch_target'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $data,
      '#default_value' => $selectedValues,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    if (!$this->required && !$this->multiple) {
      return $this->t('N/A');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    $data = $form_state->getUserInput();

    $field_name = $this->fieldDefinition->getFieldStorageDefinition()->getName();
    foreach ($data[$field_name]['branch_target'] as $target_id) {
      if (!empty($target_id)) {
        $values[] = [
          'target_id' => $target_id,
          'value' => $data['branch_target_for_' . $target_id]
        ];
      }
    }

    return $values;
  }

}
