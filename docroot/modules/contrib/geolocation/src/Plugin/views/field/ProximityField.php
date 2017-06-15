<?php

namespace Drupal\geolocation\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\geolocation\GeolocationCore;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler for geolocaiton field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("geolocation_field_proximity")
 */
class ProximityField extends NumericField implements ContainerFactoryPluginInterface {

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
  protected function defineOptions() {
    // Add source, lat, lng and filter.
    return [
      'proximity_source' => ['default' => 'direct_input'],
      'proximity_lat' => ['default' => ''],
      'proximity_lng' => ['default' => ''],
      'proximity_units' => ['default' => 'km'],
      'proximity_filter' => ['default' => ''],
      'proximity_argument' => ['default' => ''],
      'entity_id_argument' => ['default' => ''],
      'boundary_filter' => ['default' => ''],
    ] + parent::defineOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // Add the proximity field group.
    $form['proximity_group'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Proximity Settings'),
    ];

    $form['proximity_source'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the source type.'),
      '#description' => $this->t('To calculate proximity we need a starting point to compare the field value to. Select where to get the start location.'),
      '#default_value' => $this->options['proximity_source'],
      '#fieldset' => 'proximity_group',
      '#options' => [
        'direct_input' => $this->t('Static Values'),
      ],
    ];

    /*
     * Direct input form elements.
     */
    $form['proximity_lat'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Latitude'),
      '#empty_value' => '',
      '#default_value' => $this->options['proximity_lat'],
      '#maxlength' => 255,
      '#fieldset' => 'proximity_group',
      '#states' => [
        'visible' => [
          'select[name="options[proximity_source]"]' => ['value' => 'direct_input'],
        ],
      ],
    ];
    $form['proximity_lng'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Longitude'),
      '#empty_value' => '',
      '#default_value' => $this->options['proximity_lng'],
      '#maxlength' => 255,
      '#fieldset' => 'proximity_group',
      '#states' => [
        'visible' => [
          'select[name="options[proximity_source]"]' => ['value' => 'direct_input'],
        ],
      ],
    ];
    $form['proximity_units'] = [
      '#type' => 'select',
      '#title' => $this->t('Units'),
      '#default_value' => !empty($this->options['proximity_units']) ? $this->options['proximity_units'] : '',
      '#weight' => 40,
      '#fieldset' => 'proximity_group',
      '#options' => [
        'mile' => $this->t('Miles'),
        'km' => $this->t('Kilometers'),
      ],
      '#states' => [
        'visible' => [
          [
            ['select[name="options[proximity_source]"]' => ['value' => 'direct_input']],
            'or',
            ['select[name="options[proximity_source]"]' => ['value' => 'boundary_filter']],
            'or',
            ['select[name="options[proximity_source]"]' => ['value' => 'entity_id_argument']],
          ],
        ],
      ],
    ];

    /*
     * Available proximity filters form elements.
     */
    $proximity_filters = [];

    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    foreach ($this->displayHandler->getHandlers('filter') as $delta => $filter) {
      if ($filter->pluginId === 'geolocation_filter_proximity') {
        $proximity_filters[$delta] = $filter->adminLabel();
      }
    }

    if (!empty($proximity_filters)) {
      $form['proximity_filter'] = [
        '#type' => 'select',
        '#title' => $this->t('Select filter.'),
        '#description' => $this->t('Select the filter to use as the starting point for calculating proximity.'),
        '#options' => $proximity_filters,
        '#default_value' => $this->options['proximity_filter'],
        '#fieldset' => 'proximity_group',
        '#states' => [
          'visible' => [
            'select[name="options[proximity_source]"]' => ['value' => 'filter'],
          ],
        ],
      ];

      $form['proximity_source']['#options']['filter'] = $this->t('Proximity Filter');
    }

    /*
     * Proximity contextual filter form elements.
     */
    $proximity_arguments = [];

    /** @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument */
    foreach ($this->displayHandler->getHandlers('argument') as $delta => $argument) {
      if ($argument->getPluginId() === 'geolocation_argument_proximity') {
        $proximity_arguments[$delta] = $argument->adminLabel();
      }
    }

    if (!empty($proximity_arguments)) {
      $form['proximity_argument'] = [
        '#type' => 'select',
        '#title' => $this->t('Select contextual filter (argument).'),
        '#description' => $this->t('Select the contextual filter (argument) to use as the starting point for calculating proximity.'),
        '#options' => $proximity_arguments,
        '#default_value' => $this->options['proximity_argument'],
        '#fieldset' => 'proximity_group',
        '#states' => [
          'visible' => [
            'select[name="options[proximity_source]"]' => ['value' => 'argument'],
          ],
        ],
      ];

      $form['proximity_source']['#options']['argument'] = $this->t('Proximity Contextual Filter');
    }

    /*
     * Available boundary filters form elements.
     */
    $boundary_filters = [];

    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    foreach ($this->displayHandler->getHandlers('filter') as $delta => $filter) {
      if ($filter->pluginId === 'geolocation_filter_boundary') {
        $boundary_filters[$delta] = $filter->adminLabel();
      }
    }

    if (!empty($boundary_filters)) {
      $form['boundary_filter'] = [
        '#type' => 'select',
        '#title' => $this->t('Select filter.'),
        '#description' => $this->t('Select the boundary filter to use as the starting point for calculating proximity.'),
        '#options' => $boundary_filters,
        '#default_value' => $this->options['boundary_filter'],
        '#fieldset' => 'proximity_group',
        '#states' => [
          'visible' => [
            'select[name="options[proximity_source]"]' => ['value' => 'boundary_filter'],
          ],
        ],
      ];

      $form['proximity_source']['#options']['boundary_filter'] = $this->t('Boundary Filter');
    }

    /*
     * Entity ID contextual filter form elements.
     */
    $entity_id_arguments = [];

    /** @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument */
    foreach ($this->displayHandler->getHandlers('argument') as $delta => $argument) {
      $entity_id_arguments[$delta] = $argument->adminLabel();
    }

    $entity_type_label = \Drupal::entityTypeManager()->getDefinition($this->getEntityType())->getLabel();
    if (!empty($entity_id_arguments)) {
      $form['entity_id_argument'] = [
        '#type' => 'select',
        '#title' => $this->t('Select a contextual filter returning the @entity_type ID to base proximity on.', ['@entity_type' => $entity_type_label]),
        '#description' => $this->t(
          'The value of the @field_name field of this @entity_type will be used as center for distance values.',
          [
            '@entity_type' => $entity_type_label,
            '@field_name' => $this->field,
          ]
        ),
        '#options' => $entity_id_arguments,
        '#default_value' => $this->options['entity_id_argument'],
        '#fieldset' => 'proximity_group',
        '#states' => [
          'visible' => [
            'select[name="options[proximity_source]"]' => ['value' => 'entity_id_argument'],
          ],
        ],
      ];

      $form['proximity_source']['#options']['entity_id_argument'] = $this->t('Entity ID Contextual Filter');
    }

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;
    switch ($this->options['proximity_source']) {
      case 'filter':
        /** @var \Drupal\geolocation\Plugin\views\filter\ProximityFilter $filter */
        $filter = $this->view->filter[$this->options['proximity_filter']];
        $latitude = $filter->getLatitudeValue();
        $longitude = $filter->getLongitudeValue();
        $units = $filter->getProximityUnit();
        break;

      case 'boundary_filter':
        $filter = $this->view->filter[$this->options['boundary_filter']];

        // See documentation at
        // http://tubalmartin.github.io/spherical-geometry-php/#LatLngBounds
        $latitude = ($filter->value['lat_south_west'] + $filter->value['lat_north_east']) / 2;
        $longitude = ($filter->value['lng_south_west'] + $filter->value['lng_north_east']) / 2;
        if ($filter->value['lng_south_west'] > $filter->value['lng_north_east']) {
          $longitude = $longitude == 0 ? 180 : fmod((fmod((($longitude + 180) - -180), 360) + 360), 360) + -180;
        }
        $units = $this->options['proximity_units'];
        break;

      case 'argument':
        /** @var \Drupal\geolocation\Plugin\views\argument\ProximityArgument $argument */
        $argument = $this->view->argument[$this->options['proximity_argument']];
        $values = $argument->getParsedReferenceLocation();
        $latitude = $values['lat'];
        $longitude = $values['lng'];
        $units = $values['units'];
        break;

      case 'entity_id_argument':
        $argument = $this->view->argument[$this->options['entity_id_argument']];
        if (empty($argument)) {
          return;
        }
        $entity_id = $argument->getValue();
        if (!ctype_digit($entity_id)) {
          return;
        }
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = \Drupal::entityTypeManager()->getStorage($this->getEntityType())->load($entity_id);
        if (!$entity->hasField($this->realField)) {
          return;
        }
        $field = $entity->get($this->realField);
        if (empty($field)) {
          return;
        }
        $values = $field->getValue();
        if (empty($values)) {
          return;
        }
        $values = reset($values);
        $latitude = $values['lat'];
        $longitude = $values['lng'];
        $units = $this->options['proximity_units'];
        break;

      default:
        $latitude = $this->options['proximity_lat'];
        $longitude = $this->options['proximity_lng'];
        $units = $this->options['proximity_units'];
    }

    if (
      !is_numeric($latitude)
      || !is_numeric($longitude)
    ) {
      return;
    }

    // Get the earth radius from the units.
    $earth_radius = $units === 'mile' ? GeolocationCore::EARTH_RADIUS_MILE : GeolocationCore::EARTH_RADIUS_KM;

    // Build the query expression.
    $expression = $this->geolocationCore->getProximityQueryFragment($this->ensureMyTable(), $this->realField, $latitude, $longitude, $earth_radius);

    // Get a placeholder for this query and save the field_alias for it.
    // Remove the initial ':' from the placeholder and avoid collision with
    // original field name.
    $this->field_alias = $query->addField(NULL, $expression, substr($this->placeholder(), 1));
  }

}
