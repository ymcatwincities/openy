<?php

namespace Drupal\geolocation\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;

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
      '#description' => $this->t('Enter any text or HTML to be shown for each value. Tokens will be replaced as available. The "token" module greatly expands the number of available tokens as well as provides a comfortable token browser. Additionally you can use "[geolocation_current_item:lat]" and "[geolocation_current_item:lng]" tokens here, which will be replaced for each value.'),
      '#default_value' => $settings['tokenized_text'],
    ];

    if (\Drupal::service('module_handler')->moduleExists('token')) {
      // Add the token UI from the token module if present.
      $element['token_help'] = [
        '#theme' => 'token_tree_link',
        '#prefix' => $this->t('<h4>Tokens:</h4>'),
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
      ];
    }

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
      $token_context['geolocation_current_item'] = (object) [
        'lat' => $item->lat,
        'lng' => $item->lng,
      ];
      $tokenized_text = \Drupal::token()->replace($this->getSetting('tokenized_text'), $token_context, ['callback' => [$this, 'geolocationItemTokens']]);
      $elements[$delta] = [
        '#markup' => $tokenized_text,
      ];
    }

    return $elements;
  }

  /**
   * Token replacement support function, callback to token replacement function.
   *
   * @param array $replacements
   *   An associative array variable containing mappings from token names to
   *   values (for use with strtr()).
   * @param array $data
   *   An associative array of token replacement values. If the 'user' element
   *   exists, it must contain a user account object with the following
   *   properties:
   *   - login: The UNIX timestamp of the user's last login.
   *   - pass: The hashed account login password.
   * @param array $options
   *   A keyed array of settings and flags to control the token replacement
   *   process. See \Drupal\Core\Utility\Token::replace().
   */
  public function geolocationItemTokens(array &$replacements, array $data, array $options) {
    if (isset($data['geolocation_current_item'])) {
      $replacements['[geolocation_current_item:lat]'] = $data['geolocation_current_item']->lat;
      $replacements['[geolocation_current_item:lng]'] = $data['geolocation_current_item']->lng;
    }
  }

}
