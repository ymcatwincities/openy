<?php

namespace Drupal\geolocation_google_places_api\Plugin\geolocation\Geocoder;

use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;
use Drupal\geolocation\GeocoderBase;
use Drupal\geolocation\GoogleMapsDisplayTrait;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Google Places API.
 *
 * @Geocoder(
 *   id = "google_places_api",
 *   name = @Translation("Google Places API"),
 *   description = @Translation("Attention: This Plugin needs you to follow Google Places API TOS and either use the Attribution Block or provide it yourself."),
 *   locationCapable = true,
 *   boundaryCapable = true,
 * )
 */
class GooglePlacesAPI extends GeocoderBase {

  use GoogleMapsDisplayTrait;

  /**
   * {@inheritdoc}
   */
  public function getOptionsForm() {
    $settings = [
      'route' => '',
      'locality' => '',
      'administrativeArea' => '',
      'postalCode' => '',
      'country' => '',
    ];
    if (isset($this->configuration['component_restrictions'])) {
      $settings = array_replace($settings, $this->configuration['component_restrictions']);
    }

    return [
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $this->getPluginDefinition()['description'],
      ],
      'component_restrictions' => [
        '#type' => 'fieldset',
        '#title' => t("Component Restrictions"),
        'route' => [
          '#type' => 'textfield',
          '#default_value' => $settings['route'],
          '#title' => t("Route"),
          '#size' => 15,
        ],
        'locality' => [
          '#type' => 'textfield',
          '#default_value' => $settings['locality'],
          '#title' => t("Locality"),
          '#size' => 15,
        ],
        'administrativeArea' => [
          '#type' => 'textfield',
          '#default_value' => $settings['administrativeArea'],
          '#title' => t("Administrative Area"),
          '#size' => 15,
        ],
        'postalCode' => [
          '#type' => 'textfield',
          '#default_value' => $settings['postalCode'],
          '#title' => t("Postal code"),
          '#size' => 5,
        ],
        'country' => [
          '#type' => 'textfield',
          '#default_value' => $settings['country'],
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
    $render_array['#attached']['drupalSettings']['geolocation']['google_map_url'] = $this->getGoogleMapsApiUrl();

    $render_array['geolocation_geocoder_google_places_api'] = [
      '#type' => 'textfield',
      '#description' => t('Enter an address to filter results.'),
      '#attributes' => [
        'class' => [
          'form-autocomplete',
          'geolocation-views-filter-geocoder',
          'geolocation-geocoder-google-places-api',
        ],
        'data-source-identifier' => $element_name,
      ],
    ];
    $render_array['geolocation_geocoder_google_places_api_state'] = [
      '#type' => 'hidden',
      '#default_value' => 1,
      '#attributes' => [
        'class' => [
          'geolocation-geocoder-google-places-api-state',
        ],
        'data-source-identifier' => $element_name,
      ],
    ];

    if (!empty($render_array[$element_name]['#title'])) {
      $render_array['geolocation_geocoder_google_places_api']['#title'] = $render_array[$element_name]['#title'];
    }
    elseif ($this->configuration['label']) {
      $render_array['geolocation_geocoder_google_places_api']['#title'] = $this->configuration['label'];
    }

    $render_array['geolocation_geocoder_google_places_api'] = array_merge_recursive($render_array['geolocation_geocoder_google_places_api'], [
      '#attached' => [
        'library' => [
          0 => 'geolocation_google_places_api/geolocation_google_places_api.geocoder.googleplacesapi',
        ],
      ],
    ]);

    if (!empty($this->configuration['component_restrictions'])) {
      foreach ($this->configuration['component_restrictions'] as $component => $restriction) {
        if (empty($restriction)) {
          continue;
        }
        $render_array['geolocation_geocoder_google_places_api'] = array_merge_recursive($render_array['geolocation_geocoder_google_places_api'], [
          '#attached' => [
            'drupalSettings' => [
              'geolocation' => [
                'geocoder' => [
                  'googlePlacesAPI' => [
                    'restrictions' => [
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
      !empty($input['geolocation_geocoder_google_places_api'])
      && empty($input['geolocation_geocoder_google_places_api_state'])
    ) {
      $location_data = $this->geocode($input['geolocation_geocoder_google_places_api']);

      if (empty($location_data)) {
        $form_state->setErrorByName('geolocation_geocoder_google_places_api', t('Failed to geocode %input.', ['%input' => $input['geolocation_geocoder_google_places_api']]));
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
      !empty($input['geolocation_geocoder_google_places_api'])
      && empty($input['geolocation_geocoder_google_places_api_state'])
    ) {
      $location_data = $this->geocode($input['geolocation_geocoder_google_places_api']);

      if (empty($location_data)) {
        $input['geolocation_geocoder_google_places_api_state'] = 0;
        return FALSE;
      }

      $input['geolocation_geocoder_google_places_api'] = $location_data['address'];
      $input['geolocation_geocoder_google_places_api_state'] = 1;

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
    $request_url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?input=' . $address;

    $config = \Drupal::config('geolocation.settings');

    if (!empty($config->get('google_map_api_key'))) {
      $request_url .= '&key=' . $config->get('google_map_api_key');
    }
    if (!empty($this->configuration['component_restrictions']['country'])) {
      $request_url .= '&components=country:' . $this->configuration['component_restrictions']['country'];
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
      || empty($result['predictions'][0]['place_id'])
    ) {
      return FALSE;
    }

    try {
      $details_url = 'https://maps.googleapis.com/maps/api/place/details/json?placeid=' . $result['predictions'][0]['place_id'];

      if (!empty($config->get('google_map_api_key'))) {
        $details_url .= '&key=' . $config->get('google_map_api_key');
      }
      $details = Json::decode(\Drupal::httpClient()->request('GET', $details_url)->getBody());

    }
    catch (RequestException $e) {
      watchdog_exception('geolocation', $e);
      return FALSE;
    }

    if (
      $details['status'] != 'OK'
      || empty($details['result']['geometry']['location'])
    ) {
      return FALSE;
    }

    return [
      'location' => [
        'lat' => $details['result']['geometry']['location']['lat'],
        'lng' => $details['result']['geometry']['location']['lng'],
      ],
      // TODO: Add viewport or build it if missing.
      'boundary' => [
        'lat_north_east' => empty($details['result']['geometry']['viewport']) ? $details['result']['geometry']['location']['lat'] + 0.005 : $details['result']['geometry']['viewport']['northeast']['lat'],
        'lng_north_east' => empty($details['result']['geometry']['viewport']) ? $details['result']['geometry']['location']['lng'] + 0.005 : $details['result']['geometry']['viewport']['northeast']['lng'],
        'lat_south_west' => empty($details['result']['geometry']['viewport']) ? $details['result']['geometry']['location']['lat'] - 0.005 : $details['result']['geometry']['viewport']['southwest']['lat'],
        'lng_south_west' => empty($details['result']['geometry']['viewport']) ? $details['result']['geometry']['location']['lng'] - 0.005 : $details['result']['geometry']['viewport']['southwest']['lng'],
      ],
      'address' => empty($details['result']['formatted_address']) ? '' : $details['result']['formatted_address'],
    ];
  }

}
