<?php

namespace Drupal\geolocation\Plugin\geolocation\Geocoder;

use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;
use Drupal\geolocation\GeocoderBase;
use Drupal\geolocation\GoogleMapsDisplayTrait;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Google Geocoding API.
 *
 * @Geocoder(
 *   id = "google_geocoding_api",
 *   name = @Translation("Google Geocoding API"),
 *   description = @Translation("You do require an API key for this plugin to work."),
 *   locationCapable = true,
 *   boundaryCapable = true,
 * )
 */
class GoogleGeocodingAPI extends GeocoderBase {

  use GoogleMapsDisplayTrait;

  /**
   * {@inheritdoc}
   */
  public function getOptionsForm() {
    return [
      'components' => [
        '#type' => 'fieldset',
        '#title' => t("Component presets"),
        '#description' => t("See https://developers.google.com/maps/documentation/geocoding/intro#ComponentFiltering"),
        'route' => [
          '#type' => 'textfield',
          '#default_value' => isset($this->configuration['components']['route']) ? $this->configuration['components']['route'] : '',
          '#title' => t("Route"),
          '#size' => 15,
        ],
        'locality' => [
          '#type' => 'textfield',
          '#default_value' => isset($this->configuration['components']['locality']) ? $this->configuration['components']['locality'] : '',
          '#title' => t("Locality"),
          '#size' => 15,
        ],
        'administrativeArea' => [
          '#type' => 'textfield',
          '#default_value' => isset($this->configuration['components']['administrative_area']) ? $this->configuration['components']['administrativeArea'] : '',
          '#title' => t("Administrative Area"),
          '#size' => 15,
        ],
        'postalCode' => [
          '#type' => 'textfield',
          '#default_value' => isset($this->configuration['components']['postal_code']) ? $this->configuration['components']['postalCode'] : '',
          '#title' => t("Postal code"),
          '#size' => 5,
        ],
        'country' => [
          '#type' => 'textfield',
          '#default_value' => isset($this->configuration['components']['country']) ? $this->configuration['components']['country'] : '',
          '#title' => t("Country"),
          '#size' => 5,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formAttachGeocoder(array &$render_array, $element_name) {
    $render_array['geolocation_geocoder_google_geocoding_api'] = [
      '#type' => 'textfield',
      '#description' => t('Enter an address to filter results.'),
      '#attributes' => [
        'class' => [
          'form-autocomplete',
          'geolocation-views-filter-geocoder',
          'geolocation-geocoder-google-geocoding-api',
        ],
        'data-source-identifier' => $element_name,
      ],
    ];
    if (!empty($render_array[$element_name]['#title'])) {
      $render_array['geolocation_geocoder_google_geocoding_api']['#title'] = $render_array[$element_name]['#title'];
    }
    elseif ($this->configuration['label']) {
      $render_array['geolocation_geocoder_google_geocoding_api']['#title'] = $this->configuration['label'];
    }

    $render_array = array_merge_recursive($render_array, [
      '#attached' => [
        'library' => [
          0 => 'geolocation/geolocation.geocoder.googlegeocodingapi',
        ],
        'drupalSettings' => [
          'geolocation' => [
            'google_map_url' => $this->getGoogleMapsApiUrl(),
          ],
        ],
      ],
    ]);

    $render_array['geolocation_geocoder_google_geocoding_api_state'] = [
      '#type' => 'hidden',
      '#default_value' => 1,
      '#attributes' => [
        'class' => [
          'geolocation-geocoder-google-geocoding-api-state',
        ],
        'data-source-identifier' => $element_name,
      ],
    ];

    $config = \Drupal::config('geolocation.settings');

    if (!empty($config->get('google_map_custom_url_parameters')['region'])) {
      $form['#attached']['drupalSettings']['geolocation']['geocoder']['googleGeocodingAPI']['region'] = $config->get('google_map_custom_url_parameters')['region'];
    }

    if (!empty($this->configuration['components'])) {
      foreach ($this->configuration['components'] as $component => $restriction) {
        if (empty($restriction)) {
          continue;
        }
        $render_array['geolocation_geocoder_google_geocoding_api'] = array_merge_recursive($render_array['geolocation_geocoder_google_geocoding_api'], [
          '#attached' => [
            'drupalSettings' => [
              'geolocation' => [
                'geocoder' => [
                  'googleGeocodingAPI' => [
                    'components' => [
                      $component => $restriction,
                    ],
                  ],
                ],
              ],
            ],
          ],
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formValidateInput(FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    if (
      !empty($input['geolocation_geocoder_google_geocoding_api'])
      && empty($input['geolocation_geocoder_google_geocoding_api_state'])
    ) {
      $location_data = $this->geocode($input['geolocation_geocoder_google_geocoding_api']);

      if (empty($location_data)) {
        $form_state->setErrorByName('geolocation_geocoder_google_geocoding_api', t('Failed to geocode %input.', ['%input' => $input['geolocation_geocoder_google_geocoding_api']]));
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
      !empty($input['geolocation_geocoder_google_geocoding_api'])
      && empty($input['geolocation_geocoder_google_geocoding_api_state'])
    ) {
      $location_data = $this->geocode($input['geolocation_geocoder_google_geocoding_api']);

      if (empty($location_data)) {
        $input['geolocation_geocoder_google_geocoding_api_state'] = 0;
        return FALSE;
      }

      $input['geolocation_geocoder_google_geocoding_api'] = $location_data['address'];
      $input['geolocation_geocoder_google_geocoding_api_state'] = 1;

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
    $request_url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $address;

    $config = \Drupal::config('geolocation.settings');

    if (!empty($config->get('google_map_api_key'))) {
      $request_url .= '&key=' . $config->get('google_map_api_key');
    }
    if (!empty($this->configuration['components'])) {
      $request_url .= '&components=';
      foreach ($this->configuration['components'] as $component_id => $component_value) {
        $request_url .= $component_id . ':' . $component_value . '|';
      }
    }
    if (!empty($config->get('google_map_custom_url_parameters')['language'])) {
      $request_url .= '&language=' . $config->get('google_map_custom_url_parameters')['language'];
    }

    try {
      $result = Json::decode(\Drupal::httpClient()->request('GET', $request_url)->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('geolocation', $e);
      return FALSE;
    }

    if (
      $result['status'] != 'OK'
      || empty($result['results'][0]['geometry'])
    ) {
      return FALSE;
    }

    return [
      'location' => [
        'lat' => $result['results'][0]['geometry']['location']['lat'],
        'lng' => $result['results'][0]['geometry']['location']['lng'],
      ],
      // TODO: Add viewport or build it if missing.
      'boundary' => [
        'lat_north_east' => empty($result['results'][0]['geometry']['viewport']) ? $result['results'][0]['geometry']['location']['lat'] + 0.005 : $result['results'][0]['geometry']['viewport']['northeast']['lat'],
        'lng_north_east' => empty($result['results'][0]['geometry']['viewport']) ? $result['results'][0]['geometry']['location']['lng'] + 0.005 : $result['results'][0]['geometry']['viewport']['northeast']['lng'],
        'lat_south_west' => empty($result['results'][0]['geometry']['viewport']) ? $result['results'][0]['geometry']['location']['lat'] - 0.005 : $result['results'][0]['geometry']['viewport']['southwest']['lat'],
        'lng_south_west' => empty($result['results'][0]['geometry']['viewport']) ? $result['results'][0]['geometry']['location']['lng'] - 0.005 : $result['results'][0]['geometry']['viewport']['southwest']['lng'],
      ],
      'address' => empty($result['results'][0]['formatted_address']) ? '' : $result['results'][0]['formatted_address'],
    ];
  }

}
