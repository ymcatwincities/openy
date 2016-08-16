<?php

namespace Drupal\ymca_mindbody;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\State\State;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class YmcaMindbodyRequestGuard.
 *
 * @package Drupal\ymca_mindbody
 */
class YmcaMindbodyRequestGuard implements YmcaMindbodyRequestGuardInterface, ContainerInjectionInterface {

  /**
   * State definition.
   *
   * @var State
   */
  protected $state;

  /**
   * Config Factory definition.
   *
   * @var ConfigFactory
   */
  protected $configFactory;

  /**
   * Mindbody Proxy.
   *
   * @var MindbodyCacheProxyInterface
   */
  protected $proxy;

  /**
   * Training mapping service.
   *
   * @var YmcaMindbodyTrainingsMapping
   */
  protected $trainingsMapping;

  /**
   * Constructor.
   *
   * @param State $state
   *   The State Storage.
   * @param ConfigFactory $config_factory
   *   The Config Factory.
   * @param MindbodyCacheProxyInterface $proxy
   *   The Mindbody Cache Proxy.
   * @param YmcaMindbodyTrainingsMapping $trainings_mapping
   *   The Mindbody Training Mapping.
   */
  public function __construct(State $state, ConfigFactory $config_factory, MindbodyCacheProxyInterface $proxy, YmcaMindbodyTrainingsMapping $trainings_mapping) {
    $this->state = $state;
    $this->configFactory = $config_factory;
    $this->proxy = $proxy;
    $this->trainingsMapping = $trainings_mapping;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('config.factory'),
      $container->get('mindbody_cache_proxy.client'),
      $container->get('ymca_mindbody.trainings_mapping')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function status() {
    $cache_state = $this->state->get('mindbody_cache_proxy');
    $settings = $this->configFactory->get('ymca_mindbody.settings');
    if (isset($cache_state->miss) && $cache_state->miss >= $settings->get('max_requests')) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSearchCriteria(array $criteria) {
    $criteria += [
      'mb_location' => 0,
      'mb_program' => 0,
      'mb_session_type' => 0,
      'mb_trainer' => 0,
    ];

    if ($criteria['mb_location'] && !$this->validateLocation($criteria['mb_location'])) {
      return FALSE;
    }

    if ($criteria['mb_program'] && !$this->validateProgram($criteria['mb_program'])) {
      return FALSE;
    }

    if ($criteria['mb_session_type'] && !$this->validateSessionType($criteria['mb_session_type'], $criteria['mb_program'])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validates Mindbody Location ID.
   *
   * @param int $location_id
   *   Mindbody Location ID.
   *
   * @return bool
   *   TRUE if Location ID is correct.
   */
  private function validateLocation($location_id) {
    $valid = FALSE;

    $locations = $this->proxy->call('SiteService', 'GetLocations');
    foreach ($locations->GetLocationsResult->Locations->Location as $location) {
      if ($location->ID == $location_id) {
        $valid = TRUE;
        break;
      };
    }

    if (!$valid) {
      return FALSE;
    }

    $mapping_id = \Drupal::entityQuery('mapping')
      ->condition('field_mindbody_id', $location_id)
      ->execute();

    if (empty($mapping_id)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validates Mindbody Program ID.
   *
   * @param int $program_id
   *   Mindbody Program ID.
   *
   * @return bool
   *   TRUE if Program ID is correct.
   */
  private function validateProgram($program_id) {
    $programs = $this->proxy->call('SiteService', 'GetPrograms', [
      'OnlineOnly' => FALSE,
      'ScheduleType' => 'Appointment',
    ]);

    foreach ($programs->GetProgramsResult->Programs->Program as $program) {
      if ($program_id == $program->ID && $this->trainingsMapping->programIsActive($program->ID)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Validates Session Type ID.
   *
   * @param int $session_type_id
   *   Mindbody Session Type ID.
   * @param int $program_id
   *   Mindbody Program ID.
   *
   * @return bool
   *   TRUE if Session Type ID is correct.
   */
  private function validateSessionType($session_type_id, $program_id) {
    $session_types = $this->proxy->call('SiteService', 'GetSessionTypes', [
      'OnlineOnly' => FALSE,
      'ProgramIDs' => [$program_id],
    ]);

    foreach ($session_types->GetSessionTypesResult->SessionTypes->SessionType as $type) {
      if ($session_type_id == $type->ID && $this->trainingsMapping->sessionTypeIsActive($type->ID)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
