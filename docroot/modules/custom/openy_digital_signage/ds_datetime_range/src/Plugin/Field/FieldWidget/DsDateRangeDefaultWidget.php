<?php

namespace Drupal\ds_datetime_range\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDefaultWidget;

/**
 * Plugin implementation of the 'ds_daterange_default' widget.
 *
 * @FieldWidget(
 *   id = "ds_daterange_default",
 *   label = @Translation("Digital Signage Date and time range"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DsDateRangeDefaultWidget extends DateRangeDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'hide_end_date' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['hide_end_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide end date'),
      '#default_value' => $this->getSetting('hide_end_date'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Hide end date: @order', [
      '@order' => $this->getSetting('hide_end_date') ? $this->t('Yes') : $this->t('No'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    if (!$this->getSetting('hide_end_date')) {
      return $element;
    }

    // Hide the end date input.
    $element['end_value']['#date_date_element'] = 'none';
    $element['end_value']['#title'] = $this->t('End time');

    // Add our validation function as the first one.
    array_unshift($element['#element_validate'], [$this, 'validateSetEndDate']);
    return $element;
  }

  /**
   * Callback #element_validate callback to set end date = start date.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function validateSetEndDate(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (empty($element['value']['#value']['date']) || !$this->getSetting('hide_end_date')) {
      return;
    }
    $element['end_value']['#value']['date'] = $element['value']['#value']['date'];
    $element['end_value']['#value']['object'] = new DrupalDateTime($element['end_value']['#value']['date'] . 'T' . $element['end_value']['#value']['time']);
    $value = [
      'value' => $element['value']['#value']['object'],
      'end_value' => $element['end_value']['#value']['object'],
      '_weight' => $element['#weight'],
    ];
    $form_state->setValueForElement($element, $value);
  }

}
