<?php

/**
 * @file
 * Contains \Drupal\libraries\ExternalLibrary\ExternalLibraryManager.
 */

namespace Drupal\libraries\ExternalLibrary;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\libraries\Extension\ExtensionHandlerInterface;
use Drupal\libraries\ExternalLibrary\PhpFile\PhpFileLibraryInterface;
use Drupal\libraries\ExternalLibrary\PhpFile\PhpFileLoaderInterface;
use Drupal\libraries\ExternalLibrary\Registry\ExternalLibraryRegistryInterface;

/**
 * Provides a manager for external libraries.
 */
class ExternalLibraryManager implements ExternalLibraryManagerInterface {

  /**
   * The library registry.
   *
   * @var \Drupal\libraries\ExternalLibrary\Registry\ExternalLibraryRegistryInterface
   */
  protected $registry;

  /**
   * The extension handler.
   *
   * @var \Drupal\libraries\Extension\ExtensionHandlerInterface
   */
  protected $extensionHandler;

  /**
   * The PHP file loader.
   *
   * @var \Drupal\libraries\ExternalLibrary\PhpFile\PhpFileLoaderInterface
   */
  protected $phpFileLoader;

  /**
   * Constructs an external library manager.
   *
   * @param \Drupal\libraries\ExternalLibrary\Registry\ExternalLibraryRegistryInterface $registry
   *   The library registry.
   * @param \Drupal\libraries\Extension\ExtensionHandlerInterface $extension_handler
   *   The extension handler.
   * @param \Drupal\libraries\ExternalLibrary\PhpFile\PhpFileLoaderInterface $php_file_loader
   *   The PHP file loader.
   */
  public function __construct(
    ExternalLibraryRegistryInterface $registry,
    ExtensionHandlerInterface $extension_handler,
    PhpFileLoaderInterface $php_file_loader
  ) {
    $this->registry = $registry;
    $this->extensionHandler = $extension_handler;
    $this->phpFileLoader = $php_file_loader;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredLibraries() {
    foreach ($this->extensionHandler->getExtensions() as $extension) {
      foreach ($extension->getLibraryDependencies() as $library_id) {
        // Do not bother instantiating a library multiple times.
        if (!isset($libraries[$library_id])) {
          yield $this->registry->getLibrary($library_id);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    // @todo Dispatch some type of event, to provide loose coupling.
    $library = $this->registry->getLibrary($id);
    // @todo Throw an exception instead of silently failing.
    if ($library instanceof PhpFileLibraryInterface) {
      $path = $library->getLibraryPath();
      foreach ($library->getPhpFiles() as $file) {
        $this->phpFileLoader->load($path . '/' . $file);
      }
    }
  }

}
