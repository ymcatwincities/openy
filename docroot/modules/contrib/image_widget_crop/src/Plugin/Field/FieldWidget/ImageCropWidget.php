<?php

/**
 * @file
 * Contains \Drupal\image_widget_crop\Plugin\Field\FieldWidget\ImageCropWidget.
 */

namespace Drupal\image_widget_crop\Plugin\Field\FieldWidget;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\image_widget_crop\ImageWidgetCrop;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\crop\Entity\CropType;

/**
 * Plugin implementation of the 'image_widget_crop' widget.
 *
 * @FieldWidget(
 *   id = "image_widget_crop",
 *   label = @Translation("ImageWidget crop"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageCropWidget extends ImageWidget {

  /**
   * Instance of API ImageWidgetCrop.
   *
   * @var \Drupal\image_widget_crop\ImageWidgetCrop
   */
  protected $imageWidgetCrop;

  /**
   * The image style storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage
   */
  protected $imageStyleStorage;

  /**
   * The crop type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage
   */
  protected $cropTypeStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ElementInfoManagerInterface $element_info, ImageWidgetCrop $image_widget_crop, ConfigEntityStorage $image_style_storage, ConfigEntityStorage $crop_type_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $element_info);
    $this->imageWidgetCrop = $image_widget_crop;
    $this->imageStyleStorage = $image_style_storage;
    $this->cropTypeStorage = $crop_type_storage;
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
      $configuration['third_party_settings'],
      $container->get('element_info'),
      $container->get('image_widget_crop.manager'),
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('entity.manager')->getStorage('crop_type')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string,string|null>
   *   The array of settings.
   */
  public static function defaultSettings() {
    return [
      'crop_preview_image_style' => 'crop_thumbnail',
      'crop_list' => NULL,
      'crop_help_text' => 'Select an image size to crop'
    ] + parent::defaultSettings();
  }

  /**
   * Form API callback: Processes a crop_image field element.
   *
   * Expands the image_image type to include the alt and title fields.
   *
   * This method is assigned as a #process callback in formElement() method.
   *
   * @return array
   *   The elements with parents fields.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $edit = FALSE;
    $crop_types_list = $element['#crop_types_list'];
    $route_params = \Drupal::requestStack()
      ->getCurrentRequest()->attributes->get('_route_params');

    if (isset($route_params['_entity_form']) && preg_match('/.edit/', $route_params['_entity_form'])) {
      $edit = TRUE;
      /** @var \Drupal\crop\CropStorage $crop_storage */
      $crop_storage = \Drupal::service('entity.manager')->getStorage('crop');
    }

    $element['#theme'] = 'image_widget';
    $element['#attached']['library'][] = 'image/form';
    $element['#attached']['library'][] = 'image_widget_crop/drupal.image_widget_crop.admin';
    $element['#attached']['library'][] = 'image_widget_crop/drupal.image_widget_crop.upload.admin';

    // Add the image preview.
    if (!empty($element['#files']) && $element['#preview_image_style']) {
      $file = reset($element['#files']);
      $variables = ['style_name' => $element['#preview_image_style'], 'uri' => $file->getFileUri(), 'file_id' => $file->id()];
      // Verify if user have uploaded an image.
      self::getFileImageVariables($element, $variables);

      $element['crop_preview_wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['crop-wrapper']],
        '#weight' => 100,
      ];

      // List crop_type container.
      $element['crop_preview_wrapper']['list'] = [
        '#type' => 'crop_sidebar',
        '#attributes' => ['class' => ['ratio-list']],
        '#element_type' => 'ul',
        '#weight' => -10,
      ];

      // Wrap crop elements.
      $element['crop_preview_wrapper']['container'] = [
        '#type' => 'crop_container',
        '#attributes' => ['class' => ['preview-wrapper-crop']],
        '#weight' => 100,
      ];

      // Increase Human lisibility.
      $container = &$element['crop_preview_wrapper']['container'];
      if (!empty($crop_types_list)) {
        foreach ($crop_types_list as $crop_type) {
          /** @var \Drupal\crop\Entity\CropType $crop_type */
          $crop_type_id = $crop_type->id();
          $label = $crop_type->label();
          if (in_array($crop_type_id, $element['#crop_list'])) {
            $thumb_properties = [];
            // Add compatibility to PHP 5.3.
            $has_ratio = $crop_type->getAspectRatio();
            $ratio = !empty($has_ratio) ? $has_ratio : t('None');

            $element['crop_preview_wrapper']['list'][$crop_type_id] = [
              '#type' => 'crop_list_items',
              '#attributes' => ['class' => ['crop-preview-wrapper', 'item'], 'data-ratio' => [$ratio], 'data-name' => [$crop_type_id]],
              '#variables' => ['anchor' => "#$crop_type_id", 'ratio' => $ratio, 'label' => $label]
            ];

            // Generation of html List with image & crop informations.
            $container[$crop_type_id] = [
              '#type' => 'crop_image_container',
              '#attributes' => ['class' => ['crop-preview-wrapper-list'], 'id' => [$crop_type_id], 'data-ratio' => [$ratio]],
              '#variables' => ['label' => $label, 'ratio' => $ratio],
              '#weight' => -10,
            ];

            $container['crop_help'] = [
              '#type' => 'crop_help',
              '#attributes' => ['class' => ['crop-preview-wrapper-list'], 'id' => ['crop-help']],
              '#text' => $element['#crop_help_text'],
              '#weight' => -10,
            ];

            $container[$crop_type_id]['image'] = [
              '#theme' => 'image_style',
              '#style_name' => $element['#crop_preview_image_style'],
              '#attributes' => ['data-ratio' => [$ratio], 'data-name' => [$crop_type_id]],
              '#uri' => $variables['uri'],
              '#weight' => -10,
            ];

            if ($edit && !empty($crop_storage)) {
              $crops = $crop_storage->loadByProperties(['type' => $crop_type_id, 'uri' => $variables['uri']]);
              if (!empty($crops)) {
                // Only if the crop already exist pre-populate,
                // all cordinates values.
                $crop_properties = self::getCropProperties($crops);
                // Add "saved" class if the crop already exist,
                // (in list & img container element).
                $element['crop_preview_wrapper']['list'][$crop_type_id]['#attributes']['class'][] = 'saved';
                $container[$crop_type_id]['#attributes']['class'][] = 'saved';

                $thumb_properties = self::getThumbnailCropProperties($variables['uri'], $crop_properties);
              }
            }

            // Generation of html List with image & crop informations.
            $container[$crop_type_id]['values'] = [
              '#type' => 'container',
              '#attributes' => ['class' => ['crop-preview-wrapper-value']],
              '#weight' => -9,
            ];

            self::getCropFormElement($element, $thumb_properties, $edit, $crop_type_id);

            $container[$crop_type_id]['delete-crop'] = [
              '#type' => 'hidden',
              '#attributes' => ['class' => ["delete-crop"]],
              '#value' => 0,
            ];

            // Stock Original File Values.
            $element['file-uri'] = [
              '#type' => 'value',
              '#value' => $variables['uri'],
            ];

            $element['file-id'] = [
              '#type' => 'value',
              '#value' => $variables['file_id'],
            ];
          }
        }
      }
    }

    return parent::process($element, $form_state, $form);
  }

  /**
   * Set All sizes properties of the crops.
   *
   * @return array<string,array>
   *   Set all possible crop zone properties.
   */
  public static function setCoordinatesElement() {
    return [
      'x1' => ['label' => t('Crop x1'), 'value' => NULL],
      'x2' => ['label' => t('Crop x2'), 'value' => NULL],
      'y1' => ['label' => t('Crop y1'), 'value' => NULL],
      'y2' => ['label' => t('Crop y2'), 'value' => NULL],
      'crop-w' => ['label' => t('Crop size width'), 'value' => NULL],
      'crop-h' => ['label' => t('Crop size height'), 'value' => NULL],
      'thumb-w' => ['label' => t('Thumbnail Width'), 'value' => NULL],
      'thumb-h' => ['label' => t('Thumbnail Height'), 'value' => NULL],
    ];
  }

  /**
   * Get All sizes properties of the crops for an file.
   *
   * @param array $crops
   *   All crops attached to this file based on URI.
   *
   * @return array<array>
   *   Get all crop zone properties (x, y, height, width),
   */
  public static function getCropProperties(array $crops) {
    $crop_properties = [];
    /** @var \Drupal\crop\Entity\Crop $crop_entity */
    foreach ($crops as $crop_entity) {
      $crop_properties = [
        'anchor' => $crop_entity->anchor(),
        'size' => $crop_entity->size()
      ];
    }

    return $crop_properties;
  }

  /**
   * Update crop elements of crop into the form widget.
   *
   * @param array $thumb_properties
   *   All properties calculate for apply to,
   *   thumbnail image in UI.
   * @param bool $edit
   *   Context of this form.
   *
   * @return array<string,array>
   *   Populate all crop elements into the form.
   */
  public static function getCropFormProperties(array $thumb_properties, $edit) {
    $crop_elements = self::setCoordinatesElement();
    if (!empty($thumb_properties) && $edit) {
      foreach ($crop_elements as $properties => $value) {
        $crop_elements[$properties]['value'] = $thumb_properties[$properties];
      }
    }

    return $crop_elements;
  }

  /**
   * Inject crop elements into the form widget.
   *
   * @param array $element
   *   All form elements without crop properties.
   * @param array $thumb_properties
   *   All properties calculate for apply to,
   *   thumbnail image in UI.
   * @param bool $edit
   *   Context of this form.
   * @param string $crop_type
   *   The id of the current crop.
   *
   * @return array|NULL
   *   Populate all crop elements into the form.
   */
  public static function getCropFormElement(array &$element, array $thumb_properties, $edit, $crop_type) {
    if ($crop_type != 'crop_help') {
      $crop_properties = self::getCropFormProperties($thumb_properties, $edit);
      // Generate all cordinates elements into the form when,
      // process is active.
      foreach ($crop_properties as $property => $value) {
        $crop_element = &$element['crop_preview_wrapper']['container'][$crop_type]['values'][$property];
        $value_property = self::getCropFormPropertyValue($element, $crop_type, $edit, $value['value'], $property);
        $crop_element = [
          '#type' => 'hidden',
          '#attributes' => [
            'class' => ["crop-$property"]
          ],
          '#value' => $value_property,
        ];
      }

      return $element;
    }
    return NULL;
  }

  /**
   * Get default value of property elements.
   *
   * @param array $element
   *   All form elements without crop properties.
   * @param string $crop_type
   *   The id of the current crop.
   * @param bool $edit
   *   Context of this form.
   * @param bool $value
   *   The values calculated by getCropFormProperties().
   * @param string $property
   *   Name of current property @see setCoordinatesElement().
   *
   * @return integer|NULL
   *   Value of this element.
   */
  public static function getCropFormPropertyValue(array &$element, $crop_type, $edit, $value, $property) {
    // Standard case.
    if (!empty($edit) && !empty($value)) {
      return $value;
    }
    // Populate value when ajax populates values after process.
    if (isset($element['#value'])) {
      $ajax_element = &$element['#value']['crop_preview_wrapper']['container'][$crop_type]['values'];
      return (isset($ajax_element[$property]) && !empty($ajax_element[$property])) ? $ajax_element[$property] : NULL;
    }

    return NULL;
  }

  /**
   * Calculate properties of thumbnail preview.
   *
   * @param string $uri
   *   The uri of uploaded image.
   * @param array $original_crop
   *   All properties returned by the crop plugin (js),
   *   and the size of thumbnail image.
   * @param string $preview
   *   An array of values for the contained properties of image_crop widget.
   *
   * @return array<double>
   *   All properties (x1, x2, y1, y2, crop height, crop width,
   *   thumbnail height, thumbnail width), to apply the real crop
   *   into thumbnail preview.
   */
  public static function getThumbnailCropProperties($uri, array $original_crop, $preview = 'crop_thumbnail') {
    $crop_thumbnail = [];

    $image_styles = \Drupal::service('entity.manager')
      ->getStorage('image_style')
      ->loadByProperties(['status' => TRUE, 'name' => $preview]);

    // Verify the configuration of ImageStyle and get the data width.
    /** @var \Drupal\image\Entity\ImageStyle $image_style */
    $image_style = $image_styles[$preview];
    $effect = $image_style->getEffects()->getConfiguration();

    // Get the real sizes of uploaded image.
    list($width, $height) = getimagesize($uri);

    // Get max Width of this imageStyle.
    $thumbnail_width = $effect[array_keys($effect)[0]]['data']['width'];

    if (!isset($thumbnail_width) || !is_int($thumbnail_width)) {
      throw new \RuntimeException('Your crop preview ImageStyle not have "width", add it to have an correct preview.');
    }

    // Special case when the width of image is less,
    // than maximum width of thumbnail.
    if ($thumbnail_width > $width) {
      $thumbnail_width = $width;
    }

    // Calculate Thumbnail height
    // (Original Height x Thumbnail Width / Original Width = Thumbnail Height).
    $thumbnail_height = round(($height * $thumbnail_width) / $width);

    // Get the delta between Original Height divide by Thumbnail Height.
    $delta = number_format($height / $thumbnail_height, 2, '.', '');

    // Get the Crop selection Size (into Uploaded image) &,
    // calculate selection for Thumbnail.
    $crop_thumbnail['crop-h'] = round($original_crop['size']['height'] / $delta);
    $crop_thumbnail['crop-w'] = round($original_crop['size']['width'] / $delta);

    // Calculate the Top-Left corner for Thumbnail.
    $crop_thumbnail['x1'] = round($original_crop['anchor']['x'] / $delta);
    $crop_thumbnail['y1'] = round($original_crop['anchor']['y'] / $delta);

    // Calculate the Bottom-right position for Thumbnail.
    $crop_thumbnail['x2'] = $crop_thumbnail['x1'] + $crop_thumbnail['crop-w'];
    $crop_thumbnail['y2'] = $crop_thumbnail['y1'] + $crop_thumbnail['crop-h'];

    // Get the real thumbnail sizes.
    $crop_thumbnail['thumb-w'] = $thumbnail_width;
    $crop_thumbnail['thumb-h'] = $thumbnail_height;

    return $crop_thumbnail;
  }

  /**
   * Verify if ImageStyle is correctly configured.
   *
   * @param array $styles
   *   The list of available ImageStyle.
   *
   * @return array<integer>
   *   The list of styles filtred.
   */
  public function getAvailableCropImageStyle(array $styles) {
    $available_styles = [];
    foreach ($styles as $style_id => $style_label) {
      $style_loaded = $this->imageStyleStorage->loadByProperties(['name' => $style_id]);
      /** @var \Drupal\image\Entity\ImageStyle $image_style */
      $image_style = $style_loaded[$style_id];
      $effect_data = $this->imageWidgetCrop->getEffectData($image_style, 'width');
      if (!empty($effect_data)) {
        $available_styles[$style_id] = $style_label;
      }
    }

    return $available_styles;
  }

  /**
   * Verify if the crop is used by a ImageStyle.
   *
   * @param array $crop_list
   *   The list of existent Crop Type.
   *
   * @return array<integer>
   *   The list of Crop Type filtred.
   */
  public function getAvailableCropType(array $crop_list) {
    $available_crop = [];
    foreach ($crop_list as $crop_machine_name => $crop_label) {
      $image_styles = $this->imageWidgetCrop->getImageStylesByCrop($crop_machine_name);
      if (!empty($image_styles)) {
        $available_crop[$crop_machine_name] = $crop_label;
      }
    }

    return $available_crop;
  }

  /**
   * Verify if the element have an image file.
   *
   * @param array $element
   *   A form element array containing basic properties for the widget.
   * @param array $variables
   *   An array with all existent variables for render.
   *
   * @return array<string,array>
   *   The variables with width & height image informations.
   */
  public static function getFileImageVariables(array $element, array &$variables) {
    // Determine image dimensions.
    if (isset($element['#value']['width']) && isset($element['#value']['height'])) {
      $variables['width'] = $element['#value']['width'];
      $variables['height'] = $element['#value']['height'];
    }
    else {
      /** @var \Drupal\Core\Image\Image $image */
      $image = \Drupal::service('image.factory')->get($variables['uri']);
      if ($image->isValid()) {
        $variables['width'] = $image->getWidth();
        $variables['height'] = $image->getHeight();
      }
      else {
        $variables['width'] = $variables['height'] = NULL;
      }
    }

    return $variables;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['crop_preview_image_style'] = [
      '#title' => t('Crop preview image style'),
      '#type' => 'select',
      '#options' => $this->getAvailableCropImageStyle(image_style_options(FALSE)),
      '#default_value' => $this->getSetting('crop_preview_image_style'),
      '#description' => t('The preview image will be shown while editing the content.'),
      '#weight' => 15,
    ];

    $element['crop_list'] = [
      '#title' => t('Crop Type'),
      '#type' => 'select',
      '#options' => $this->getAvailableCropType(CropType::getCropTypeNames()),
      '#empty_option' => t('<@no-preview>', ['@no-preview' => t('no preview')]),
      '#default_value' => $this->getSetting('crop_list'),
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#description' => t('The type of crop to apply to your image. If your Crop Type not appear here, set an image style use your Crop Type'),
      '#weight' => 16,
    ];

    $element['crop_help_text'] = [
      '#title' => t('Crop default help text'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('crop_help_text'),
      '#description' => t('Help text in the form for first slide after upload an image to crop.'),
      '#weight' => 16,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<array>
   *   A short summary of the widget settings.
   */
  public function settingsSummary() {
    $preview = [];

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);

    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $image_styles[$this->getSetting('preview_image_style')];
    $crop_preview = $image_styles[$this->getSetting('crop_preview_image_style')];
    $crop_list = $this->getSetting('crop_list');
    $crop_help_text = $this->getSetting('crop_help_text');

    if (isset($image_style_setting)) {
      $preview[] = t('Preview image style: @style', ['@style' => $image_style_setting]);
    }
    else {
      $preview[] = t('No preview image style');
    }

    if (isset($crop_preview)) {
      $preview[] = t('Preview crop zone image style: @style', ['@style' => $crop_preview]);
    }

    if (!empty($crop_list)) {
      $preview[] = t('Crop Type used: @list', ['@list' => implode(", ", $crop_list)]);
    }

    if (!empty($crop_help_text)) {
      $preview[] = t('Default help text: @text', ['@text' => $crop_help_text]);
    }

    return $preview;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string,array>
   *   The form elements for a single widget for this field.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Add properties needed by process() method.
    $element['#crop_list'] = $this->getSetting('crop_list');
    $element['#crop_preview_image_style'] = $this->getSetting('crop_preview_image_style');
    $element['#crop_types_list'] = $this->cropTypeStorage->loadMultiple();
    $element['#crop_help_text'] = $this->getSetting('crop_help_text');
    // Set an custom upload_location.
    $element['#upload_location'] = 'public://crop/pictures/';

    return parent::formElement($items, $delta, $element, $form, $form_state);
  }

}
