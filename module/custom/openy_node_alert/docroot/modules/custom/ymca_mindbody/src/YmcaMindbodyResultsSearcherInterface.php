<?php

namespace Drupal\ymca_mindbody;

use Drupal\node\NodeInterface;

/**
 * Interface YmcaMindbodyResultsSearcherInterface.
 *
 * @package Drupal\ymca_mindbody
 */
interface YmcaMindbodyResultsSearcherInterface {

  /**
   * Retrieves search results.
   *
   * @param array $criteria
   *   Associative array of search criteria.
   * @param mixed $node
   *   A location node object or null.
   *
   * @return array
   *   Render array.
   */
  public function getSearchResults(array $criteria, $node);

  /**
   * Retrieves date range representation for strtotime function.
   *
   * @param string $value
   *   Date range element value.
   *
   * @return string
   *   Representation of the date range value, usable with strtotime function.
   */
  public static function getRangeStrtotime($value);

  /**
   * Returns search link based on context.
   *
   * @param array $options
   *   Array of options.
   * @param mixed $node
   *   Location node object or NULL.
   *
   * @return \Drupal\Core\Url
   *   Route object.
   */
  public static function getSearchLink($options, $node);

  /**
   * Retrievs location options to be used in form element.
   *
   * @return array
   *   Array of locations usable in #options attribute of form elements.
   */
  public function getLocations();

  /**
   * Retrieves program options to be used in form element.
   *
   * @return array
   *   Array of programs usable in #options attribute of form elements.
   */
  public function getPrograms();

  /**
   * Retrieves session types options to be used in form element.
   *
   * @param int $program_id
   *   MindBody program id.
   *
   * @return array
   *   Array of session types usable in #options attribute of form elements.
   */
  public function getSessionTypes($program_id);

  /**
   * Retrieves trainer options to be used in form element.
   *
   * @param int $session_type_id
   *   MindBody session type id.
   * @param int $location_id
   *   MindBody location id.
   *
   * @return array
   *   Array of trainers usable in #options attribute of form elements.
   */
  public function getTrainers($session_type_id, $location_id);

  /**
   * Retrieves trainer name form mapping.
   *
   * @param int $trainer
   *   MindBody trainer id.
   *
   * @return string
   *   Trainer's name.
   */
  public function getTrainerName($trainer);

  /**
   * Retrieves available time options.
   *
   * @return array
   *   Array of time options to be used in form element.
   */
  public static function getTimeOptions();

  /**
   * Provides markup for disabled form.
   *
   * @return array
   *   Render array for disabled form.
   */
  public function getDisabledMarkup();

  /**
   * Retrieves duration for specific session type.
   *
   * @param int $session_type
   *   Session type ID.
   *
   * @return int
   *   Duration in minutes.
   */
  public function getDuration($session_type);

}
