<?php

/**
 * @file
 * Contains \Drupal\video\VideoProviderManagerInterface.
 */

namespace Drupal\video;

/**
 * Interface for the class that gathers the provider plugins.
 */
interface ProviderManagerInterface {

  /**
   * Get an options list suitable for form elements for provider selection.
   *
   * @return array
   *   An array of options keyed by plugin ID with label values.
   */
  public function getProvidersOptionList();

  /**
   * Load the provider plugin definitions from a FAPI options list value.
   *
   * @param array $options
   *   An array of options from a form API submission.
   *
   * @return array
   */
  public function loadDefinitionsFromOptionList($options);

  /**
   * Get the provider applicable to the given user input.
   *
   * @param array $definitions
   *   A list of definitions to test against.
   * @param $user_input
   *   The user input to test against the plugins.
   *
   * @return \Drupal\video\ProviderPluginInterface|bool
   *   The relevant plugin or FALSE on failure.
   */
  public function loadApplicableDefinitionMatches(array $definitions, $user_input);
  
  /**
   * Load a provider from stream wrapper.
   *
   * @param string $stream
   *   Stream used from the file.
   *
   * @param Drupal\file\Entity\File $file
   *   The source file.
   *
   * @param array $data
   *   Source file metadata.
   *
   * @return \Drupal\video\ProviderPluginInterface|bool
   *   The loaded plugin.
   */
  public function loadProviderFromStream($stream, $file, $data = array());

}
