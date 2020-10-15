<?php

namespace Drupal\openy_prgf\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * OpenY Paragraphs configuration override.
 */
class OpenyParagraphOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (\Drupal::service('module_handler')->moduleExists('entity_clone')) {
      $overrides['entity_clone.settings'] = [
        'form_settings' => [
          'paragraph' => [
            'default_value' => TRUE,
            'disable' => TRUE,
          ],
        ],
      ];
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'OpenyParagraphOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
