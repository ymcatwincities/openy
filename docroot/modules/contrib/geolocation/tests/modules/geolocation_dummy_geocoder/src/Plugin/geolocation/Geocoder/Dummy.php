<?php

namespace Drupal\geolocation_dummy_geocoder\Plugin\geolocation\Geocoder;

use Drupal\geolocation\GeocoderBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Google Geocoding API.
 *
 * @Geocoder(
 *   id = "dummy",
 *   name = @Translation("Dummy Geocoder"),
 *   locationCapable = true,
 *   boundaryCapable = false,
 * )
 */
class Dummy extends GeocoderBase {

  public static $targets = [
    'Berlin' => [
      'lat' => 52.517037,
      'lng' => 13.38886,
    ],
    'Vladivostok' => [
      'lat' => 43.115284,
      'lng' => 131.885401,
    ],
    'Santiago de Chile' => [
      'lat' => -33.437913,
      'lng' => -70.650456,
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function formAttachGeocoder(array &$render_array, $element_name) {
    $render_array['geolocation_geocoder_dummy'] = [
      '#type' => 'textfield',
      '#description' => t('Enter one of the statically defined targets. See geolocation/Geocoder/Dummy.php.'),
      '#attributes' => [
        'class' => [
          'form-autocomplete',
          'geolocation-views-filter-geocoder',
          'geolocation-geocoder-dummy',
        ],
        'data-source-identifier' => $element_name,
      ],
    ];

    $render_array = array_merge_recursive($render_array, [
      '#attached' => [
        'library' => [
          0 => 'geolocation_dummy_geocoder/geocoder',
        ],
      ],
    ]);

    $render_array['geolocation_geocoder_dummy_state'] = [
      '#type' => 'hidden',
      '#default_value' => 1,
      '#attributes' => [
        'class' => [
          'geolocation-geocoder-dummy-state',
        ],
        'data-source-identifier' => $element_name,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formValidateInput(FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    if (
      !empty($input['geolocation_geocoder_dummy'])
      && empty($input['geolocation_geocoder_dummy_state'])
    ) {
      $location_data = $this->geocode($input['geolocation_geocoder_dummy']);

      if (empty($location_data)) {
        $form_state->setErrorByName('geolocation_geocoder_dummy', t('Failed to geocode %input.', ['%input' => $input['geolocation_geocoder_dummy']]));
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function formProcessInput(array &$input, $element_name) {
    if (
      !empty($input['geolocation_geocoder_dummy'])
      && empty($input['geolocation_geocoder_dummy_state'])
    ) {
      $location_data = $this->geocode($input['geolocation_geocoder_dummy']);

      if (empty($location_data)) {
        $input['geolocation_geocoder_dummy_state'] = 0;
        return FALSE;
      }

      $input['geolocation_geocoder_dummy'] = $location_data['address'];
      $input['geolocation_geocoder_dummy_state'] = 1;

      return $location_data;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function geocode($address) {

    if (empty($address)) {
      return FALSE;
    }

    if (!empty(self::$targets[$address])) {
      return [
        'location' => [
          'lat' => self::$targets[$address]['lat'],
          'lng' => self::$targets[$address]['lng'],
        ],
        'boundary' => [
          'lat_north_east' => self::$targets[$address]['lat'] + 0.01,
          'lng_north_east' => self::$targets[$address]['lng'] + 0.01,
          'lat_south_west' => self::$targets[$address]['lat'] + 0.01,
          'lng_south_west' => self::$targets[$address]['lng'] + 0.01,
        ],
      ];
    }
    else {
      return FALSE;
    }
  }

}
