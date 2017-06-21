<?php

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Copy a file from a blob into a file.
 *
 * @MigrateProcessPlugin(
 *   id = "file_blob"
 * )
 */
class FileBlob extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a file_blob process plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, FileSystemInterface $file_system) {
    $configuration += array(
      'reuse' => FALSE,
    );
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If we're stubbing a file entity, return a URI of NULL so it will get
    // stubbed by the general process.
    if ($row->isStub()) {
      return NULL;
    }
    list($destination, $blob) = $value;

    // Determine if we going to overwrite existing files or not touch them.
    $replace = $this->getOverwriteMode();

    // Attempt to save the file to avoid calling file_prepare_directory() any
    // more than absolutely necessary.
    if ($this->putFile($destination, $blob, $replace)) {
      return $destination;
    }
    $dir = $this->getDirectory($destination);
    if (!file_prepare_directory($dir, FILE_CREATE_DIRECTORY)) {
      throw new MigrateSkipProcessException("Could not create directory '$dir'");
    }
    if ($this->putFile($destination, $blob, $replace)) {
      return $destination;
    }
    throw new MigrateSkipProcessException("Blob data could not be copied to $destination.");
  }

  /**
   * Try to save the file.
   *
   * @param string $destination
   *   The destination path or URI.
   * @param string $blob
   *   The base64 encoded file contents.
   * @param int $replace
   *   (optional) FILE_EXISTS_REPLACE (default) or FILE_EXISTS_ERROR, depending
   *   on the configuration.
   *
   * @return bool|string
   *   File path on success, FALSE on failure.
   */
  protected function putFile($destination, $blob, $replace = FILE_EXISTS_REPLACE) {
    if ($path = file_destination($destination, $replace)) {
      if (file_put_contents($path, $blob)) {
        return $path;
      }
      else {
        return FALSE;
      }
    }

    // File was already copied.
    return $destination;
  }

  /**
   * Determines how to handle file conflicts.
   *
   * @return int
   *   Either FILE_EXISTS_REPLACE (default) or FILE_EXISTS_ERROR, depending on
   *   the configuration.
   */
  protected function getOverwriteMode() {
    if (!empty($this->configuration['reuse'])) {
      return FILE_EXISTS_ERROR;
    }

    return FILE_EXISTS_REPLACE;
  }

  /**
   * Returns the directory component of a URI or path.
   *
   * For URIs like public://foo.txt, the full physical path of public://
   * will be returned, since a scheme by itself will trip up certain file
   * API functions (such as file_prepare_directory()).
   *
   * @param string $uri
   *   The URI or path.
   *
   * @return string|false
   *   The directory component of the path or URI, or FALSE if it could not
   *   be determined.
   */
  protected function getDirectory($uri) {
    $dir = $this->fileSystem->dirname($uri);
    if (substr($dir, -3) == '://') {
      return $this->fileSystem->realpath($dir);
    }
    return $dir;
  }

}
