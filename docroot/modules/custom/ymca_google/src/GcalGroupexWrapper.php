<?php

namespace Drupal\ymca_google;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Class GcalGroupexWrapper.
 *
 * @package Drupal\ymca_google
 */
class GcalGroupexWrapper implements GcalGroupexWrapperInterface {

  /**
   * The name of key to store schedule.
   */
  const SCHEDULE_KEY = 'ymca_google_syncer_schedule';

  /**
   * Logger channel name.
   */
  const LOGGER_CHANNEL = 'gcal_groupex';

  /**
   * Amount of seconds to always fetch the nearest schedule.
   */
  const HOT_TIME_FRAME = 86400;

  /**
   * Number steps.
   *
   * @var int
   */
  private $steps = 180;

  /**
   * Step length.
   *
   * @var int
   */
  private $length = 43200;

  /**
   * Raw source data from source system.
   *
   * @var array
   */
  protected $sourceData = [];

  /**
   * Prepared data for proxy system.
   *
   * @var array
   */
  protected $proxyData = [];

  /**
   * Time frame for the data.
   *
   * @var array
   */
  protected $timeFrame = [];

  /**
   * State.
   *
   * @var StateInterface
   */
  protected $state;

  /**
   * Logger channel.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * GcalGroupexWrapper constructor.
   *
   * @param StateInterface $state
   *   State.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(StateInterface $state, LoggerChannelFactoryInterface $logger_factory) {
    $this->state = $state;
    $this->logger = $logger_factory->get(self::LOGGER_CHANNEL);

  }

  /**
   * Source data setter.
   *
   * @param array $data
   *   Source data from Groupex.
   */
  public function setSourceData(array $data) {
    $this->sourceData = $data;
  }

  /**
   * Source data getter.
   */
  public function getSourceData() {
    return $this->sourceData;
  }

  /**
   * {@inheritdoc}
   */
  public function getProxyData() {
    return $this->proxyData;
  }

  /**
   * {@inheritdoc}
   */
  public function setProxyData(array $data) {
    $this->proxyData = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimeFrame(array $frame) {
    $this->timeFrame = $frame;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeFrame() {
    return $this->timeFrame;
  }

  /**
   * {@inheritdoc}
   */
  public function next($noExceptions = TRUE) {
    $schedule = $this->getSchedule();
    $next = $schedule['current'] + 1;
    if ($next >= $this->steps) {
      // We reached the end. Build new one.
      $new_schedule = $this->buildSchedule(REQUEST_TIME);

      // Log the end of the loop.
      $context = [
        '%steps' => $this->steps,
        '%length' => $this->length,
        '%time' => 'unknown',
      ];
      if (array_key_exists('created', $schedule)) {
        $context['%time'] = REQUEST_TIME - $schedule['created'];
      }
      $this->logger->info("Loop finished. Time: %time sec., steps num: %steps, step length: %length sec.", $context);
    }
    else {
      // Update current step pointer.
      $new_schedule = $schedule;
      $new_schedule['current'] = $next;

      $context = [
        '%steps' => $this->steps,
        '%length' => $this->length,
        '%current' => $new_schedule['current'],
      ];
      $this->logger->info("Step finished. Current step: %current, steps num: %steps, step length: %length sec.", $context);
    }

    // Save schedule.
    $this->state->set(self::SCHEDULE_KEY, $new_schedule);
  }

  /**
   * {@inheritdoc}
   */
  public function getSchedule() {
    if (!$schedule = $this->state->get(self::SCHEDULE_KEY)) {
      $schedule = $this->buildSchedule(REQUEST_TIME);
      $this->state->set(self::SCHEDULE_KEY, $schedule);
    }

    return $schedule;
  }

  /**
   * Build schedule.
   *
   * @param int $start
   *   Start timestamp.
   *
   * @return array
   *   Schedule.
   */
  private function buildSchedule($start) {
    $schedule = [
      'steps' => [],
      'current' => 0,
      'created' => REQUEST_TIME,
    ];
    for ($i = 0; $i < $this->steps; $i++) {
      if ($i == 0) {
        $schedule['steps'][$i]['start'] = $start;
      }
      else {
        $schedule['steps'][$i]['start'] = $schedule['steps'][$i - 1]['end'];
      }
      $schedule['steps'][$i]['end'] = $schedule['steps'][$i]['start'] + $this->length;
    }
    return $schedule;
  }

  /**
   * Remove schedule.
   *
   * Used for resetting current schedule.
   */
  public function removeSchedule() {
    $this->state->delete(self::SCHEDULE_KEY);
  }

}
