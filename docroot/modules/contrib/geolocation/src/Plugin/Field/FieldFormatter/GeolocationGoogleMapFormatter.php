<?php

namespace Drupal\geolocation\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geolocation\GoogleMapsDisplayTrait;

/**
 * Plugin implementation of the 'geolocation_latlng' formatter.
 *
 * @FieldFormatter(
 *   id = "geolocation_map",
 *   module = "geolocation",
 *   label = @Translation("Geolocation Google Maps API - Map"),
 *   field_types = {
 *     "geolocation"
 *   }
 * )
 */
class GeolocationGoogleMapFormatter extends FormatterBase {

  use GoogleMapsDisplayTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [];
    $settings['title'] = '';
    $settings['set_marker'] = TRUE;
    $settings['info_text'] = '';
    $settings += parent::defaultSettings();
    $settings['use_overridden_map_settings'] = FALSE;
    $settings += self::getGoogleMapDefaultSettings();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    $element['set_marker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set map marker'),
      '#description' => $this->t('The map will be centered on the stored location. Additionally a marker can be set at the exact location.'),
      '#default_value' => $settings['set_marker'],
    ];

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Marker title'),
      '#description' => $this->t('When the cursor hovers on the marker, this title will be shown as description.'),
      '#default_value' => $settings['title'],
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][set_marker]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['info_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Marker info text'),
      '#description' => $this->t('When the marker is clicked, this text will be shown in a popup above it. Leave blank to not display. Token replacement supported.'),
      '#default_value' => $settings['info_text'],
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][set_marker]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['replacement_patterns'] = [
      '#type' => 'details',
      '#title' => 'Replacement patterns',
      '#description' => $this->t('The following replacement patterns are available for the "Info text" and the "Hover title" settings.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][set_marker]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['replacement_patterns']['native'] = [
      '#markup' => $this->t('<h4>Geolocation field data:</h4><ul><li>Latitude (%lat) or (:lat)</li><li>Longitude (%lng) or (:lng)</li></ul>'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][set_marker]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    // Add the token UI from the token module if present.
    $element['replacement_patterns']['token_help'] = [
      '#theme' => 'token_tree_link',
      '#prefix' => $this->t('<h4>Tokens:</h4>'),
      '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][set_marker]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element += $this->getGoogleMapsSettingsForm($settings);

    $element['use_overridden_map_settings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use custom map settings if provided'),
      '#description' => $this->t('The Geolocation GoogleGeocoder widget optionally allows to define custom map settings to use here.'),
      '#default_value' => $settings['use_overridden_map_settings'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();

    $summary = [];
    $summary[] = $this->t('Marker set: @marker', ['@marker' => $settings['set_marker'] ? $this->t('Yes') : $this->t('No')]);
    if ($settings['set_marker']) {
      $summary[] = $this->t('Marker Title: @type', ['@type' => $settings['title']]);
      $summary[] = $this->t('Marker Info Text: @type', [
        '@type' => current(explode(chr(10), wordwrap($settings['info_text'], 30))),
      ]);
    }
    $summary = array_merge($summary, $this->getGoogleMapsSettingsSummary($settings));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Add formatter settings to the drupalSettings array.
    $field_settings = $this->getGoogleMapsSettings($this->getSettings()) + $this->getSettings();
    $elements = [];
    // This is a list of tokenized settings that should have placeholders
    // replaced with contextual values.
    $tokenized_settings = [
      'info_text',
      'title',
    ];

    foreach ($items as $delta => $item) {
      // @todo: Add token support to the geolocation field exposing sub-fields.
      // Get token context.
      $token_context = [
        'field' => $items,
        $this->fieldDefinition->getTargetEntityTypeId() => $items->getEntity(),
      ];

      if (
        $field_settings['use_overridden_map_settings']
        && !empty($item->getValue()['data']['google_map_settings'])
        && is_array($item->getValue()['data']['google_map_settings'])
      ) {
        $field_settings['google_map_settings'] = array_replace($field_settings['google_map_settings'], $item->getValue()['data']['google_map_settings']);
      }

      $uniqueue_id = uniqid("map-canvas-");

      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => '<div id="' . $uniqueue_id . '" class="geolocation-google-map" data-lat="' . (float) $item->lat . '" data-lng="' . (float) $item->lng . '" data-set-marker="' . $field_settings['set_marker'] . '"></div>',
        '#attached' => [
          'library' => ['geolocation/geolocation.formatter.googlemap'],
          'drupalSettings' => [
            'geolocation' => [
              'maps' => [
                $uniqueue_id => [
                  'id' => "{$uniqueue_id}",
                  'settings' => $field_settings,
                ],
              ],
              'google_map_url' => $this->getGoogleMapsApiUrl(),
            ],
          ],
        ],
      ];

      // Replace placeholders with token values.
      $item_settings = &$elements[$delta]['#attached']['drupalSettings']['geolocation']['maps'][$uniqueue_id]['settings'];
      array_walk($tokenized_settings, function ($v) use (&$item_settings, $token_context, $item) {
        $item_settings[$v] = \Drupal::token()->replace($item_settings[$v], $token_context);
        // TODO: Drupal does not like variables handed to t().
        $item_settings[$v] = $this->t($item_settings[$v], [
          ':lat' => (float) $item->lat,
          '%lat' => (float) $item->lat,
          ':lng' => (float) $item->lng,
          '%lng' => (float) $item->lng,
        ]);
      });

    }
    return $elements;
  }

}
