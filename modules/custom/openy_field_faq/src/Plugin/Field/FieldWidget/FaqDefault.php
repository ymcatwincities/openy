<?php

namespace Drupal\openy_field_faq\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'faq_default' widget.
 *
 * @FieldWidget(
 *   id = "faq_default",
 *   label = @Translation("Faq default"),
 *   field_types = {
 *     "faq"
 *   }
 * )
 */
class FaqDefault extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['question'] = [
      '#title' => $this->t('Question'),
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->question) ? $items[$delta]->question : NULL,
    ];

    $element['answer'] = [
      '#title' => $this->t('Answer'),
      '#type' => 'text_format',
      '#rows' => 5,
      '#default_value' => isset($items[$delta]->answer) ? $items[$delta]->answer : NULL,
      '#format' => isset($items[$delta]->format) ? $items[$delta]->format : NULL,
      '#base_type' => 'textarea',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    foreach ($values as &$value) {
      $value['answer'] = isset($value['answer']) ? $value['answer']['value'] : '';
    }

    return $values;
  }

}
