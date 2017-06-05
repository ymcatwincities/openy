<?php

namespace Drupal\config_import;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Interface ConfigImporterServiceInterface.
 */
interface ConfigImporterServiceInterface extends ContainerInjectionInterface {

  /**
   * Set path to directory where configs stored.
   *
   * @param string $directory
   *   Path to directory with configs or type of config directory.
   */
  public function setDirectory($directory);

  /**
   * Get path to directory where configs stored.
   *
   * @return string
   *   Path to directory where configs stored.
   */
  public function getDirectory();

  /**
   * Import configurations.
   *
   * @param string[] $configs
   *   Configurations to import.
   *
   * @example
   * The next example will import the following configs:
   * - /directory/outside/webroot/user.role.authenticated.yml
   * - /directory/outside/webroot/user.role.anonymous.yml
   *
   * @code
   * $this->importConfigs([
   *   'user.role.authenticated',
   *   'user.role.anonymous',
   * ]);
   * @endcode
   */
  public function importConfigs(array $configs);

  /**
   * Export configurations.
   *
   * @param string[] $configs
   *   Configurations to export.
   */
  public function exportConfigs(array $configs);

}
