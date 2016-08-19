<?php

namespace Drupal\ymca_mindbody;

/**
 * Mindbody Trainings Mapping Service.
 *
 * @package Drupal\mindbody
 */
class YmcaMindbodyTrainingsMapping {

  /**
   * Default image. Will be displayed when image is missed in configuration.
   */
  const DEFAULT_IMAGE = 'default_program.jpg';

  /**
   * Configuration name.
   */
  const CONFIG_NAME = 'ymca_mindbody.trainings_mapping';

  /**
   * Returns mapping config.
   *
   * @param string $key
   *   Config key.
   *
   * @return array
   *   Configuration array.
   */
  private function getMappingConfig($key) {
    return \Drupal::config($this::CONFIG_NAME)->get($key);
  }

  /**
   * Indicates whether program is active or not.
   *
   * Wrapper for MindbodyObjectIsActive().
   *
   * @param int $id
   *   Program ID.
   *
   * @return bool
   *   Whether program is active or not.
   */
  public function programIsActive($id) {
    return $this->mindbodyObjectIsActive($id, 'programs');
  }

  /**
   * Indicates whether location is active or not.
   *
   * Wrapper for MindbodyObjectIsActive().
   *
   * @param int $id
   *   Location ID.
   *
   * @return bool
   *   Whether location is active or not.
   */
  public function locationIsActive($id) {
    return $this->mindbodyObjectIsActive($id, 'locations');
  }

  /**
   * Returns label for program.
   *
   * Wrapper for getMindbodyObjectLabel().
   *
   * @param int $id
   *   Program ID.
   * @param string $default_label
   *   Default label.
   *
   * @return string
   *   Return original or overridden label.
   */
  public function getProgramLabel($id, $default_label) {
    return $this->getMindbodyObjectLabel($id, $default_label, 'programs');
  }

  /**
   * Returns label for location.
   *
   * Wrapper for getMindbodyObjectLabel().
   *
   * @param int $id
   *   Location ID.
   * @param string $default_label
   *   Default label.
   *
   * @return string
   *   Return original or overridden label.
   */
  public function getLocationLabel($id, $default_label) {
    return $this->getMindbodyObjectLabel($id, $default_label, 'locations');
  }

  /**
   * Indicates whether session type is active or not.
   *
   * Wrapper for MindbodyObjectIsActive().
   *
   * @param int $id
   *   Session Type ID.
   *
   * @return bool
   *   Whether session type is active or not.
   */
  public function sessionTypeIsActive($id) {
    return $this->mindbodyObjectIsActive($id, 'session_types');
  }

  /**
   * Returns label for session type.
   *
   * Wrapper for getMindbodyObjectLabel().
   *
   * @param int $id
   *   Session Type ID.
   * @param string $default_label
   *   Default label.
   *
   * @return string
   *   Return original or overridden label.
   */
  public function getSessionTypeLabel($id, $default_label) {
    return $this->getMindbodyObjectLabel($id, $default_label, 'session_types');
  }

  /**
   * Returns label for MindBody object.
   *
   * @param int $id
   *   MindBody object ID.
   * @param string $default_label
   *   Default label.
   *
   * @return string
   *   Return original or overridden label.
   */
  public function getMindbodyObjectLabel($id, $default_label, $key) {
    $mapping = $this->getMappingConfig($key);

    return !empty($mapping[$id]['label']) ? $mapping[$id]['label'] : $default_label;
  }

  /**
   * Indicates whether MindBody object is active or not.
   *
   * @param int $id
   *   MindBody object ID.
   *
   * @return bool
   *   Whether MindBody object is active or not.
   */
  public function mindbodyObjectIsActive($id, $key) {
    $mapping = $this->getMappingConfig($key);

    // Display objects that are explicitly active, otherwise hide.
    return !empty($mapping[$id]['active']);
  }

  /**
   * Indicates whether MindBody object is active or not.
   *
   * @param int $id
   *   MindBody object ID.
   *
   * @return string
   *   Image name.
   */
  public function getProgramImageName($id) {
    $mapping = $this->getMappingConfig('programs');

    // Return image when explicitly specified, otherwise return default.
    return !empty($mapping[$id]['image']) ? $mapping[$id]['image'] : $this::DEFAULT_IMAGE;
  }

}
