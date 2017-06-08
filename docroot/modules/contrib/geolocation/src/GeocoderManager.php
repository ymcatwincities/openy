<?php

namespace Drupal\geolocation;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Search plugin manager.
 */
class GeocoderManager extends DefaultPluginManager {

  /**
   * Constructs an GeocoderManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/geolocation/Geocoder', $namespaces, $module_handler, 'Drupal\geolocation\GeocoderInterface', 'Drupal\geolocation\Annotation\Geocoder');
    $this->alterInfo('geolocation_geocoder_info');
    $this->setCacheBackend($cache_backend, 'geolocation_geocoder');
  }

  /**
   * Return Geocoder by ID.
   *
   * @param string $id
   *   Geocoder ID.
   * @param array $configuration
   *   Configuration.
   *
   * @return \Drupal\geolocation\GeocoderInterface|false
   *   Geocoder instance.
   */
  public function getGeocoder($id, $configuration = []) {
    $definitions = $this->getDefinitions();
    if (empty($definitions[$id])) {
      return FALSE;
    }
    try {
      /** @var \Drupal\geolocation\GeocoderInterface $instance */
      $instance = $this->createInstance($id, $configuration);
      if ($instance) {
        return $instance;
      }
    }
    catch (\Exception $e) {
      return FALSE;
    }
    return FALSE;
  }

  /**
   * Get location capable geocoder definitions.
   *
   * @return array
   *   List of location capable geocoder definitions.
   */
  public function getLocationCapableGeocoders() {
    $location_capable_geocoders = [];
    foreach ($this->getDefinitions() as $id => $definition) {
      if (!empty($definition['locationCapable'])) {
        $location_capable_geocoders[$id] = $definition;
      }
    }
    return $location_capable_geocoders;
  }

  /**
   * Get boundary capable geocoder definitions.
   *
   * @return array
   *   List of boundary capable geocoder definitions.
   */
  public function getBoundaryCapableGeocoders() {
    $boundary_capable_geocoders = [];
    foreach ($this->getDefinitions() as $id => $definition) {
      if (!empty($definition['boundaryCapable'])) {
        $boundary_capable_geocoders[$id] = $definition;
      }
    }
    return $boundary_capable_geocoders;
  }

  /**
   * Return settings array for geocoder after select change.
   *
   * @param array $form
   *   Form.
   * @param FormStateInterface $form_state
   *   Current From State.
   *
   * @return array|false
   *   Settings form.
   */
  public static function addGeocoderSettingsFormAjax($form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement()['#parents'];
    array_pop($triggering_element);

    $target = $triggering_element;
    $target[]  = 'plugin_id';
    $plugin_id = $form_state->getValue($target, '');
    $target = $triggering_element;
    $target[] = 'settings';
    $geocoder_settings = $form_state->getValue($target, []);

    /** @var \Drupal\geolocation\GeocoderInterface $geocoder_plugin */
    $geocoder_plugin = \Drupal::service('plugin.manager.geolocation.geocoder')->getGeocoder($plugin_id, $geocoder_settings);

    if (empty($geocoder_plugin)) {
      $return = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => t('Non-existing geocoder plugin requested.'),
      ];
    }
    else {
      $geocoder_settings_form = $geocoder_plugin->getOptionsForm();

      if (!empty($geocoder_settings_form)) {
        $return = $geocoder_settings_form;
      }
      else {
        $return = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => t("No settings available."),
        ];
      }
    }

    $return = array_merge_recursive($return, [
      '#prefix' => '<div id="geocoder-plugin-settings">',
      '#suffix' => '</div>',
    ]);

    return $return;
  }

}
