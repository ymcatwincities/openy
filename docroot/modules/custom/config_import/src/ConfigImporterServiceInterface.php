<?php

namespace Drupal\config_import;

/**
 * Interface ConfigImporterServiceInterface.
 *
 * @package Drupal\config_import
 */
interface ConfigImporterServiceInterface {

  /**
   * Import config files.
   *
   * @param array $files
   *   Config files to import.
   */
  public function importConfigs(array $files);

}
