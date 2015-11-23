<?php

/**
 * @file
 * Contains \Drupal\libraries\ExternalLibrary\Asset\AssetLibrary.
 */

namespace Drupal\libraries\ExternalLibrary\Asset;

use Drupal\libraries\ExternalLibrary\ExternalLibraryTrait;

/**
 * Provides a base asset library implementation.
 */
class AssetLibrary implements AssetLibraryInterface {

  use ExternalLibraryTrait;
  use SingleAssetLibraryTrait;

  /**
   * Construct an external library.
   *
   * @param string $id
   *   The library ID.
   * @param array $definition
   *   The library definition array parsed from the definition JSON file.
   */
  public function __construct($id, array $definition) {
    $this->id = (string) $id;
    // @todo Split this into proper properties.
    $this->definition = $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getCssAssets() {
    // @todo
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getJsAssets() {
    // @todo
    return [];
  }

}
