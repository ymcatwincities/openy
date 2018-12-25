<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Xss;
use Drupal\blazy\BlazyGrid;

/**
 * A Trait common for blazy image and file ER formatters.
 */
trait BlazyFormatterTrait {

  /**
   * Returns the blazy admin service.
   */
  public function admin() {
    return \Drupal::service('blazy.admin.formatter');
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $build = [];
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $build;
    }

    // Collects specific settings to this formatter.
    $settings              = $this->buildSettings();
    $settings['blazy']     = TRUE;
    $settings['namespace'] = $settings['item_id'] = $settings['lazy'] = 'blazy';
    $settings['_grid']     = !empty($settings['style']) && !empty($settings['grid']);

    // Build the settings.
    $build = ['settings' => $settings];

    // Modifies settings.
    $this->blazyManager->buildSettings($build, $items);

    // Build the elements.
    $this->buildElements($build, $files);

    // Updates settings.
    $settings = $build['settings'];
    unset($build['settings']);

    // Supports Blazy multi-breakpoint images if provided.
    $this->blazyManager->isBlazy($settings, $build[0]['#build']);

    // Build grid if provided.
    if (empty($settings['_grid'])) {
      $build['#blazy'] = $settings;
    }
    else {
      $build = BlazyGrid::build($build, $settings);
    }

    $build['#attached'] = $this->blazyManager->attach($settings);
    return $build;
  }

  /**
   * Build the Blazy elements.
   */
  public function buildElements(array &$build, $files) {
    $settings = $build['settings'];
    $item_id  = $settings['item_id'];
    $is_media = method_exists($this, 'getMediaItem');

    if (!empty($settings['caption'])) {
      $settings['caption_attributes']['class'][] = $item_id . '__caption';
    }

    foreach ($files as $delta => $file) {
      /* @var Drupal\image\Plugin\Field\FieldType\ImageItem $item */
      $item = $file->_referringItem;

      $settings['delta']     = $delta;
      $settings['file_tags'] = $file->getCacheTags();
      $settings['type']      = 'image';
      $settings['uri']       = $file->getFileUri();

      $box['item']     = $item;
      $box['settings'] = $settings;

      // If imported Drupal\blazy\Dejavu\BlazyVideoTrait.
      if ($is_media) {
        /** @var Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
        // EntityReferenceItem provides $item->entity Drupal\file\Entity\File.
        if (!empty($this->getImageItem($item, $delta))) {
          $box['item'] = $this->getImageItem($item)['item'];
          $box['settings'] = array_merge($settings, $this->getImageItem($item)['settings']);
        }

        $this->getMediaItem($box, $file);
      }

      // Build caption if so configured.
      if (!empty($settings['caption'])) {
        foreach ($settings['caption'] as $caption) {
          $box['captions'][$caption]['content'] = empty($box['item']->{$caption}) ? [] : ['#markup' => Xss::filterAdmin($box['item']->{$caption})];
          $box['captions'][$caption]['tag'] = $caption == 'title' ? 'h2' : 'div';
          if (!isset($box['captions'][$caption]['attributes'])) {
            $class = $caption == 'alt' ? 'description' : $caption;
            $box['captions'][$caption]['attributes'] = new Attribute();
            $box['captions'][$caption]['attributes']->addClass($item_id . '__' . $class);
          }
        }
      }

      // Image with grid, responsive image, lazyLoad, and lightbox supports.
      $build[$delta] = $this->blazyManager->getImage($box);
      unset($box);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return $this->admin()->settingsSummary($this);
  }

}
