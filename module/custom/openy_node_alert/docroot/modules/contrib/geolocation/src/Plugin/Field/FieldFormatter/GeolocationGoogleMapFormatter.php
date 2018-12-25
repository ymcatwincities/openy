<?php

namespace Drupal\geolocation\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geolocation\GoogleMapsDisplayTrait;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\geolocation\GeolocationItemTokenTrait;

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
  use GeolocationItemTokenTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [];
    $settings['title'] = '';
    $settings['set_marker'] = TRUE;
    $settings['common_map'] = FALSE;
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

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    if (
      $cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
      || $cardinality > 1
    ) {
      $element['common_map'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display multiple values on a common map'),
        '#description' => $this->t('By default, each value will be displayed in a separate map. Settings this option displays all values on a common map instead. This settings is only useful on multi-value fields.'),
        '#default_value' => $settings['common_map'],
        '#states' => [
          'visible' => [
            ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][set_marker]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

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

    $element['replacement_patterns']['token_geolocation'] = $this->getTokenHelp();

    $element += $this->getGoogleMapsSettingsForm($settings, 'fields][' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][');

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
      if (!empty($settings['common_map'])) {
        $summary[] = $this->t('Common Map Display: Yes');
      }
    }
    $summary = array_merge($summary, $this->getGoogleMapsSettingsSummary($settings));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    if ($items->count() == 0) {
      return [];
    }

    $settings = $this->getSettings();
    $map_settings = $this->getGoogleMapsSettings($this->getSettings());

    if (
      $settings['use_overridden_map_settings']
      && !empty($items->get(0)->getValue()['data']['google_map_settings'])
      && is_array($items->get(0)->getValue()['data']['google_map_settings'])
    ) {
      $map_settings = $this->getGoogleMapsSettings($items->get(0)->getValue()['data']);
    }

    $render_array = [
      '#theme' => 'geolocation_map_formatter',
      '#attached' => [
        'library' => ['geolocation/geolocation.formatter.googlemap'],
        'drupalSettings' => [
          'geolocation' => [
            'google_map_url' => $this->getGoogleMapsApiUrl(),
          ],
        ],
      ],
    ];

    if (empty($settings['set_marker'])) {
      $single_map = $render_array;

      $unique_id = uniqid("map-canvas-");

      if ($single_center = $items->get(0)) {
        $single_map['#latitude'] = $items->get(0)->getValue()['lat'];
        $single_map['#longitude'] = $items->get(0)->getValue()['lng'];
      }
      $single_map['#uniqueid'] = $unique_id;
      $single_map['#attached']['drupalSettings']['geolocation']['maps'][$unique_id] = [
        'settings' => $map_settings,
      ];

      return $single_map;
    }

    $elements = [];

    $token_context = [
      $this->fieldDefinition->getTargetEntityTypeId() => $items->getEntity(),
    ];

    $locations = [];

    foreach ($items as $delta => $item) {
      $token_context['geolocation_current_item'] = $item;

      $title = \Drupal::token()->replace($settings['title'], $token_context, [
        'callback' => [$this, 'geolocationItemTokens'],
        'clear' => TRUE,
      ]);
      if (empty($title)) {
        $title = $item->lat . ', ' . $item->lng;
      }
      $content = \Drupal::token()->replace($settings['info_text'], $token_context, [
        'callback' => [$this, 'geolocationItemTokens'],
        'clear' => TRUE,
      ]);

      $location = [
        '#theme' => 'geolocation_common_map_location',
        '#content' => $content,
        '#title' => $title,
        '#position' => [
          'lat' => $item->lat,
          'lng' => $item->lng,
        ],
      ];

      if (!empty($settings['common_map'])) {
        $locations[] = $location;
      }
      else {
        $unique_id = uniqid("map-canvas-");

        $elements[$delta] = $render_array;
        $elements[$delta]['#locations'] = [$location];
        $elements[$delta]['#uniqueid'] = $unique_id;
        $elements[$delta]['#attached']['drupalSettings']['geolocation']['maps'][$unique_id] = [
          'settings' => $map_settings,
        ];
      }
    }

    if (!empty($settings['common_map'])) {
      $unique_id = uniqid("map-canvas-");

      $elements = $render_array;
      $elements['#locations'] = $locations;
      $elements['#uniqueid'] = $unique_id;
      $elements['#attached']['drupalSettings']['geolocation']['maps'][$unique_id] = [
        'settings' => $map_settings,
      ];
    }

    return $elements;
  }

}
