<?php

namespace Drupal\geolocation\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geolocation\GoogleMapsDisplayTrait;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\geolocation\GeolocationCore;

/**
 * Plugin implementation of the 'geolocation_googlegeocoder' widget.
 *
 * @FieldWidget(
 *   id = "geolocation_googlegeocoder",
 *   label = @Translation("Geolocation Google Maps API - Geocoding and Map"),
 *   field_types = {
 *     "geolocation"
 *   }
 * )
 */
class GeolocationGooglegeocoderWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  use GoogleMapsDisplayTrait;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The GeolocationCore object.
   *
   * @var \Drupal\geolocation\GeolocationCore
   */
  protected $geolocationCore;

  /**
   * Constructs a WidgetBase object.
   *
   * @param array $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\geolocation\GeolocationCore $geolocation_core
   *   The GeolocationCore object.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityFieldManagerInterface $entity_field_manager, GeolocationCore $geolocation_core) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityFieldManager = $entity_field_manager;
    $this->geolocationCore = $geolocation_core;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = $container->get('entity_field.manager');

    /** @var \Drupal\geolocation\GeolocationCore $geocoder_core */
    $geocoder_core = $container->get('geolocation.core');

    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $entity_field_manager,
      $geocoder_core
    );
  }

  /**
   * {@inheritdoc}
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    foreach ($violations as $offset => $violation) {
      if ($violation->getMessageTemplate() == 'This value should not be null.') {
        $form_state->setErrorByName($items->getName(), $this->t('No location has been selected yet for required field %field.', ['%field' => $items->getFieldDefinition()->getLabel()]));
      }
    }
    parent::flagErrors($items, $violations, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'populate_address_field' => FALSE,
      'target_address_field' => NULL,
      'default_longitude' => NULL,
      'default_latitude' => NULL,
      'auto_client_location' => FALSE,
      'auto_client_location_marker' => FALSE,
      'allow_override_map_settings' => FALSE,
    ];
    $settings += parent::defaultSettings();
    $settings += self::getGoogleMapDefaultSettings();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $element = [];

    $element['default_longitude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Longitude'),
      '#description' => $this->t('The default center point, before a value is set.'),
      '#default_value' => $settings['default_longitude'],
    ];

    $element['default_latitude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Latitude'),
      '#description' => $this->t('The default center point, before a value is set.'),
      '#default_value' => $settings['default_latitude'],
    ];

    $element['auto_client_location'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically use client location, when no value is set'),
      '#default_value' => $settings['auto_client_location'],
    ];
    $element['auto_client_location_marker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically set marker to client location as well'),
      '#default_value' => $settings['auto_client_location_marker'],
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][auto_client_location]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions */
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($this->fieldDefinition->getTargetEntityTypeId(), $this->fieldDefinition->getTargetBundle());

    $address_fields = [];
    foreach ($field_definitions as $field_definition) {
      if ($field_definition->getType() == 'address' && $field_definition->getFieldStorageDefinition()->getCardinality() == 1) {
        $address_fields[$field_definition->getName()] = $field_definition->getLabel();
      }
    }

    if (!empty($address_fields)) {
      $element['populate_address_field'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Store retrieved address data in address field'),
        '#default_value' => $settings['populate_address_field'],
      ];

      $element['target_address_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Select target field to append address data.'),
        '#description' => $this->t('Only fields of type "address" with a cardinality of 1 are available.'),
        '#options' => $address_fields,
        '#default_value' => $settings['target_address_field'],
        '#states' => [
          'visible' => [
            ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][populate_address_field]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    $element['allow_override_map_settings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow override the map settings when create/edit an content.'),
      '#default_value' => $settings['allow_override_map_settings'],
    ];
    $element += $this->getGoogleMapsSettingsForm($settings);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    $summary[] = $this->t('Default center longitude @default_longitude and latitude @default_latitude', [
      '@default_longitude' => $settings['default_longitude'],
      '@default_latitude' => $settings['default_latitude'],
    ]);

    if (!empty($settings['auto_client_location'])) {
      $summary[] = $this->t('Will use client location automatically by default');
      if (!empty($settings['auto_client_location_marker'])) {
        $summary[] = $this->t('Will set client location marker automatically by default');
      }
    }

    if (!empty($settings['populate_address_field'])) {
      $summary[] = $this->t('Geocoded address will be stored in @field', ['@field' => $settings['target_address_field']]);
    }

    if (!empty($settings['allow_override_map_settings'])) {
      $summary[] = $this->t('Users will be allowed to override the map settings for each content.');
    }

    $summary = array_merge($summary, $this->getGoogleMapsSettingsSummary($settings));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = $this->getGoogleMapsSettings($this->getSettings()) + $this->getSettings();

    // Get this field name and parent.
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];
    // Get the field state.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);

    // Create a unique canvas id for each map of each geolocation field
    // instance.
    $field_id = preg_replace('/[^a-zA-Z0-9\-]/', '-', $this->fieldDefinition->getName());
    $canvas_id = !empty($field_state['canvas_ids'][$delta])
      ? $field_state['canvas_ids'][$delta]
      : uniqid("map-canvas-{$field_id}-");

    // Add the canvas id for this field.
    $field_state['canvas_ids'] = isset($field_state['canvas_ids'])
      ? $field_state['canvas_ids'] + [$delta => $canvas_id]
      : [$delta => $canvas_id];

    // Save the field state for this field.
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    // Get the geolocation value for this element.
    $lat = $items[$delta]->lat;
    $lng = $items[$delta]->lng;

    $default_field_values = [
      'lat' => '',
      'lng' => '',
    ];

    $default_map_values = [
      'lat' => $settings['default_latitude'],
      'lng' => $settings['default_longitude'],
    ];

    if (!empty($this->fieldDefinition->getDefaultValueLiteral()[0])) {
      $default_field_values = [
        'lat' => $this->fieldDefinition->getDefaultValueLiteral()[0]['lat'],
        'lng' => $this->fieldDefinition->getDefaultValueLiteral()[0]['lng'],
      ];
    }

    if (!empty($lat) && !empty($lng)) {
      $default_field_values = [
        'lat' => $lat,
        'lng' => $lng,
      ];

      $default_map_values = [
        'lat' => $lat,
        'lng' => $lng,
      ];
    }

    // Hidden lat,lng input fields.
    $element['lat'] = [
      '#type' => 'hidden',
      '#default_value' => $default_field_values['lat'],
      '#attributes' => ['class' => ['geolocation-hidden-lat']],
    ];
    $element['lng'] = [
      '#type' => 'hidden',
      '#default_value' => $default_field_values['lng'],
      '#attributes' => ['class' => ['geolocation-hidden-lng']],
    ];

    $element['controls'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'geocoder-controls-wrapper-' . $canvas_id,
        'class' => [
          'geocode-controls-wrapper',
        ],
      ],
    ];

    $element['controls']['location'] = [
      '#type' => 'textfield',
      '#placeholder' => t('Enter a location'),
      '#attributes' => [
        'class' => [
          'location',
          'form-autocomplete',
        ],
      ],
      '#theme_wrappers' => [],
    ];

    $element['controls']['search'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#attributes' => [
        'class' => [
          'search',
        ],
        'title' => t('Search'),
      ],
    ];

    $element['controls']['locate'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#attributes' => [
        'class' => [
          'locate',
        ],
        'style' => 'display: none;',
        'title' => t('Locate'),
      ],
    ];

    $element['controls']['clear'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#attributes' => [
        'class' => [
          'clear',
          'disabled'
        ],
        'title' => t('Clear'),
      ],
    ];

    // Add the map container.
    $element['map_canvas'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => $canvas_id,
        'class' => ['geolocation-map-canvas'],
      ],
      '#attached' => [
        'library' => ['geolocation/geolocation.widgets.googlegeocoder'],
        'drupalSettings' => [
          'geolocation' => [
            'widgetSettings' => [
              $canvas_id => [
                'autoClientLocation' => $settings['auto_client_location'] ? TRUE : FALSE,
                'autoClientLocationMarker' => $settings['auto_client_location_marker'] ? TRUE : FALSE,
              ],
            ],
            'widgetMaps' => [
              $canvas_id => [
                'id' => $canvas_id,
                'lat' => (float) $default_map_values['lat'],
                'lng' => (float) $default_map_values['lng'],
                'settings' => $settings,
              ],
            ],
            'google_map_url' => $this->getGoogleMapsApiUrl(),
          ],
        ],
      ],
    ];

    if ($settings['populate_address_field']) {
      $element['map_canvas']['#attached']['drupalSettings']['geolocation']['widgetSettings'][$canvas_id]['addressFieldTarget'] = $settings['target_address_field'];
    }

    if ($settings['allow_override_map_settings']) {
      if (!empty($items[$delta]->data['google_map_settings'])) {
        $map_settings = [
          'google_map_settings' => $items[$delta]->data['google_map_settings'],
        ];
      }
      else {
        $map_settings = $settings;
      }
      $element += $this->getGoogleMapsSettingsForm($map_settings);
    }

    // Wrap the whole form in a container.
    $element += [
      '#type' => 'fieldset',
      '#title' => $element['#title'],
      '#attributes' => [
        'class' => ['canvas-' . $canvas_id],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);

    if ($this->settings['allow_override_map_settings']) {
      foreach ($values as $delta => $item_values) {
        if (!empty($item_values['google_map_settings'])) {
          $values[$delta]['data']['google_map_settings'] = $item_values['google_map_settings'];
        }
      }
    }

    return $values;
  }

}
