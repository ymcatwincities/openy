<?php

namespace Drupal\openy_upgrade_tool;

use Drupal\Component\Utility\NestedArray;
use Drupal\config_import\ConfigParamUpdaterService;
use Drupal\Core\Serialization\Yaml;

/**
 * Advanced version of ConfigParamUpdaterService.
 *
 * With checking of manual config change.
 */
class ConfigParamUpgradeTool extends ConfigParamUpdaterService {

  /**
   * {@inheritdoc}
   */
  public function update($config, $config_name, $param) {
    // Get base storage config.
    if (!file_exists($config)) {
      $this->logger->error($this->t('File @file does not exist.', ['@file' => $config]));
      return;
    }
    // TODO: check if this config was manual changed (get info from logger entity), return if changed.
    $storage_config = Yaml::decode(file_get_contents($config));
    // Retrieve a value from a nested array with variable depth.
    $update_value = NestedArray::getValue($storage_config, explode('.', $param));
    if (!$update_value) {
      $this->logger->info(
        $this->t('Param "@param" not exist in config @name.',
        ['@name' => $config_name, '@param' => $param])
      );
      return;
    }
    // Get active storage config.
    $config_factory = $this->configManager->getConfigFactory();
    $config = $config_factory->getEditable($config_name);
    if ($config->isNew() && empty($config->getOriginal())) {
      $this->logger->error($this->t('Config @name does not exist.', ['@name' => $config_name]));
      return;
    }
    // Update value retrieved from storage config.
    $config->set($param, $update_value);
    // Add openy_upgrade param for detecting this upgrade
    // in '@openy_upgrade_tool.event_subscriber'.
    $config->set('openy_upgrade', TRUE);
    $config->save();
    $this->logger->info($this->t('Param "@param" in config @name was updated.',
      [
        '@name' => $config_name,
        '@param' => $param,
      ]
    ));
  }

}
