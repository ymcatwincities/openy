<?php

namespace Drupal\file_browser\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests install/uninstall routines for File Browser.
 *
 * @group file_browser
 */
class InstallTest extends WebTestBase {

  /**
   * {@inheritdoc}
   *
   * We set this to FALSE here as DropzoneJS and Entity Browser use dynamic
   * config settings which fail strict checks during install.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file_browser'];

  /**
   * Tests if the module can be installed during a config sync.
   */
  public function testInstallDuringSync() {
    // Export config post-module install.
    $active = $this->container->get('config.storage');
    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($active, $sync);

    // Uninstall File browser.
    /** @var \Drupal\Core\Extension\ModuleInstaller $module_installer */
    $module_installer = $this->container->get('module_installer');
    $module_installer->uninstall(['file_browser']);

    // Import config.
    $this->configImporter()->import();
  }

}
