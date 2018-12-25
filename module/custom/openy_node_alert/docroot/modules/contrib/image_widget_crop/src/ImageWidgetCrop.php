<?php

/**
 * @file
 * Contains of \Drupal\image_widget_crop\ImageWidgetCrop.
 */

namespace Drupal\image_widget_crop;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\crop\Entity\Crop;
use Drupal\crop\Entity\CropType;
use Drupal\image\Entity\ImageStyle;

/**
 * ImageWidgetCrop calculation class.
 */
class ImageWidgetCrop {

  /**
   * The crop storage.
   *
   * @var \Drupal\crop\CropStorage.
   */
  protected $cropStorage;

  /**
   * The image style storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage.
   */
  protected $imageStyleStorage;

  /**
   * The File storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage.
   */
  protected $fileStorage;

  /**
   * Constructs a ImageWidgetCrop.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->cropStorage = $entity_manager->getStorage('crop');
    $this->imageStyleStorage = $entity_manager->getStorage('image_style');
    $this->fileStorage = $entity_manager->getStorage('file');
  }

  /**
   * Create new crop entity with user properties.
   *
   * @param array $properties
   *   All properties returned by the crop plugin (js),
   *   and the size of thumbnail image.
   * @param array|mixed $field_value
   *   An array of values for the contained properties of image_crop widget.
   * @param CropType $crop_type
   *   The entity CropType.
   */
  public function applyCrop(array $properties, $field_value, CropType $crop_type) {
    // Get Original sizes and position of crop zone.
    $crop_properties = $this->getCropOriginalDimension($field_value['height'], $properties);
    // Get all imagesStyle used this crop_type.
    $image_styles = $this->getImageStylesByCrop($crop_type->id());

    $this->saveCrop($crop_properties, $field_value, $image_styles, $crop_type);
  }

  /**
   * Update old crop with new properties choose in UI.
   *
   * @param array $properties
   *   All properties returned by the crop plugin (js),
   *   and the size of thumbnail image.
   * @param array|mixed $field_value
   *   An array of values for the contained properties of image_crop widget.
   * @param CropType $crop_type
   *   The entity CropType.
   */
  public function updateCrop(array $properties, $field_value, CropType $crop_type) {
    // Get Original sizes and position of crop zone.
    $crop_properties = $this->getCropOriginalDimension($field_value['height'], $properties);
    // Get all imagesStyle used this crop_type.
    $image_styles = $this->getImageStylesByCrop($crop_type->id());

    if (!empty($image_styles)) {
      $crops = $this->loadImageStyleByCrop($image_styles, $crop_type, $field_value['file-uri']);
    }

    // If any crop exist add new crop.
    if (empty($crops)) {
      $this->saveCrop($crop_properties, $field_value, $image_styles, $crop_type);
      return;
    }

    foreach ($crops as $crop_element) {
      /** @var \Drupal\crop\Entity\Crop $crop */
      $crop = current($crop_element);

      if ($this->cropHasChanged($crop_properties, array_merge($crop->position(), $crop->size()))) {
        return;
      }

      $this->updateCropProperties($crop, $crop_properties);
      $this->imageStylesOperations($image_styles, $field_value['file-uri']);
      drupal_set_message(t('The crop "@cropType" are successfully updated for image "@filename"', ['@cropType' => $crop_type->label(), '@filename' => $this->fileStorage->load($field_value['file-id'])->getFilename()]));
    }
  }

  /**
   * Save the crop when this crop not exist.
   *
   * @param double[] $crop_properties
   *   The properties of the crop applied to the original image (dimensions).
   * @param array|mixed $field_value
   *   An array of values for the contained properties of image_crop widget.
   * @param array $image_styles
   *   The list of imagesStyle available for this crop.
   * @param CropType $crop_type
   *   The entity CropType.
   */
  public function saveCrop(array $crop_properties, $field_value, array $image_styles, CropType $crop_type) {
    /** @var \Drupal\image\Entity\ImageStyle $image_style */
    foreach ($image_styles as $image_style) {
      $values = [
        'type' => $crop_type->id(),
        'entity_id' => $field_value['file-id'],
        'entity_type' => 'file',
        'uri' => $field_value['file-uri'],
        'x' => $crop_properties['x'],
        'y' => $crop_properties['y'],
        'width' => $crop_properties['width'],
        'height' => $crop_properties['height'],
        'image_style' => $image_style->getName(),
      ];

      // Save crop with previous values.
      /** @var \Drupal\crop\CropInterface $crop */
      $crop = $this->cropStorage->create($values);
      $crop->save();
    }
    $this->imageStylesOperations($image_styles, $field_value['file-uri'], TRUE);
    drupal_set_message(t('The crop "@cropType" are successfully added for image "@filename"', ['@cropType' => $crop_type->label(), '@filename' => $this->fileStorage->load($field_value['file-id'])->getFilename()]));
  }

  /**
   * Delete the crop when user delete it.
   *
   * @param string $file_uri
   *   Uri of image uploaded by user.
   * @param \Drupal\crop\Entity\CropType $crop_type
   *   The CropType object.
   * @param int $file_id
   *   Id of image uploaded by user.
   */
  public function deleteCrop($file_uri, CropType $crop_type, $file_id) {
    $image_styles = $this->getImageStylesByCrop($crop_type->id());
    /** @var \Drupal\image\Entity\ImageStyle $image_style */
    foreach ($image_styles as $image_style) {
      $crop = $this->cropStorage->loadByProperties([
        'type' => $crop_type->id(),
        'uri' => $file_uri,
        'image_style' => $image_style->getName(),
      ]);
      $this->cropStorage->delete($crop);
    }
    $this->imageStylesOperations($image_styles, $file_uri);
    drupal_set_message(t('The crop "@cropType" are successfully delete for image "@filename"', ['@cropType' => $crop_type->label(), '@filename' => $this->fileStorage->load($file_id)->getFilename()]));
  }

  /**
   * Get center of crop selection.
   *
   * @param int[] $axis
   *   Coordinates of x-axis & y-axis.
   * @param array $crop_selection
   *   Coordinates of crop selection (width & height).
   *
   * @return array<string,double>
   *   Coordinates (x-axis & y-axis) of crop selection zone.
   */
  public function getAxisCoordinates(array $axis, array $crop_selection) {
    return [
      'x' => (int) $axis['x'] + ($crop_selection['width'] / 2),
      'y' => (int) $axis['y'] + ($crop_selection['height'] / 2),
    ];
  }

  /**
   * Get the size and position of the crop.
   *
   * @param int $original_height
   *   The original height of image.
   * @param array $properties
   *   The original height of image.
   *
   * @return array<double>
   *   The data dimensions (width & height) into this ImageStyle.
   */
  public function getCropOriginalDimension($original_height, array $properties) {
    $delta = $original_height / $properties['thumb-h'];

    // Get Center coordinate of crop zone.
    $axis_coordinate = $this->getAxisCoordinates(
      ['x' => $properties['x1'], 'y' => $properties['y1']],
      ['width' => $properties['crop-w'], 'height' => $properties['crop-h']]
    );

    // Calculate coordinates (position & sizes) of crop zone.
    $crop_coordinates = $this->getCoordinates([
      'width' => $properties['crop-w'],
      'height' => $properties['crop-h'],
      'x' => $axis_coordinate['x'],
      'y' => $axis_coordinate['y'],
    ], $delta);

    return $crop_coordinates;
  }

  /**
   * Calculate all coordinates for apply crop into original picture.
   *
   * @param array $properties
   *   All properties returned by the crop plugin (js),
   *   and the size of thumbnail image.
   * @param int $delta
   *   The calculated difference between original height and thumbnail height.
   *
   * @return array<double>
   *   Coordinates (x & y or width & height) of crop.
   */
  public function getCoordinates(array $properties, $delta) {
    $original_coordinates = [];
    foreach ($properties as $key => $coordinate) {
      $original_coordinates[$key] = round($coordinate * $delta);
    }

    return $original_coordinates;
  }

  /**
   * Get one effect instead of ImageStyle.
   *
   * @param \Drupal\image\Entity\ImageStyle $image_style
   *   The ImageStyle to get data.
   * @param string $data_type
   *   The type of data needed in current ImageStyle.
   *
   * @return mixed|NULL
   *   The effect data in current ImageStyle.
   */
  public function getEffectData(ImageStyle $image_style, $data_type) {
    $data = NULL;
    /* @var  \Drupal\image\ImageEffectInterface $effect */
    foreach ($image_style->getEffects() as $uuid => $effect) {
      $data_effect = $image_style->getEffect($uuid)->getConfiguration()['data'];
      if (isset($data_effect[$data_type])) {
        $data = $data_effect[$data_type];
      }
    }

    return $data;
  }

  /**
   * Get the imageStyle using this crop_type.
   *
   * @param string $crop_type_name
   *   The id of the current crop_type entity.
   *
   * @return array
   *   All imageStyle used by this crop_type.
   */
  public function getImageStylesByCrop($crop_type_name) {
    $styles = [];
    $image_styles = $this->imageStyleStorage->loadMultiple();

    /** @var \Drupal\image\Entity\ImageStyle $image_style */
    foreach ($image_styles as $image_style) {
      $image_style_data = $this->getEffectData($image_style, 'crop_type');
      if (!empty($image_style_data) && ($image_style_data == $crop_type_name)) {
        $styles[] = $image_style;
      }
    }

    return $styles;
  }

  /**
   * Apply different operation on ImageStyles.
   *
   * @param array $image_styles
   *   All ImageStyles used by this cropType.
   * @param string $file_uri
   *   Uri of image uploaded by user.
   * @param bool $create_derivative
   *   Boolean to create an derivative of the image uploaded.
   */
  public function imageStylesOperations(array $image_styles, $file_uri, $create_derivative = FALSE) {
    /** @var \Drupal\image\Entity\ImageStyle $image_style */
    foreach ($image_styles as $image_style) {
      if ($create_derivative) {
        // Generate the image derivative uri.
        $destination_uri = $image_style->buildUri($file_uri);

        // Create a derivative of the original image with a good uri.
        $image_style->createDerivative($file_uri, $destination_uri);
      }
      // Flush the cache of this ImageStyle.
      $image_style->flush($file_uri);
    }
  }

  /**
   * Update existent crop entity properties.
   *
   * @param \Drupal\crop\Entity\Crop $crop
   *   The crop object loaded.
   * @param array $crop_properties
   *   The machine name of ImageStyle.
   */
  public function updateCropProperties(Crop $crop, array $crop_properties) {
    // Parse all properties if this crop have changed.
    foreach ($crop_properties as $crop_coordinate => $value) {
      // Edit the crop properties if he have changed.
      $crop->set($crop_coordinate, $value, TRUE)
        ->save();
    }
  }

  /**
   * Load all crop using the ImageStyles.
   *
   * @param array $image_styles
   *   All ImageStyle for this current CROP.
   * @param CropType $crop_type
   *   The entity CropType.
   * @param string $file_uri
   *   Uri of uploded file.
   *
   * @return array
   *   All crop used this ImageStyle.
   */
  public function loadImageStyleByCrop(array $image_styles, CropType $crop_type, $file_uri) {
    $crops = [];
    /** @var \Drupal\image\Entity\ImageStyle $image_style */
    foreach ($image_styles as $image_style) {
      $crop_entities = $this->cropStorage->loadByProperties(['type' => $crop_type->id(), 'uri' => $file_uri, 'image_style' => $image_style->id()]);
      if (!empty($crop_entities)) {
        $crops[$image_style->id()] = $crop_entities;
      }
    }

    return $crops;
  }

  /**
   * Compare crop zone properties when user saved one crop.
   *
   * @param array $crop_properties
   *   The crop properties after saved the form.
   * @param array $old_crop
   *   The crop properties save in this crop entity,
   *   Only if this crop already exist.
   *
   * @return bool
   *   True if properties not match.
   */
  public function cropHasChanged(array $crop_properties, array $old_crop) {
    return (($crop_properties['x'] == $old_crop['x'] && $crop_properties['width'] == $old_crop['width']) && ($crop_properties['y'] == $old_crop['y'] && $crop_properties['height'] == $old_crop['height']));
  }

}
