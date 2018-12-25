<?php

namespace Drupal\webforms\Plugin\Field\FieldWidget;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'webform_markup_widget' widget.
 *
 * @FieldWidget(
 *   id = "webform_markup_widget",
 *   label = @Translation("Markup"),
 *   field_types = {
 *     "webform_markup_item"
 *   }
 * )
 */
class MarkupWidget extends WidgetBase {

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
    if (isset($element['#field_parents'][$delta]) && $element['#field_parents'][$delta] == 'default_value_input') {
      $element['value'] = $element + array(
        '#type' => 'textarea',
        '#default_value' => $items[$delta]->value,
        '#rows' => $this->getSetting('rows'),
        '#placeholder' => $this->getSetting('placeholder'),
        '#attributes' => array('class' => array('js-text-full', 'text-full')),
      );
    }
    else {
      $element['value'] = $element + array(
        '#markup' => new FormattableMarkup($items[$delta]->value, []),
      );
    }

    return $element;
  }

}
