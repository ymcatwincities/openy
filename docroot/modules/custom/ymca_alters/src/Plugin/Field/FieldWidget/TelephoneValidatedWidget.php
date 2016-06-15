<?php

namespace Drupal\ymca_alters\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\telephone\Plugin\Field\FieldWidget\TelephoneDefaultWidget;

/**
 * Plugin implementation of the 'telephone_default' widget.
 *
 * @FieldWidget(
 *   id = "telephone_default",
 *   label = @Translation("Telephone number"),
 *   field_types = {
 *     "telephone"
 *   }
 * )
 */
class TelephoneValidatedWidget extends TelephoneDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'validate_pattern' => '',
      'validate_message' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['validate_pattern'] = array(
      '#type' => 'textfield',
      '#title' => t('Validation pattern'),
      '#default_value' => $this->getSetting('validate_pattern'),
      '#description' => t('The regexp pattern that field value should match. Example: %example', [
        '%example' => '^(\d{10})|\((\d{3})\)(\d{3})\-(\d{4})$',
      ]),
    );
    $form['validate_message'] = array(
      '#type' => 'textfield',
      '#title' => t('Validation message'),
      '#default_value' => $this->getSetting('validate_message'),
      '#description' => t('Text that will be shown if field value doesn\'t match the pattern. This hint is usually a sample value or a brief description of the expected format. Example: %example', [
        '%example' => t('Telephone number should have 10 digits. Example: 1234567890 or (123)456-7890'),
      ]),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $validate_pattern = $this->getSetting('validate_pattern');
    if (!empty($validate_pattern)) {
      $summary[] = t('Validation pattern: @placeholder', array('@placeholder' => $validate_pattern));
    }
    else {
      $summary[] = t('No Validation pattern');
    }

    $validate_message = $this->getSetting('validate_message');
    if (!empty($validate_message)) {
      $summary[] = t('Validation message: @placeholder', array('@placeholder' => $validate_message));
    }
    else {
      $summary[] = t('No Validation message');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['value'] = $element['value'] + array(
      '#element_validate' => array(array(get_called_class(), 'validateElement')),
    );
    if ($this->getSetting('validate_pattern')) {
      $element['value']['#attributes']['pattern'] = $this->getSetting('validate_pattern');
      $element['value']['#attributes']['title'] = t($this->getSetting('validate_message'));
    }
    return $element;
  }

  /**
   * Form element validation handler.
   */
  public static function validateElement(&$element, FormStateInterface $form_state, $form) {
    if (empty($element['#attributes']['pattern'])) {
      return;
    }
    if (preg_match('/' . $element['#attributes']['pattern'] . '/', $element['#value']) === 0) {
      if (!empty($element['#attributes']['title']) && $title = (string) $element['#attributes']['title']) {
        $form_state->setError($element, $title);
      }
      else {
        $form_state->setError($element, t('@name doesn\'t match the pattern', array('@name' => $element['#title'])));
      }
    }
  }

}
