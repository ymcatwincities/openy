<?php

namespace Drupal\openy_map\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\openy_map\OpenyMapManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin settings Form for openy_map form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The OpenY Map manager.
   *
   * @var \Drupal\openy_map\OpenyMapManager
   */
  protected $openyMapManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\openy_map\OpenyMapManager $openy_map_manager
   *   The OpenY Map manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, OpenyMapManager $openy_map_manager) {
    parent::__construct($config_factory);
    $this->openyMapManager = $openy_map_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('openy_map.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_map_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openy_map.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_map.settings');
    $form_state->setCached(FALSE);

    $form['map_engine_title'] = [
      '#markup' => '<h2>' . $this->t('Map provider') . '</h2>',
    ];
    $form['map_engine'] = [
      '#type' => 'radios',
      '#options' => [
        'leaflet' => $this->t('Leaflet'),
        'gmaps' => $this->t('Google Maps'),
      ],
      '#default_value' => !empty($config->get('map_engine')) ? $config->get('map_engine') : 'leaflet',
      '#required' => TRUE,
    ];

    $form['gmaps_keys'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Google Maps Configuration'),
      '#states' => [
        'visible' => [
          ':input[name="map_engine"]' => ['value' => 'gmaps'],
        ],
      ],
    ];

    $form['gmaps_keys']['info'] = [
      '#type' => 'inline_template',
      '#template' => '<p>Please find Google Maps keys here {{ link }}</p>',
      '#context' => [
        'link' => Link::createFromRoute('Geolocation settings', 'geolocation.settings'),
      ],
    ];

    $form['leaflet'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Leaflet Configuration'),
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="map_engine"]' => ['value' => 'leaflet'],
        ],
      ],
    ];

    $form['leaflet']['location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default search location'),
      '#description' => $this->t('When search performs the results of the set location are prioritized, e.g. %ex1 or %ex2', [
        '%ex1' => '"Houston, TX"',
        '%ex2' => '"CA, United States of America"',
      ]),
      '#default_value' => _openy_map_get_default_location(),
    ];

    $options = ['Wikimedia', 'Esri.WorldStreetMap', 'Esri.NatGeoWorldMap', 'OpenStreetMap.Mapnik'];
    $options = array_combine($options, $options);
    array_walk($options, function (&$value) {
      $link = Link::fromTextAndUrl('preview',
        Url::fromUri('https://leaflet-extras.github.io/leaflet-providers/preview/', [
          'attributes' => ['target' => '_blank'],
          'fragment' => 'filter=' . $value,
        ]))->toString();
      $value .= ' <small>(' . $link . ')</small>';
    });
    $form['leaflet']['base_layer'] = [
      '#type' => 'radios',
      '#title' => $this->t('Base layer'),
      '#options' => $options,
      '#default_value' => !empty($config->get('leaflet.base_layer')) ? $config->get('leaflet.base_layer') : 'Wikimedia',
    ];

    $form['title'] = [
      '#markup' => '<h2>' . $this->t('Location list page settings') . '</h2>',
    ];
    $form['help'] = [
      '#type' => 'details',
      '#title' => $this->t('How to add new content type to Location list page'),
      '#open' => FALSE,
      'help_text' => [
        '#markup' => '<p>' . $this->t('1. You have to reuse Coordinates Geolocation field <b>field_location_coordinates</b>.') . '</p>' .
        '<p>' . $this->t("2. It's highly recommended to reuse Address <b>field_location_address</b> and Phone <b>field_location_phone</b> fields. They will be shown on map and on locations list teasers.") . '</p>' .
        '<p>' . $this->t('3. To use Amenities search feature you have to reuse Amenities field <b>field_location_amenities</b>') . '</p>' .
        '<p>' . $this->t('4. You have to check fields display settings with @branch_display_link', [
          '@branch_display_link' => Link::fromTextAndUrl('Branch Teaser display',
            Url::fromUserInput('/admin/structure/types/manage/branch/display/teaser', ['attributes' => ['target' => '_blank']]))->toString()
          ]) . '</p>',
      ],
    ];

    $nodeTypes = $this->openyMapManager->getLocationNodeTypes();
    if (!empty($nodeTypes)) {
      // Render icon files from Location Finder module and default theme.
      $themeConfig = $this->config('system.theme');
      $themePath = drupal_get_path('theme', $themeConfig->get('default')) . '/img/locations_icons';
      $fileOptions = $themeFiles = [];
      if (is_dir($themePath)) {
        $themeFiles = scandir($themePath);
        foreach ($themeFiles as $themeFile) {
          $path = $themePath . '/' . $themeFile;
          if (is_dir($path)) {
            continue;
          }
          $path = file_create_url($path);
          $fileOptions[$path] = '<img src="' . $path . '" />';
        }
      }
      $openYMapPath = drupal_get_path('module', 'openy_map') . '/img';
      foreach (scandir($openYMapPath) as $imgFile) {
        $path = $openYMapPath . '/' . $imgFile;
        if (!in_array($imgFile, $themeFiles) && !is_dir($path)) {
          $path = file_create_url($path);
          $fileOptions[$path] = '<img src="' . $path . '" />';
        }
      }

      /** @var \Drupal\node\Entity\NodeType $nodeType */
      foreach ($nodeTypes as $nodeType) {
        $id = $nodeType->id();
        $label = $nodeType->label();

        $form[$id] = [
          '#type' => 'details',
          '#title' => $this->t('@branch content type', ['@branch' => $label]),
          '#open' => TRUE,
        ];
        $form[$id][$id . '_label'] = [
          '#type' => 'textfield',
          '#title' => t('Locations filters label to show on Locations filters under the map:'),
          '#default_value' => !empty($config->get('type_labels')[$id]) ? $config->get('type_labels')[$id] : $label,
        ];
        $form[$id][$id . '_block_label'] = [
          '#type' => 'textfield',
          '#title' => t('Location block header to show on Locations list:'),
          '#default_value' => !empty($config->get('block_labels')[$id]) ? $config->get('block_labels')[$id] : $label,
        ];
        $form[$id][$id . '_active'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable on map and list on Locations page', ['@branch' => $label]),
          '#default_value' => !empty($config->get('active_types')[$id]),
        ];
        $form[$id][$id . '_default'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Set as default filter values for Locations map', ['@branch' => $label]),
          '#default_value' => !empty($config->get('default_tags')[$id]),
        ];
        $form[$id][$id . '_icon'] = [
          '#prefix' => '<div class="container-inline">',
          '#type' => 'radios',
          '#title' => $this->t('Locations Map icon'),
          '#default_value' => !empty($config->get('type_icons')[$id]) ? $config->get('type_icons')[$id] : array_keys($fileOptions)[0],
          '#options' => $fileOptions,
          '#description' => $this->t('Choose content type map icon. To redefine icons add file in <b>{default_theme}/img/locations_icons</b> directory in active default theme'),
          '#required' => TRUE,
          '#multiple' => FALSE,
          '#suffix' => '</div>',
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $config \Drupal\Core\Config\Config */
    $config = $this->config('openy_map.settings');

    $nodeTypes = $this->openyMapManager->getLocationNodeTypes();
    $default_tags = $active_types = $type_labels = $block_labels = $type_icons = [];
    /** @var \Drupal\node\Entity\NodeType $nodeType */
    foreach ($nodeTypes as $nodeType) {
      $id = $nodeType->id();
      $label = !empty($form_state->getValue($id . '_label')) ?
        $form_state->getValue($id . '_label') : $nodeType->label();

      $block_labels[$id] = !empty($form_state->getValue($id . '_block_label')) ? $form_state->getValue($id . '_block_label') : $label;
      $default_tags[$id] = !empty($form_state->getValue($id . '_default')) ? $label : '';
      $active_types[$id] = !empty($form_state->getValue($id . '_active')) ? $label : '';
      $type_labels[$id] = $label;
      $type_icons[$id] = $form_state->getValue($id . '_icon');
    }

    $config->set('map_engine', $form_state->getValue('map_engine'));
    $config->set('leaflet.location', $form_state->getValue('leaflet')['location']);
    $config->set('leaflet.base_layer', $form_state->getValue('leaflet')['base_layer']);
    $config->set('default_tags', $default_tags);
    $config->set('active_types', $active_types);
    $config->set('type_labels', $type_labels);
    $config->set('block_labels', $block_labels);
    $config->set('type_icons', $type_icons);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
