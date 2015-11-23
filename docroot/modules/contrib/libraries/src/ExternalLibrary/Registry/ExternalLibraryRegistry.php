<?php

/**
 * @file
 * Contains \Drupal\libraries\ExternalLibrary\Registry\ExternalLibraryRegistry.
 */

namespace Drupal\libraries\ExternalLibrary\Registry;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\libraries\ExternalLibrary\Exception\LibraryClassNotFoundException;
use Drupal\libraries\ExternalLibrary\Exception\LibraryDefinitionNotFoundException;

/**
 * Provides an implementation of a registry of external libraries.
 *
 * @todo Allow for JavaScript CDN's, Packagist, etc. to act as library
 *   registries.
 */
class ExternalLibraryRegistry implements ExternalLibraryRegistryInterface {

  /**
   * The serializer for the library definition files.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * Constructs a registry of external libraries.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serializer for the library definition files.
   */
  public function __construct(SerializationInterface $serializer) {
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary($id) {
    if (!$this->hasDefinition($id)) {
      throw new LibraryDefinitionNotFoundException($id);
    }
    $definition = $this->getDefinition($id);
    $class = $this->getClass($definition);
    return $class::create($id, $definition);
  }

  /**
   * Checks whether a library definition exists for the given ID.
   *
   * @param string $id
   *   The library ID to check for.
   *
   * @return bool
   *  TRUE if the library definition exists; FALSE otherwise.
   */
  protected function hasDefinition($id) {
    return file_exists($this->getFileUri($id));
  }

  /**
   * Returns the library definition for the given ID.
   *
   * @param string $id
   *   The library ID to retrieve the definition for.
   *
   * @return array
   *   The library definition array parsed from the definition JSON file.
   */
  protected function getDefinition($id) {
    return $this->serializer->decode(file_get_contents($this->getFileUri($id)));
  }

  /**
   * Returns the file URI of the library definition file for a given library ID.
   *
   * @param $id
   *   The ID of the external library.
   *
   * @return string
   *   The file URI of the file the library definition resides in.
   */
  protected function getFileUri($id) {
    $filename = $id . '.' . $this->serializer->getFileExtension();
    return "library-definitions://$filename";
  }

  /**
   * Returns the library class for a library definition.
   *
   * @param array $definition
   *   The library definition array parsed from the definition JSON file.
   *
   * @return string|\Drupal\libraries\ExternalLibrary\ExternalLibraryInterface
   *   The library class.
   *
   * @throws \Drupal\libraries\ExternalLibrary\Exception\LibraryClassNotFoundException
   */
  protected function getClass(array $definition) {
    // @todo Reconsider
    if (!isset($definition['class'])) {
      // @todo What if $definition['id'] is not set?
      throw new LibraryClassNotFoundException($definition['id']);
    }
    // @todo Make sure the class exists.
    return $definition['class'];
  }

}
