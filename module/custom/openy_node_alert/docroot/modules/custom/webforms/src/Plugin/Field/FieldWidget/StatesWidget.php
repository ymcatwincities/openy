<?php

namespace Drupal\webforms\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'webform_states_widget' widget.
 *
 * @FieldWidget(
 *   id = "webform_states_widget",
 *   label = @Translation("List of US and Canada states and provinces"),
 *   field_types = {
 *     "webform_states_item"
 *   }
 * )
 */
class StatesWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'placeholder' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $placeholder));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $us_subdivisions = file_get_contents(drupal_get_path('module', 'webforms') . '/resources/US.json');
    $us_subdivisions = json_decode($us_subdivisions, TRUE);
    $ca_subdivisions = file_get_contents(drupal_get_path('module', 'webforms') . '/resources/CA.json');
    $ca_subdivisions = json_decode($ca_subdivisions, TRUE);

    $us_options = array();
    foreach ($us_subdivisions['subdivisions'] as $state) {
      $us_options[$state['code']] = $state['name'];
    }

    $ca_options = array();
    foreach ($ca_subdivisions['subdivisions'] as $state) {
      $ca_options[$state['code']] = $state['name'];
    }
    $element['value'] = $element + array(
      '#type' => 'select',
      '#title' => t('State (optional)'),
      '#default_value' => 0,
      '#options' => array(
        0 => t('Select State or Province...'),
        'United States' => $us_options,
        'Canada' => $ca_options,
        'Other/None' => array(
          'Other' => 'Other',
        ),
      ),
    );
    return $element;
  }

}
