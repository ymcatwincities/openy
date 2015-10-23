<?php

/**
 * @file
 * Contains \Drupal\file_entity\Plugin\Field\FieldFormatter\FileImageFormatter.
 */

namespace Drupal\file_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Implementation of the 'image' formatter for the file_entity files.
 *
 * @FieldFormatter(
 *   id = "file_image",
 *   label = @Translation("File Image"),
 *   field_types = {
 *     "uri"
 *   }
 * )
 */
class FileImageFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $file = $items->getEntity();

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $cache_tags = $image_style->getCacheTags();
    }
    $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

    if (isset($image_style)) {
      $elements[0] = [
        '#theme' => 'image_style',
        '#style_name' => $image_style_setting,
      ];
    }
    else {
      $elements[0] = [
        '#theme' => 'image',
      ];
    }
    $elements[0] += [
      '#uri' => $file->getFileUri(),
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {}

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    unset($element['image_link']);
    return $element;
  }

}
