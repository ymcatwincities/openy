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
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + array(
      '#type' => 'tel',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#placeholder' => $this->getSetting('placeholder'),
      '#element_validate' => array(array(get_called_class(), 'validateElement')),
      '#attributes' => array(
        'pattern' => '^(\d{10})|\((\d{3})\)(\d{3})\-(\d{4})$',
        'title' => t('Telephone number should have 10 digits. Example: 1234567890 or (123)456-7890'),
      ),
    );
    return $element;
  }

  /**
   * Form element validation handler.
   */
  public static function validateElement(&$element, FormStateInterface $form_state, $form) {
    if (preg_match('/' . $element['#attributes']['pattern'] . '/', $element['#value']) === 0) {
      $form_state->setError($element, t($element['#attributes']['title']->__toString()));
    }
  }

}
