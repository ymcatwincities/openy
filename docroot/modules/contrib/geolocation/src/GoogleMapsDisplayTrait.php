<?php

namespace Drupal\geolocation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class GoogleMapsDisplayTrait.
 *
 * @package Drupal\geolocation
 */
trait GoogleMapsDisplayTrait {

  /**
   * Google map style - Roadmap.
   *
   * @var string
   */
  public static $ROADMAP = 'ROADMAP';

  /**
   * Google map style - Satellite.
   *
   * @var string
   */
  public static $SATELLITE = 'SATELLITE';

  /**
   * Google map style - Hybrid.
   *
   * @var string
   */
  public static $HYBRID = 'HYBRID';

  /**
   * Google map style - Terrain.
   *
   * @var string
   */
  public static $TERRAIN = 'TERRAIN';

  /**
   * Google maps url with default parameters.
   *
   * @var string
   */
  public static $GOOGLEMAPSAPIURL = 'https://maps.googleapis.com/maps/api/js';

  /**
   * Google map max zoom level.
   *
   * @var int
   */
  public static $MAXZOOMLEVEL = 18;

  /**
   * Google map min zoom level.
   *
   * @var int
   */
  public static $MINZOOMLEVEL = 0;

  /**
   * Return all module and custom defined parameters.
   *
   * @return array
   *   Parameters
   */
  public function getGoogleMapsApiParameters() {
    $config = \Drupal::config('geolocation.settings');
    $geolocation_parameters = [
      'callback' => 'Drupal.geolocation.googleCallback',
      'key' => $config->get('google_map_api_key'),
    ];
    $module_parameters = \Drupal::moduleHandler()->invokeAll('geolocation_google_maps_parameters') ?: [];
    $custom_parameters = $config->get('google_map_custom_url_parameters') ?: [];

    // Set the map language to site language if desired and possible.
    if ($config->get('use_current_language') &&  \Drupal::moduleHandler()->moduleExists('language')) {
      $custom_parameters['language'] = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }

    $parameters = array_replace_recursive($custom_parameters, $module_parameters, $geolocation_parameters);

    if (!empty($parameters['client'])) {
      unset($parameters['key']);
    }
    else {
      unset($parameters['channel']);
    }

    return $parameters;
  }

  /**
   * Return the fully build URL to load Google Maps API.
   *
   * @return string
   *   Google Maps API URL
   */
  public function getGoogleMapsApiUrl() {
    $parameters = [];
    foreach ($this->getGoogleMapsApiParameters() as $parameter => $value) {
      $parameters[$parameter] = is_array($value) ? implode(',', $value) : $value;
    }
    $url = Url::fromUri(static::$GOOGLEMAPSAPIURL, [
      'query' => $parameters,
      'https' => TRUE,
    ]);
    return $url->toString();
  }

  /**
   * An array of all available map types.
   *
   * @return array
   *   The map types.
   */
  private function getMapTypes() {
    $mapTypes = [
      static::$ROADMAP => 'Road map view',
      static::$SATELLITE => 'Google Earth satellite images',
      static::$HYBRID => 'A mixture of normal and satellite views',
      static::$TERRAIN => 'A physical map based on terrain information',
    ];

    return array_map([$this, 't'], $mapTypes);
  }

  /**
   * Provide a populated settings array.
   *
   * @return array
   *   The settings array with the default map settings.
   */
  public static function getGoogleMapDefaultSettings() {
    return [
      'google_map_settings' => [
        'type' => static::$ROADMAP,
        'zoom' => 10,
        'minZoom' => static::$MINZOOMLEVEL,
        'maxZoom' => static::$MAXZOOMLEVEL,
        'rotateControl' => FALSE,
        'mapTypeControl' => TRUE,
        'streetViewControl' => TRUE,
        'zoomControl' => TRUE,
        'fullscreenControl' => FALSE,
        'scrollwheel' => TRUE,
        'disableDoubleClickZoom' => FALSE,
        'draggable' => TRUE,
        'height' => '400px',
        'width' => '100%',
        'info_auto_display' => TRUE,
        'marker_icon_path' => '',
        'disableAutoPan' => TRUE,
        'style' => '',
        'preferScrollingToZooming' => FALSE,
        'gestureHandling' => 'auto',
      ],
    ];
  }

  /**
   * Provide settings ready to handover to JS to feed to Google Maps.
   *
   * @param array $settings
   *   Current settings. Might contain unrelated settings as well.
   *
   * @return array
   *   An array only containing keys defined in this trait.
   */
  public function getGoogleMapsSettings(array $settings) {
    $default_settings = self::getGoogleMapDefaultSettings();
    $settings = array_replace_recursive($default_settings, $settings);

    $settings['google_map_settings']['marker_icon_path'] = \Drupal::token()->replace($settings['google_map_settings']['marker_icon_path']);

    foreach ($settings['google_map_settings'] as $key => $setting) {
      if (!isset($default_settings['google_map_settings'][$key])) {
        unset($settings['google_map_settings'][$key]);
      }
    }

    // Convert JSON string to actual array before handing to Renderer.
    if (!empty($settings['google_map_settings']['style'])) {
      $json = json_decode($settings['google_map_settings']['style']);
      if (is_array($json)) {
        $settings['google_map_settings']['style'] = $json;
      }
    }

    return [
      'google_map_settings' => $settings['google_map_settings'],
    ];
  }

  /**
   * Provide a summary array to use in field formatters.
   *
   * @param array $settings
   *   The current map settings.
   *
   * @return array
   *   An array to use as field formatter summary.
   */
  public function getGoogleMapsSettingsSummary(array $settings) {
    $types = $this->getMapTypes();
    $summary = [];
    $summary[] = $this->t('Map Type: @type', ['@type' => $types[$settings['google_map_settings']['type']]]);
    $summary[] = $this->t('Zoom level: @zoom', ['@zoom' => $settings['google_map_settings']['zoom']]);
    $summary[] = $this->t('Height: @height', ['@height' => $settings['google_map_settings']['height']]);
    $summary[] = $this->t('Width: @width', ['@width' => $settings['google_map_settings']['width']]);
    return $summary;
  }

  /**
   * Provide a generic map settings form array.
   *
   * @param array $settings
   *   The current map settings.
   * @param string $form_prefix
   *   Form specific optional prefix.
   *
   * @return array
   *   A form array to be integrated in whatever.
   */
  public function getGoogleMapsSettingsForm(array $settings, $form_prefix = '') {
    $settings['google_map_settings'] += self::getGoogleMapDefaultSettings()['google_map_settings'];
    $form = [
      'google_map_settings' => [
        '#type' => 'details',
        '#title' => t('Google Maps settings'),
        '#description' => t('Additional map settings provided by Google Maps'),
      ],
    ];

    /*
     * General settings.
     */
    $form['google_map_settings']['general_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General'),
    ];
    $form['google_map_settings']['height'] = [
      '#group' => $form_prefix . 'google_map_settings][general_settings',
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#description' => $this->t('Enter the dimensions and the measurement units. E.g. 200px or 100%.'),
      '#size' => 4,
      '#default_value' => $settings['google_map_settings']['height'],
    ];
    $form['google_map_settings']['width'] = [
      '#group' => $form_prefix . 'google_map_settings][general_settings',
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#description' => $this->t('Enter the dimensions and the measurement units. E.g. 200px or 100%.'),
      '#size' => 4,
      '#default_value' => $settings['google_map_settings']['width'],
    ];
    $form['google_map_settings']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default map type'),
      '#options' => $this->getMapTypes(),
      '#default_value' => $settings['google_map_settings']['type'],
      '#group' => $form_prefix . 'google_map_settings][general_settings',
      '#process' => [
        ['\Drupal\Core\Render\Element\RenderElement', 'processGroup'],
        ['\Drupal\Core\Render\Element\Select', 'processSelect'],
      ],
      '#pre_render' => [
        ['\Drupal\Core\Render\Element\RenderElement', 'preRenderGroup'],
      ],
    ];
    $form['google_map_settings']['zoom'] = [
      '#type' => 'select',
      '#title' => $this->t('Zoom level'),
      '#options' => range(static::$MINZOOMLEVEL, static::$MAXZOOMLEVEL),
      '#description' => $this->t('The initial resolution at which to display the map, where zoom 0 corresponds to a map of the Earth fully zoomed out, and higher zoom levels zoom in at a higher resolution.'),
      '#default_value' => $settings['google_map_settings']['zoom'],
      '#group' => $form_prefix . 'google_map_settings][general_settings',
      '#process' => [
        ['\Drupal\Core\Render\Element\RenderElement', 'processGroup'],
        ['\Drupal\Core\Render\Element\Select', 'processSelect'],
      ],
      '#pre_render' => [
        ['\Drupal\Core\Render\Element\RenderElement', 'preRenderGroup'],
      ],
    ];
    $form['google_map_settings']['maxZoom'] = [
      '#group' => $form_prefix . 'google_map_settings][general_settings',
      '#type' => 'select',
      '#title' => $this->t('Max Zoom level'),
      '#options' => range(static::$MINZOOMLEVEL, static::$MAXZOOMLEVEL),
      '#description' => $this->t('The maximum zoom level which will be displayed on the map. If omitted, or set to null, the maximum zoom from the current map type is used instead.'),
      '#default_value' => $settings['google_map_settings']['maxZoom'],
      '#process' => [
        ['\Drupal\Core\Render\Element\RenderElement', 'processGroup'],
        ['\Drupal\Core\Render\Element\Select', 'processSelect'],
      ],
      '#pre_render' => [
        ['\Drupal\Core\Render\Element\RenderElement', 'preRenderGroup'],
      ],
    ];
    $form['google_map_settings']['minZoom'] = [
      '#group' => $form_prefix . 'google_map_settings][general_settings',
      '#type' => 'select',
      '#title' => $this->t('Min Zoom level'),
      '#options' => range(static::$MINZOOMLEVEL, static::$MAXZOOMLEVEL),
      '#description' => $this->t('The minimum zoom level which will be displayed on the map. If omitted, or set to null, the minimum zoom from the current map type is used instead.'),
      '#default_value' => $settings['google_map_settings']['minZoom'],
      '#process' => [
        ['\Drupal\Core\Render\Element\RenderElement', 'processGroup'],
        ['\Drupal\Core\Render\Element\Select', 'processSelect'],
      ],
      '#pre_render' => [
        ['\Drupal\Core\Render\Element\RenderElement', 'preRenderGroup'],
      ],
    ];

    /*
     * Control settings.
     */

    $form['google_map_settings']['control_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Controls'),
    ];
    $form['google_map_settings']['mapTypeControl'] = [
      '#group' => $form_prefix . 'google_map_settings][control_settings',
      '#type' => 'checkbox',
      '#title' => $this->t('Map type control'),
      '#description' => $this->t('Allow the user to change the map type.'),
      '#default_value' => $settings['google_map_settings']['mapTypeControl'],
    ];
    $form['google_map_settings']['streetViewControl'] = [
      '#group' => $form_prefix . 'google_map_settings][control_settings',
      '#type' => 'checkbox',
      '#title' => $this->t('Street view control'),
      '#description' => $this->t('Allow the user to switch to google street view.'),
      '#default_value' => $settings['google_map_settings']['streetViewControl'],
    ];
    $form['google_map_settings']['zoomControl'] = [
      '#group' => $form_prefix . 'google_map_settings][control_settings',
      '#type' => 'checkbox',
      '#title' => $this->t('Zoom control'),
      '#description' => $this->t('Show zoom controls.'),
      '#default_value' => $settings['google_map_settings']['zoomControl'],
    ];
    $form['google_map_settings']['rotateControl'] = [
      '#group' => $form_prefix . 'google_map_settings][control_settings',
      '#type' => 'checkbox',
      '#title' => $this->t('Rotate control'),
      '#description' => $this->t('Show rotate control.'),
      '#default_value' => $settings['google_map_settings']['rotateControl'],
    ];
    $form['google_map_settings']['fullscreenControl'] = [
      '#group' => $form_prefix . 'google_map_settings][control_settings',
      '#type' => 'checkbox',
      '#title' => $this->t('Fullscreen control'),
      '#description' => $this->t('Show fullscreen control.'),
      '#default_value' => $settings['google_map_settings']['fullscreenControl'],
    ];

    /*
     * Behavior settings.
     */
    $form['google_map_settings']['behavior_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Behavior'),
    ];

    $form['google_map_settings']['scrollwheel'] = [
      '#group' => $form_prefix . 'google_map_settings][behavior_settings',
      '#type' => 'checkbox',
      '#title' => $this->t('Scrollwheel'),
      '#description' => $this->t('Allow the user to zoom the map using the scrollwheel.'),
      '#default_value' => $settings['google_map_settings']['scrollwheel'],
    ];
    $form['google_map_settings']['gestureHandling'] = [
      '#group' => $form_prefix . 'google_map_settings][behavior_settings',
      '#type' => 'select',
      '#title' => $this->t('Gesture Handling'),
      '#default_value' => $settings['google_map_settings']['gestureHandling'],
      '#description' => $this->t('Define how to handle interactions with map on mobile. Read the <a href=":introduction">introduction</a> for handling or the <a href=":details">details</a>, <i>available as of v3.27 / Nov. 2016</i>.', [
        ':introduction' => 'https://googlegeodevelopers.blogspot.de/2016/11/smart-scrolling-comes-to-mobile-web-maps.html',
        ':details' => 'https://developers.google.com/maps/documentation/javascript/3.exp/reference#MapOptions',
      ]),
      '#options' => [
        'auto' => $this->t('auto (default)'),
        'cooperative' => $this->t('cooperative'),
        'greedy' => $this->t('greedy'),
        'none' => $this->t('none'),
      ],
      '#process' => [
        ['\Drupal\Core\Render\Element\RenderElement', 'processGroup'],
        ['\Drupal\Core\Render\Element\Select', 'processSelect'],
      ],
      '#pre_render' => [
        ['\Drupal\Core\Render\Element\RenderElement', 'preRenderGroup'],
      ],
    ];
    $form['google_map_settings']['draggable'] = [
      '#group' => $form_prefix . 'google_map_settings][behavior_settings',
      '#type' => 'checkbox',
      '#title' => $this->t('Draggable'),
      '#description' => $this->t('Allow the user to change the field of view. <i>Deprecated as of v3.27 / Nov. 2016 in favor of gesture handling described above.</i>.'),
      '#default_value' => $settings['google_map_settings']['draggable'],
    ];
    $form['google_map_settings']['preferScrollingToZooming'] = [
      '#group' => $form_prefix . 'google_map_settings][behavior_settings',
      '#type' => 'checkbox',
      '#title' => $this->t('Require the user to click the map once to zoom, to ease scrolling behavior.'),
      '#description' => $this->t('Note: this is only relevant, when the Scrollwheel option is enabled.'),
      '#default_value' => $settings['google_map_settings']['preferScrollingToZooming'],
    ];
    $form['google_map_settings']['disableDoubleClickZoom'] = [
      '#group' => $form_prefix . 'google_map_settings][behavior_settings',
      '#type' => 'checkbox',
      '#title' => $this->t('Disable double click zoom'),
      '#description' => $this->t('Disables the double click zoom functionality.'),
      '#default_value' => $settings['google_map_settings']['disableDoubleClickZoom'],
    ];

    $form['google_map_settings']['style'] = [
      '#title' => $this->t('JSON styles'),
      '#type' => 'textarea',
      '#default_value' => $settings['google_map_settings']['style'],
      '#description' => $this->t('A JSON encoded styles array to customize the presentation of the Google Map. See the <a href=":styling">Styled Map</a> section of the Google Maps website for further information.', [
        ':styling' => 'https://developers.google.com/maps/documentation/javascript/styling',
      ]),
    ];

    /*
     * Marker settings.
     */
    $form['google_map_settings']['marker_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Marker'),
    ];

    $form['google_map_settings']['info_auto_display'] = [
      '#group' => $form_prefix . 'google_map_settings][marker_settings',
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically show info text'),
      '#default_value' => $settings['google_map_settings']['info_auto_display'],
    ];
    $form['google_map_settings']['marker_icon_path'] = [
      '#group' => $form_prefix . 'google_map_settings][marker_settings',
      '#type' => 'textfield',
      '#title' => $this->t('Marker icon path'),
      '#description' => $this->t('Set relative or absolute path to custom marker icon. Tokens supported. Empty for default.'),
      '#default_value' => $settings['google_map_settings']['marker_icon_path'],
    ];
    $form['google_map_settings']['disableAutoPan'] = [
      '#group' => $form_prefix . 'google_map_settings][marker_settings',
      '#type' => 'checkbox',
      '#title' => $this->t('Disable automatic panning of map when info bubble is opened.'),
      '#default_value' => $settings['google_map_settings']['disableAutoPan'],
    ];

    return $form;
  }

  /**
   * Validate the form elements defined above.
   *
   * @param array $form
   *   Values to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current Formstate.
   * @param string|null $prefix
   *   Form state prefix if needed.
   */
  public function validateGoogleMapsSettingsForm(array $form, FormStateInterface $form_state, $prefix = NULL) {
    if ($prefix) {
      $values = $form_state->getValues();
      if (!empty($values[$prefix])) {
        $values = $values[$prefix];
        $prefix = $prefix . '][';
      }
      else {
        return;
      }
    }
    else {
      $values = $form_state->getValues();
    }

    $json_style = $values['google_map_settings']['style'];
    if (!empty($json_style)) {
      if (!is_string($json_style)) {
        $form_state->setErrorByName($prefix . 'google_map_settings][style', $this->t('Please enter a JSON string as style.'));
      }
      $json_result = json_decode($json_style);
      if ($json_result === NULL) {
        $form_state->setErrorByName($prefix . 'google_map_settings][style', $this->t('Decoding style JSON failed. Error: %error.', ['%error' => json_last_error()]));
      }
      elseif (!is_array($json_result)) {
        $form_state->setErrorByName($prefix . 'google_map_settings][style', $this->t('Decoded style JSON is not an array.'));
      }
    }
  }

}
