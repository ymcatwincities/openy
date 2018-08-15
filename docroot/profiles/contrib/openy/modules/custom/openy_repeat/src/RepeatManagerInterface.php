<?php

namespace Drupal\openy_repeat;

use Drupal\node\NodeInterface;
use Drupal\openy_repeat_entity\Entity\Repeat;

/**
 * Interface RepeatManagerInterface.
 *
 * @package Drupal\openy_repeat
 */
interface RepeatManagerInterface {

  /**
   * Reset the cache.
   */
  public function resetCache();

  /**
   * Retrieves Repeat entities.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The session node.
   *
   * @return array
   *   An array of Repeat entities.
   */
  public function getSessionInstancesBySession(NodeInterface $node);

  /**
   * Deletes Repeat entities.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The session node.
   *
   * @return int
   *   A number of deleted Repeat entities.
   */
  public function deleteSessionInstancesBySession(NodeInterface $node);

  /**
   * Extracts session references data.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The session node.
   *
   * @return array|void
   *   An associative array of session references data.
   */
  public function getSessionData(NodeInterface $node);

  /**
   * Calculates Repeat entities timestamps using session_schedule.
   *
   * @param array $session_schedule
   *   Array of session schedule.
   * @param int|null $timestamp
   *   The earliest allowed Repeat entities.
   *
   * @return array
   *   Array of timestamps
   */
  public static function calcSessionInstancesBySchedule(array $session_schedule, $timestamp = NULL);

  /**
   * Fetches sessions schedule.
   *
   * @param NodeInterface $node
   *   The session node.
   *
   * @return array
   *   An array representing the session schedule.
   */
  public static function loadSessionSchedule(NodeInterface $node);

  /**
   * Calculates Repeat entities timestamps for the session.
   *
   * @param NodeInterface $node
   *   The session node.
   * @param int|null $timestamp
   *   The earliest allowed Repeat entities.
   *
   * @return array
   *   Array of timestamps
   */
  public static function calcSessionInstancesBySession(NodeInterface $node, $timestamp = NULL);

  /**
   * Recreates Repeat entities if required.
   *
   * Takes the content moderation state into account.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The session node.
   */
  public function recreateSessionInstances(NodeInterface $node);

  /**
   * Retrieves closest upcoming Repeat entities by Session.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The session node.
   * @param int $from
   *   The earliest possible occurrence.
   * @param int $to
   *   The latest possible occurrence.
   *
   * @return Repeat|null
   *   The Repeat entities or null.
   */
  public function getClosestUpcomingSessionInstanceBySession(NodeInterface $node, $from = NULL, $to = NULL);

  /**
   * Retrieves Repeat entities by provided parameters.
   *
   * @param array $conditions
   *   Associative array of conditions.
   *
   * @return array
   *   Retrieved Repeat entities.
   */
  public function getSessionInstancesByParams(array $conditions);

  /**
   * Retrieves Sessions by provided parameters.
   *
   * @param array $conditions
   *   Associative array of conditions.
   *
   * @return array
   *   Retrieved Sessions.
   */
  public function getSessionsByParams(array $conditions);

  /**
   * Retrieves Class Sessions for the Camp CT node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Camp node.
   *
   * @param array $conditions
   *   Array of key (field) value pairs.
   *
   * @return array
   *   Array of Repeat entities.
   */
  public function getSessionInstancesByClassNode(NodeInterface $node, $conditions = []);

  /**
   * Retrieves locations for the class which have upcoming Repeat entities.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Camp node.
   *
   * @return array
   *   Array of branch/camp nodes.
   */
  public function getLocationsByClassNode(NodeInterface $node);

  /**
   * Retrieves location count for the class which have upcoming Repeat entities.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Camp node.
   *
   * @return int
   *   Integer count.
   */
  public function getLocationCountByClassNode(NodeInterface $node);

}
