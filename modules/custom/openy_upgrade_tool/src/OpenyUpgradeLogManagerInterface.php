<?php

namespace Drupal\openy_upgrade_tool;

/**
 * Interface for wrapper with main logic that related to OpenyUpgradeLog.
 */
interface OpenyUpgradeLogManagerInterface {

  /**
   * Get Open Y features configs list.
   */
  public function getOpenyConfigList();

  /**
   * Helper function for checking that Upgrade Tool in force mode.
   *
   * @return bool
   *   TRUE if force mode enabled.
   */
  public function isForceMode();

  /**
   * Creates logger entity or update if exist.
   *
   * @param string $name
   *   Config name.
   * @param array $data
   *   Config data.
   * @param string|null $message
   *   Revision message.
   *
   * @return int|bool
   *   Entity ID in case of success.
   */
  public function saveLoggerEntity($name, array $data, $message = NULL);

  /**
   * Creates backup of active config in new logger entity revision.
   *
   * @param string $name
   *   Config name.
   *
   * @return OpenyUpgradeLogManager
   *   OpenyUpgradeLogManager instance.
   */
  public function createBackup($name);

  /**
   * Get Logger Entity Type name.
   *
   * This helper function provide ability of smooth switching from
   * logger entity to OpenyUpgradeLog.
   *
   * We don't know the number of updates and need to support update from any
   * Open Y version. This can be fixed by changing openy_upgrade_tool
   * module weight, but to avoid any issues with deployments and with
   * upgrade path we will dynamically switch to the new workflow during
   * the update.
   * The migration to OpenyUpgradeLog can be considered as completed
   * after the openy_upgrade_tool_update_8005 finish and
   * openy_config_upgrade_logs removing. Util this moment will be used
   * old logger entity.
   *
   * TODO: return only 'openy_upgrade_log' ('logger_entity' is deprecated).
   *
   * @return string
   *   Entity type that used for upgrade log.
   *   logger_entity or openy_upgrade_log
   *
   * @see openy_upgrade_tool.install
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getLoggerEntityTypeName();

  /**
   * Check if config exist in openy_config_upgrade_logs.
   *
   * @param string $config_name
   *   Config name.
   * @param bool $check_force_mode
   *   If TRUE - skip force mode checking to get real changing status.
   *
   * @return bool
   *   TRUE if config was changed.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function isManuallyChanged($config_name, $check_force_mode = TRUE);

  /**
   * Load upgrade log entity by ID.
   *
   * @param int $id
   *   Upgrade log entity ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Upgrade log entity.
   */
  public function load($id);

  /**
   * Load upgrade log entity by name.
   *
   * @param string $config_name
   *   Upgrade log entity name.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Upgrade log entity.
   */
  public function loadByName($config_name);

  /**
   * Import OpenY config from file storage.
   *
   * @param string $name
   *   Upgrade log entity name.
   *
   * @return OpenyUpgradeLogManager
   *   OpenyUpgradeLogManager instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function applyOpenyVersion($name);

  /**
   * Import config from specified data.
   *
   * @param string $name
   *   Upgrade log entity name.
   * @param array $data
   *   Config data for import.
   * @param bool $delete_log
   *   If TRUE - delete OpenyUpgradeLog instance after import.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function updateExistingConfig($name, array $data, $delete_log = FALSE);

  /**
   * Validate specified config data.
   *
   * @param string $name
   *   Upgrade log entity name.
   * @param array $data
   *   Config data for import.
   *
   * @return bool
   *   Validation result. TRUE if valid.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function validateConfigData($name, array &$data);

  /**
   * Get config type.
   *
   * @param string $name
   *   Config name.
   *
   * @return string
   *   Config type.
   */
  public function getConfigType($name);

  /**
   * Validate config diff and get ignore status.
   *
   * @param \Drupal\Core\Config\Config $config
   *
   * @return bool
   *   TRUE - if changes can be ignored.
   */
  public function validateConfigDiff(\Drupal\Core\Config\Config $config);

}
