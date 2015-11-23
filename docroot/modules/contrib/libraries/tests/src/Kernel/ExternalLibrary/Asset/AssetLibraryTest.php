<?php

/**
 * @file
 * Contains \Drupal\Tests\libraries\Kernel\ExternalLibrary\Asset\AssetLibraryTest.
 */

namespace Drupal\Tests\libraries\Kernel\ExternalLibrary\Asset;

use Drupal\Tests\libraries\Kernel\ExternalLibraryKernelTestBase;

/**
 * Tests that external asset libraries are registered as core asset libraries.
 *
 * @group libraries
 */
class AssetLibraryTest extends ExternalLibraryKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * \Drupal\libraries\Extension requires system_get_info() which is in
   * system.module.
   */
  public static $modules = ['libraries', 'libraries_test', 'system'];

  /**
   * The Drupal core library discovery.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->libraryDiscovery = $this->container->get('library.discovery');
  }

  /**
   * Tests that an external asset library is registered as a core asset library.
   *
   * @see \Drupal\libraries\Extension\Extension
   * @see \Drupal\libraries\Extension\ExtensionHandler
   * @see \Drupal\libraries\ExternalLibrary\Asset\AssetLibrary
   * @see \Drupal\libraries\ExternalLibrary\Asset\AssetLibraryTrait
   * @see \Drupal\libraries\ExternalLibrary\ExternalLibraryManager
   * @see \Drupal\libraries\ExternalLibrary\ExternalLibraryTrait
   * @see \Drupal\libraries\ExternalLibrary\Registry\ExternalLibraryRegistry
   */
  public function testAssetLibrary() {
    $library = $this->libraryDiscovery->getLibraryByName('libraries', 'test_asset_library');
    $this->assertNotEquals(FALSE, $library);
    $this->assertTrue(is_array($library));
  }

}
