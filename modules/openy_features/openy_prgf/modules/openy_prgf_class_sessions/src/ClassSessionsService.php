<?php

namespace Drupal\openy_prgf_class_sessions;

use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openy_session_instance\SessionInstanceManagerInterface;
use Drupal\openy_session_instance\Entity\SessionInstance;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class ClassSessionsService.
 *
 * @package Drupal\openy_prgf_class_sessions
 */
class ClassSessionsService implements ClassSessionsServiceInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The Session instance manager.
   *
   * @var \Drupal\openy_session_instance\SessionInstanceManagerInterface
   */
  protected $sessionInstanceManager;

  /**
   * Constructs a new ClassSessionsService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The current service container.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContainerInterface $container, SessionInstanceManagerInterface $sessionInstanceManager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->container = $container;
    $this->sessionInstanceManager = $sessionInstanceManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getClassNodeSessionInstances(NodeInterface $node, $location_id = NULL) {
    return $this->sessionInstanceManager->getSessionInstancesByClassNode($node, $location_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionInstancesRows($session_instances, &$tags) {
    $session_instances_rows = [];
    foreach ($session_instances as $session_instance) {
      /* @var $session_instance \Drupal\openy_session_instance\Entity\SessionInstance */

      // If location empty skip session instance.
      if (!is_a($location = $session_instance->getLocation(), 'Drupal\node\Entity\Node')) {
        continue;
      }

      // If session empty skip session instance.
      if (!is_a($session = $session_instance->getSession(), 'Drupal\node\Entity\Node')) {
        continue;
      }

      // Add location cache tags.
      $tags_location = $location->getCacheTags();
      $tags = Cache::mergeTags($tags, $tags_location);

      // Add session cache tags
      $tags_session = $session->getCacheTags();
      $tags = Cache::mergeTags($tags, $tags_session);

      // Get Time information.
      $timestamp = $session_instance->getTimestamp();
      $time_start = date('g:iA', $timestamp);
      $day_of_week_start = date('l', $timestamp);
      $time_to = date('g:iA', $session_instance->getTimestampTo());

      $row = [
        // Location.
        'location' => $location->label(),
        // Time range.
        'time_start' => $time_start,
        'day_of_week' => $day_of_week_start,
        'time_to' => $time_to,
        // Formatted Date.
        'formatted_date' => $session_instance->getFormattedDateRangeDate(),
        // Online registration from session field_session_online.
        'registration' => $this->getSessionOnlineRegistration($session),
        // Ticket required from session field_session_ticket.
        'ticket_required' => $this->getSessionTicketRequired($session),
        // Registration(link) from session field_session_reg_link.
        'registration_link' => $this->getSessionRegistrationLink($session),
        // In membership from session field_session_in_mbrsh.
        'in_membership' => $this->getSessionInMembership($session),
      ];

      //  Min Age
      if (!empty($session_instance->get('min_age')->value)) {
        $row['age_min'] = $session_instance->get('min_age')->value;
      }

      //  Max Age
      if (!empty($session_instance->get('max_age')->value)) {
        $row['age_max'] = $session_instance->get('max_age')->value;
      }

      $session_instances_rows[] = $row;
    }

    return $session_instances_rows;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionRegistrationLink($session) {
    if (!empty($session->field_session_reg_link->getValue())) {
      $reg_link = $session->field_session_reg_link->getValue();
      $reg_link = reset($reg_link);
      $url = Url::fromUri($reg_link['uri']);
      return Link::fromTextAndUrl($reg_link['title'], $url);
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionOnlineRegistration($session) {
    if (!empty($session->field_session_online->value)) {
      return ($session->field_session_online->value == TRUE);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionTicketRequired($session) {
    if (!empty($session->field_session_ticket->value)) {
      return ($session->field_session_ticket->value == TRUE);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionInMembership($session) {
    if (!empty($session->field_session_in_mbrsh->value)) {
      return ($session->field_session_in_mbrsh->value == TRUE);
    }

    return FALSE;
  }

}
