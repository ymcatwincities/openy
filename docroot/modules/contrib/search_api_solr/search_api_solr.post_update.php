<?php

/**
 * Installs the standard highlighter config.
 */
function search_api_solr_post_update_install_standard_highlighter_config() {
  /** @var \Drupal\Core\Config\ConfigInstallerInterface $config_installer */
  $config_installer = \Drupal::service('config.installer');
  $config_installer->installDefaultConfig('module', 'search_api_solr');
}
