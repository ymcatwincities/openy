<?php

namespace Drupal\config_import;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Interface ConfigFeaturesImporterServiceInterface.
 */
interface ConfigFeaturesImporterServiceInterface extends ContainerInjectionInterface {

  /**
   * Import features.
   *
   * @param string[] $features
   *   Feature names.
   */
  public function importFeatures(array $features);

}
