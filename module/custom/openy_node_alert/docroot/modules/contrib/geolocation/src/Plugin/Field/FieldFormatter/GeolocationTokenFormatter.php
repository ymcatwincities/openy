<?php

namespace Drupal\geolocation\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\geolocation\GeolocationItemTokenTrait;

/**
 * Plugin implementation of the 'geolocation_token' formatter.
 *
 * @FieldFormatter(
 *   id = "geolocation_token",
 *   module = "geolocation",
 *   label = @Translation("Geolocation tokenized text"),
 *   field_types = {
 *     "geolocation"
 *   }
 * )
 */
class GeolocationTokenFormatter extends FormatterBase {

  use GeolocationItemTokenTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [];
    $settings['tokenized_text'] = '';
    $settings += parent::defaultSettings();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    $element['tokenized_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Tokenized text'),
      '#description' => $this->t('Enter any text or HTML to be shown for each value. Tokens will be replaced as available. The "token" module greatly expands the number of available tokens as well as provides a comfortable token browser.'),
      '#default_value' => $settings['tokenized_text'],
    ];

    $element['token_help'] = $this->getTokenHelp();

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();

    $summary = [];
    $summary[] = $this->t('Tokenized Text: %text', [
      '%text' => Unicode::truncate(
        $settings['tokenized_text'],
        100,
        TRUE,
        TRUE
      ),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $token_context = [
      $this->fieldDefinition->getTargetEntityTypeId() => $items->getEntity(),
    ];

    $elements = [];
    foreach ($items as $delta => $item) {
      $token_context['geolocation_current_item'] = $item;
      $tokenized_text = \Drupal::token()->replace($this->getSetting('tokenized_text'), $token_context, [
        'callback' => [$this, 'geolocationItemTokens'],
        'clear' => TRUE,
      ]);
      $elements[$delta] = [
        '#markup' => $tokenized_text,
      ];
    }

    return $elements;
  }

}
