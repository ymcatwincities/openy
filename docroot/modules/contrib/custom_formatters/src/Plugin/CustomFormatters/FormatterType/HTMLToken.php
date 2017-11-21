<?php

namespace Drupal\custom_formatters\Plugin\CustomFormatters\FormatterType;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_formatters\FormatterTypeBase;

/**
 * Plugin implementation of the HTML + Token Formatter type.
 *
 * @FormatterType(
 *   id = "html_token",
 *   label = "HTML + Token",
 *   description = "A HTML based editor with Token support.",
 * )
 */
class HTMLToken extends FormatterTypeBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['tokens'] = [
      '#type'   => 'markup',
      '#markup' => $this->t('@TODO - Message when Token module not present.'),
    ];
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['tokens']['#markup'] = '@TODO - Add token tree';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $text = $this->entity->get('data');
    $token_data = [
      $items->getEntity()->getEntityTypeId() => $items->getEntity(),
    ];

    foreach ($items as $delta => $item) {
      // Allow third parties to modify the available token data.
      $context = [
        'text'  => $text,
        'item'  => $item,
        'delta' => $delta,
      ];
      \Drupal::moduleHandler()
        ->alter('custom_formatters_token_data', $token_data, $context);

      $element[$delta] = [
        '#markup' => \Drupal::token()
          ->replace($text, $token_data, ['clear' => TRUE]),
      ];
    }

    return $element;
  }

}
