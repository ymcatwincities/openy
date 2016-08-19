<?php

namespace Drupal\gardengnome_player\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Gardengnome_player_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "gardengnome_player_field_formatter",
 *   label = @Translation("Gardengnome player field formatter"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class GardengnomePlayerFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->imageStyleStorage = $image_style_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity.manager')->getStorage('image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'autoplay' => 1,
      'display_style' => 'inline',
      'preview_style' => 'none',
      'popup_height' => 300,
      'popup_width' => 400,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $this->getSetting('autoplay'),
      '#description' => t('Start the player on page load. Popup displays always start immediately.'),
    ];

    $id = Html::getUniqueId('display-style');
    $form['display_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Display style'),
      '#description' => $this->t('Choose a display style. <em>Inline</em> will inherit the preview images size. </em><strong>Do not use <em>Inline autoplay</em> if many items are displayed on one page!</strong>'),
      '#options' => [
        'inline' => $this->t('Inline'),
        'popup' => $this->t('Popup'),
      ],
      '#default_value' => $this->getSetting('display_style'),
      '#id' => $id,
    ];

    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure Image Styles'),
      Url::fromRoute('entity.image_style.collection')
    );
    $description = [
      [
        '#markup' => t('Choose an image style for the preview image. Inline displays will inherit it\'s size. '),
      ],
      $description_link->toRenderable() + [
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ],
    ];
    $form['preview_style'] = [
      '#title' => $this->t('Preview style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('preview_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description,
    ];

    $form['popup_height'] = [
      '#title' => $this->t('Popup height'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('popup_height'),
      '#states' => [
        'visible' => [
          "#$id" => ['value' => 'popup'],
        ],
      ],
    ];
    $form['popup_width'] = [
      '#title' => $this->t('Popup width'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('popup_width'),
      '#states' => [
        'visible' => [
          "#$id" => ['value' => 'popup'],
        ],
      ],
    ];

    return $form + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // Implement settings summary.
    $summary = [];
    $summary[] = $this->t('Autoplay: %autoplay', [
      '%autoplay' => $this->t($this->getSetting('autoplay') ? 'yes' : 'no'),
    ]);
    $summary[] = $this->t('Display style: %display_style', [
      '%display_style' => $this->t($this->getSetting('display_style') ? $this->getSetting('display_style') : 'none'),
    ]);
    $summary[] = $this->t('Preview style: %preview_style', [
      '%preview_style' => $this->getSetting('preview_style') ? $this->getSetting('preview_style') : $this->t('none'),
    ]);
    if ($this->getSetting('display_style') == 'popup') {
      $summary[] = $this->t('Popup dimensions: %widthx%height', [
        '%width' => $this->getSetting('popup_width'),
        '%height' => $this->getSetting('popup_height'),
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }
    $elements['#attached']['library'] = [
      'gardengnome_player/gardengnome_player'
    ];

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $build = [];

    if (!$file = \Drupal::entityTypeManager()->getStorage('file')->load($item->target_id)) {
      return $build;
    }

    if (!$extracted = $this->extract($file)) {
      return $build;
    }

    $package_info = $this->getPackageInfo($extracted);
    $attributes_array = [];
    foreach ($package_info as $key => $value) {
      if ($value) {
        $attributes_array['data-' . $key] = $value;
      }
    }

    $attributes_array['class'] = ['gardengnome-player'];
    $attributes_array['data-autoplay'] = $this->getSetting('autoplay');
    $attributes_array['data-display'] = $this->getSetting('display_style');
    $attributes_array['data-package'] = file_create_url($extracted);
    $attributes_array['data-popup-height'] = $this->getSetting('popup_height');
    $attributes_array['data-popup-width'] = $this->getSetting('popup_width');
    $attributes = new Attribute($attributes_array);

    if ($package_info['flash'] !== 'false') {
      $build['#attached']['library'][] = 'gardengnome_player/swfobject.swfobjectjs';
    }

    $build['content'] = [
      '#type' => 'container',
      '#tag' => 'div',
      '#attributes' => $attributes,
    ];

    if (file_exists($extracted . '/preview.jpg')) {
      $build['content']['preview'] = [
        '#theme' => 'image',
        '#uri' => $extracted . '/preview.jpg',
        '#attributes' => [
          'class' => ['gardengnome-player-preview'],
        ],
      ];
      if ($this->getSetting('preview_style')) {
        $build['content']['preview']['#theme'] = 'image_style';
        $build['content']['preview']['#style_name'] = $this->getSetting('preview_style');
      }
    }
    else {
      $build['content']['preview'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Panorama block. Package %package. No preview available.', [
          '%package' => $file->getFilename(),
        ]),
        '#attributes' => [
          'style' => 'border:1px dashed grey;',
        ],
      ];
      $build['content']['#attributes']['data-autoplay'] = 'true';
    }

    return $build;
  }

  /**
   * Provides information about player type, options.
   */
  public function getPackageInfo($uri) {
    $path = \Drupal::service('file_system')->realpath($uri);
    $type = file_exists($path . '/object.xml') ? 'object2vr' : 'pano2vr';

    $assets = [
      'flash' => file_exists($path . '/' . $type . '_player.swf') ? 'true' : 'false',
      'html5' => file_exists($path . '/' . $type . '_player.js') ? 'true' : 'false',
      'skin' => file_exists($path . '/skin.js') ? 'true' : 'false',
      'type' => $type,
    ];

    return $assets;
  }

  /**
   * Extract provided archive.
   *
   * Extracts a Gardengnome package if necessary and returns the path or false
   * if something went terribly wrong.
   */
  public function extract(EntityInterface $file) {
    $extract_path = \Drupal::service('config.factory')
      ->get('gardengnome_player.settings')
      ->get('path');

    if (!is_dir($extract_path) && !file_prepare_directory($extract_path, FILE_CREATE_DIRECTORY)) {
      drupal_set_message(t('Unable to create directory !dir for Gardengnome player files.', [
        '!dir' => $extract_path,
      ]), 'error');
      return FALSE;
    }

    $uri = $file->getFileUri();
    $directory = $extract_path . '/' . file_uri_target($uri);
    if (!file_exists($directory)) {
      if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
        drupal_set_message(t('Unable to create directory !dir for Gardengnome player files.', [
          '!dir' => $directory,
        ]), 'error');
        return FALSE;
      }
      /* @var \Drupal\Core\Archiver\ArchiverInterface $archiver */
      if (!$archiver = archiver_get_archiver($uri)) {
        drupal_set_message(t('The uploaded file %file is not an archive or not of supported archive type.', [
          '%file' => $file->name,
        ]), 'error');
        return FALSE;
      }
      $archiver->extract($directory);
    }

    // Search for pano.xml.
    if (!file_exists($directory . '/pano.xml')) {
      // Loop thru subdirs until file is found.
      $scan = file_scan_directory($directory, '/.*/', ['recurse' => FALSE]);
      foreach ($scan as $dir) {
        if (is_dir($dir->uri) && file_exists($dir->uri . '/pano.xml')) {
          return $dir->uri;
        }
      }
      drupal_set_message(t('The uploaded archive %file doesn\'t contain a panorama.', [
        '%file' => $file->name,
      ]), 'error');
      return FALSE;
    }

    return $directory;
  }

}
