<?php

namespace Drupal\openy_prgf_class_sessions;

use Drupal\node\NodeInterface;
use Drupal\openy_session_instance\Entity\SessionInstance;

/**
 * Class ClassSessionsService.
 *
 * @package Drupal\openy_prgf_class_sessions
 */
interface ClassSessionsServiceInterface {

  /**
   * Retrieves Class Sessions for class nodes.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The class node.
   *
   * @param array $conditions
   *   Array of key (field) value pairs.
   *
   * @return array
   *   Array of Drupal\openy_session_instance\Entity\SessionInstance.
   */
  public function getClassNodeSessionInstances(NodeInterface $node, $conditions = []);

  /**
   * Retrieves Session Instances rows.
   *
   * @param array $session_instances
   *   Array of \Drupal\openy_session_instance\Entity\SessionInstance.
   *
   * @param $tags string[]
   *   Cache tags array.
   *
   * @return array
   *   Session Instance rows for class_sessions theme.
   */
  public function getSessionInstancesRows($session_instances, &$tags);

  /**
   * Build render array of registation link.
   *
   * @param $session \Drupal\node\Entity\Node
   *
   * @return null|Link
   */
  public function getSessionRegistrationLink($session);

  /**
   * Get session online value.
   *
   * @param $session \Drupal\node\Entity\Node
   *
   * @return bool
   */
  public function getSessionOnlineRegistration($session);

  /**
   * Get session ticket value.
   *
   * @param $session \Drupal\node\Entity\Node
   *
   * @return bool
   */
  public function getSessionTicketRequired($session);

  /**
   * Get in membership value.
   *
   * @param $session \Drupal\node\Entity\Node
   *
   * @return bool
   */
  public function getSessionInMembership($session);

}
