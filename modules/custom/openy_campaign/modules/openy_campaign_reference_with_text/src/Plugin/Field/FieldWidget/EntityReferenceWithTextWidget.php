<?php

namespace Drupal\openy_campaign_reference_with_text\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;

/**
 * Plugin implementation of the 'entity_reference_with_text' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_with_text_widget_1",
 *   label = @Translation("Check boxes/radio buttons with text field"),
 *   field_types = {
 *     "entity_reference_with_text_1",
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

    $options = $this->getOptions($items->getEntity());

    //$selected = $this->getSelectedOptions($items);

    //$text = $this->getTextValues($items);


    $header = array(
      'branch' => t('Branch'),
      'target' => t('Target'),
    );

    foreach ($options as $id => $option) {

      $data[$id] =
        [
          'tid' => $id,
          'branch' => $option,
          'target' => [
            'data' => [
              '#type' => 'textfield',
              '#default_value' => isset($items[$delta]->branch_target[$id]) ?
                $items[$delta]->branch_target[$id] : NULL,
              '#placeholder' => t('Target'),
              '#maxlenght' => 10,
              '#name' => 'target_for_' . $id,
            ],
          ],
        ];
    }

    $element['branch_target'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $data,
      /*'#element_validate' => [
        [static::class, 'validate'],
      ],*/
    );

    //return ['value' => $element];

    return $element;
  }

  /**
   * Validate the color text field.
   */
  public static function validate($element, FormStateInterface $form_state) {
    //$value = $element['#value'];

    /*if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }
    if (!preg_match('/^#([a-f0-9]{6})$/iD', strtolower($value))) {
      $form_state->setError($element, t("Color must be a 6-digit hexadecimal value, suitable for CSS."));
    }*/
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    if (!$this->required && !$this->multiple) {
      return t('N/A');
    }
  }

  /**
   * Determines selected options from the incoming text field values.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values.
   *
   * @return array
   *   The array of corresponding selected options.
   */
  protected function getTextValues(FieldItemListInterface $items) {
    // We need to check against a flat list of options.
    $flat_options = OptGroup::flattenOptions($this->getOptions($items->getEntity()));

    $selected_options = [];
    foreach ($items as $item) {
      $value = $item->{$this->column};
      // Keep the value if it actually is in the list of options (needs to be
      // checked against the flat list).
      if (isset($flat_options[$value])) {
        $selected_options[] = $value;
      }
    }

    return $selected_options;
  }


  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    foreach ($values as $delta => $data) {
      if (isset($data['element'])) {
        $values[$delta] = $data['element'];
      }
      if (empty($data['text'])) {
        unset($values[$delta]['text']);
      }
    }
    return $values;
  }
}
