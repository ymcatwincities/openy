<?php

namespace Drupal\geolocation;

/**
 * Class GeolocationItemTokenTrait - Provide Token for geolocation items.
 *
 * @package Drupal\geolocation
 */
trait GeolocationItemTokenTrait {

  /**
   * Return token form element.
   */
  public function getTokenHelp() {
    $element = [];

    // Add the token UI from the token module if present.
    $element['token_items'] = [
      '#type' => 'table',
      '#caption' => $this->t('Geolocation Item Tokens'),
      '#header' => [$this->t('Token'), $this->t('Description')],
    ];

    // Value tokens.
    $element['token_items'][] = [
      'token' => [
        '#plain_text' => '[geolocation_current_item:lat]',
      ],
      'description' => [
        '#plain_text' => $this->t('Current value latitude'),
      ],
    ];
    $element['token_items'][] = [
      'token' => [
        '#plain_text' => '[geolocation_current_item:lng]',
      ],
      'description' => [
        '#plain_text' => $this->t('Current value longitude'),
      ],
    ];

    // Sexagesimal tokens.
    $element['token_items'][] = [
      'token' => [
        '#plain_text' => '[geolocation_current_item:lng_sex]',
      ],
      'description' => [
        '#plain_text' => $this->t('Current value longitude in sexagesimal notation.'),
      ],
    ];
    $element['token_items'][] = [
      'token' => [
        '#plain_text' => '[geolocation_current_item:lng_sex]',
      ],
      'description' => [
        '#plain_text' => $this->t('Current value longitude in sexagesimal notation'),
      ],
    ];

    // Raw tokens.
    $element['token_items'][] = [
      'token' => [
        '#plain_text' => '[geolocation_current_item:lat_sin]',
      ],
      'description' => [
        '#plain_text' => $this->t('Add description'),
      ],
    ];
    $element['token_items'][] = [
      'token' => [
        '#plain_text' => '[geolocation_current_item:lat_cos]',
      ],
      'description' => [
        '#plain_text' => $this->t('Add description'),
      ],
    ];
    $element['token_items'][] = [
      'token' => [
        '#plain_text' => '[geolocation_current_item:lng_rad]',
      ],
      'description' => [
        '#plain_text' => $this->t('Add description'),
      ],
    ];

    $element['token_items'][] = [
      'token' => [
        '#plain_text' => '[geolocation_current_item:data:?]',
      ],
      'description' => [
        '#plain_text' => $this->t('Data stored with the field item'),
      ],
    ];

    if (
      \Drupal::service('module_handler')->moduleExists('token')
      && method_exists($this->fieldDefinition, 'getTargetEntityTypeId')
    ) {
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
   * Token replacement support function, callback to token replacement function.
   *
   * @param array $replacements
   *   An associative array variable containing mappings from token names to
   *   values (for use with strtr()).
   * @param array $data
   *   Current item replacements.
   * @param array $options
   *   A keyed array of settings and flags to control the token replacement
   *   process. See \Drupal\Core\Utility\Token::replace().
   */
  public function geolocationItemTokens(array &$replacements, array $data, array $options) {
    if (isset($data['geolocation_current_item'])) {

      /** @var \Drupal\geolocation\Plugin\Field\FieldType\GeolocationItem $item */
      $item = $data['geolocation_current_item'];
      $replacements['[geolocation_current_item:lat]'] = $item->get('lat')->getValue();
      $replacements['[geolocation_current_item:lat_sex]'] = GeolocationCore::decimalToSexagesimal($item->get('lat')->getValue());
      $replacements['[geolocation_current_item:lng]'] = $item->get('lng')->getValue();
      $replacements['[geolocation_current_item:lng_sex]'] = GeolocationCore::decimalToSexagesimal($item->get('lng')->getValue());
      $replacements['[geolocation_current_item:lat_sin]'] = $item->get('lat_sin')->getValue();
      $replacements['[geolocation_current_item:lat_cos]'] = $item->get('lat_cos')->getValue();
      $replacements['[geolocation_current_item:lng_rad]'] = $item->get('lng_rad')->getValue();

      // Handle data tokens.
      $metadata = $item->get('data')->getValue();
      if (is_array($metadata) || ($metadata instanceof \Traversable)) {
        foreach ($metadata as $key => $value) {
          try {
            // Maybe there is values inside the values.
            if (is_array($value) || ($value instanceof \Traversable)) {
              foreach ($value as $deepkey => $deepvalue) {
                $replacements['[geolocation_current_item:data:' . $key . ':' . $deepkey . ']'] = (string) $deepvalue;
              }
            }
            else {
              $replacements['[geolocation_current_item:data:' . $key . ']'] = (string) $value;
            }
          }
          catch (\Exception $e) {
            watchdog_exception('geolocation', $e);
          }
        }
      }
    }
  }

}
