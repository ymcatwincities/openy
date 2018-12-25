<?php

namespace Drupal\geolocation\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\geolocation\GeolocationCore;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter handler for search keywords.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("geolocation_filter_boundary")
 */
class BoundaryFilter extends FilterPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public $no_operator = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $alwaysMultiple = TRUE;

  /**
   * The GeolocationCore object.
   *
   * @var \Drupal\geolocation\GeolocationCore
   */
  protected $geolocationCore;

  /**
   * Constructs a Handler object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\geolocation\GeolocationCore $geolocation_core
   *   The GeolocationCore object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GeolocationCore $geolocation_core) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->geolocationCore = $geolocation_core;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Make phpcs happy.
    /** @var \Drupal\geolocation\GeolocationCore $geolocation_core */
    $geolocation_core = $container->get('geolocation.core');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $geolocation_core
    );
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    return $this->t("Boundary filter");
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['expose']['contains']['input_by_geocoding_widget'] = ['default' => FALSE];
    $options['expose']['contains']['geocoder_plugin_settings'] = [
      'default' => [
        'plugin_id' => '',
        'settings' => [],
      ],
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    $geocoder_definitions = $this->geolocationCore->getGeocoderManager()->getBoundaryCapableGeocoders();

    if ($geocoder_definitions) {
      $form['expose']['input_by_geocoding_widget'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use geocoding widget to retrieve boundary values'),
        '#default_value' => $this->options['expose']['input_by_geocoding_widget'],
      ];

      $geocoder_options = [];
      foreach ($geocoder_definitions as $id => $definition) {
        $geocoder_options[$id] = $definition['name'];
      }

      $form['expose']['geocoder_plugin_settings'] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            'input[name="options[expose][input_by_geocoding_widget]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $geocoder_container = &$form['expose']['geocoder_plugin_settings'];

      $geocoder_container['plugin_id'] = [
        '#type' => 'select',
        '#options' => $geocoder_options,
        '#title' => $this->t('Geocoder plugin'),
        '#default_value' => $this->options['expose']['geocoder_plugin_settings']['plugin_id'],
        '#ajax' => [
          'callback' => [get_class($this->geolocationCore->getGeocoderManager()), 'addGeocoderSettingsFormAjax'],
          'wrapper' => 'geocoder-plugin-settings',
          'effect' => 'fade',
        ],
      ];

      if (!empty($this->options['expose']['geocoder_plugin_settings']['plugin_id'])) {
        $geocoder_plugin = $this->geolocationCore->getGeocoderManager()
          ->getGeocoder(
            $this->options['expose']['geocoder_plugin_settings']['plugin_id'],
            $this->options['expose']['geocoder_plugin_settings']['settings']
          );
      }
      elseif (current(array_keys($geocoder_options))) {
        $geocoder_plugin = $this->geolocationCore->getGeocoderManager()->getGeocoder(current(array_keys($geocoder_options)));
      }

      if (!empty($geocoder_plugin)) {
        $geocoder_settings_form = $geocoder_plugin->getOptionsForm();
        if ($geocoder_settings_form) {
          $geocoder_container['settings'] = $geocoder_settings_form;
        }
      }

      if (empty($geocoder_container['settings'])) {
        $geocoder_container['settings'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $this->t("No settings available."),
        ];
      }

      $geocoder_container['settings'] = array_replace_recursive($geocoder_container['settings'], [
        '#flatten' => TRUE,
        '#prefix' => '<div id="geocoder-plugin-settings">',
        '#suffix' => '</div>',
      ]);
    }

    parent::buildExposeForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    $identifier = $this->options['expose']['identifier'];

    if (
      $this->options['expose']['input_by_geocoding_widget']
      && !empty($form[$identifier])
      && !empty($this->options['expose']['geocoder_plugin_settings'])
    ) {

      $geocoder_configuration = $this->options['expose']['geocoder_plugin_settings']['settings'];
      $geocoder_configuration['label'] = $this->options['expose']['label'];

      /** @var \Drupal\geolocation\GeocoderInterface $geocoder_plugin */
      $geocoder_plugin = $this->geolocationCore->getGeocoderManager()->getGeocoder(
        $this->options['expose']['geocoder_plugin_settings']['plugin_id'],
        $geocoder_configuration
      );

      if (empty($geocoder_plugin)) {
        return;
      }

      $form[$identifier]['lat_north_east']['#type'] = 'hidden';
      $form[$identifier]['lng_north_east']['#type'] = 'hidden';
      $form[$identifier]['lat_south_west']['#type'] = 'hidden';
      $form[$identifier]['lng_south_west']['#type'] = 'hidden';

      $geocoder_plugin->formAttachGeocoder($form[$this->options['expose']['identifier']], $identifier);

      $form = array_merge_recursive($form, [
        '#attached' => [
          'library' => [
            'geolocation/geolocation.views.filter.geocoder',
          ],
          'drupalSettings' => [
            'geolocation' => [
              'geocoder' => [
                'viewsFilterGeocoder' => [
                  $identifier => [
                    'type' => 'boundary',
                  ],
                ],
              ],
            ],
          ],
        ],
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposed(&$form, FormStateInterface $form_state) {
    parent::validateExposed($form, $form_state);

    if (
      $this->options['expose']['input_by_geocoding_widget']
      && !empty($this->options['expose']['geocoder_plugin_settings']['plugin_id'])
    ) {
      $geocoder_configuration = $this->options['expose']['geocoder_plugin_settings']['settings'];
      /** @var \Drupal\geolocation\GeocoderInterface $geocoder_plugin */
      $geocoder_plugin = $this->geolocationCore->getGeocoderManager()
        ->getGeocoder(
          $this->options['expose']['geocoder_plugin_settings']['plugin_id'],
          $geocoder_configuration
        );

      if (!empty($geocoder_plugin)) {
        $geocoder_plugin->formvalidateInput($form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    $return_value = parent::acceptExposedInput($input);
    if (
      $this->options['expose']['input_by_geocoding_widget']
      && !empty($this->options['expose']['geocoder_plugin_settings']['plugin_id'])
    ) {
      $geocoder_configuration = $this->options['expose']['geocoder_plugin_settings']['settings'];
      /** @var \Drupal\geolocation\GeocoderInterface $geocoder_plugin */
      $geocoder_plugin = $this->geolocationCore->getGeocoderManager()->getGeocoder(
        $this->options['expose']['geocoder_plugin_settings']['plugin_id'],
        $geocoder_configuration
      );

      if (!empty($geocoder_plugin)) {
        $location_data = $geocoder_plugin->formProcessInput($input[$this->options['expose']['identifier']], $this->options['expose']['identifier']);

        // No location found at all.
        if (!$location_data) {
          $this->value = [];
          // Accept it anyway, to ensure empty result.
          return TRUE;
        }
        else {
          // Location geocoded server-side. Add to input for later processing.
          if (!empty($location_data['boundary'])) {
            $this->value = array_replace($input[$this->options['expose']['identifier']], $location_data['boundary']);
          }
          // Location geocoded client-side. Assign to handler value.
          else {
            $this->value = $input[$this->options['expose']['identifier']];
          }
        }
      }
    }
    return $return_value;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {

    parent::valueForm($form, $form_state);

    $form['value']['#tree'] = TRUE;
    $value_element = &$form['value'];

    // Add the Latitude and Longitude elements.
    $value_element += [
      'lat_north_east' => [
        '#type' => 'textfield',
        '#title' => $this->t('North East Boundary - Latitude'),
        '#default_value' => !empty($this->value['lat_north_east']) ? $this->value['lat_north_east'] : '',
        '#weight' => 10,
      ],
      'lng_north_east' => [
        '#type' => 'textfield',
        '#title' => $this->t('North East Boundary - Longitude'),
        '#default_value' => !empty($this->value['lng_north_east']) ? $this->value['lng_north_east'] : '',
        '#weight' => 20,
      ],
      'lat_south_west' => [
        '#type' => 'textfield',
        '#title' => $this->t('South West Boundary - Latitude'),
        '#default_value' => !empty($this->value['lat_south_west']) ? $this->value['lat_south_west'] : '',
        '#weight' => 30,
      ],
      'lng_south_west' => [
        '#type' => 'textfield',
        '#title' => $this->t('South West Boundary - Longitude'),
        '#default_value' => !empty($this->value['lng_south_west']) ? $this->value['lng_south_west'] : '',
        '#weight' => 40,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!($this->query instanceof Sql)) {
      return;
    }

    if (empty($this->value)) {
      return;
    }

    // Get the field alias.
    $lat_north_east = $this->value['lat_north_east'];
    $lng_north_east = $this->value['lng_north_east'];
    $lat_south_west = $this->value['lat_south_west'];
    $lng_south_west = $this->value['lng_south_west'];

    if (
      !is_numeric($lat_north_east)
      || !is_numeric($lng_north_east)
      || !is_numeric($lat_south_west)
      || !is_numeric($lng_south_west)
    ) {
      return;
    }

    $this->query->addWhereExpression(
      $this->options['group'],
      $this->geolocationCore->getBoundaryQueryFragment($this->ensureMyTable(), $this->realField, $lat_north_east, $lng_north_east, $lat_south_west, $lng_south_west)
    );
  }

}
