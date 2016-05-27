<?php

namespace Drupal\ymca_groupex;
use Drupal\Core\State\StateInterface;
use Drupal\ymca_google\GcalGroupexWrapperInterface;

/**
 * Class GroupexDataFetcher.
 *
 * @package Drupal\ymca_groupex
 */
class GroupexDataFetcher implements GroupexDataFetcherInterface {

  use GroupexRequestTrait;

  /**
   * The name of key to store schedule.
   */
  const SCHEDULE_KEY = 'ymca_google_syncer_schedule';

  /**
   * Debug mode.
   *
   * @var bool
   */
  public $debug;

  /**
   * Number steps.
   *
   * @var int
   */
  protected $steps = 2;

  /**
   * Step length.
   *
   * @var int
   */
  protected $length = 3600;

  /**
   * Data wrapper.
   *
   * @var GcalGroupexWrapperInterface
   */
  protected $dataWrapper;

  /**
   * State.
   *
   * @var StateInterface
   */
  protected $state;

  /**
   * Schedule.
   *
   * @var array
   */
  private $schedule = [];

  /**
   * GroupexDataFetcher constructor.
   *
   * @param GcalGroupexWrapperInterface $data_wrapper
   *   Data wrapper.
   * @param StateInterface $state
   *   State.
   */
  public function __construct(GcalGroupexWrapperInterface $data_wrapper, StateInterface $state) {
    $this->dataWrapper = $data_wrapper;
    $this->state = $state;
    $this->schedule = $this->getSchedule();
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(array $args) {
    $start = $this->schedule['steps'][$this->schedule['current']]['start'];
    $end = $this->schedule['steps'][$this->schedule['current']]['end'];

    $options = [
      'query' => [
        'schedule' => TRUE,
        'desc' => 'true',
        'start' => $start,
        'end' => $end,
      ],
    ];

    $data = $this->request($options);
    if ($data) {
      if ($this->debug) {
        // Limit data by 3 items for development.
        $data = array_slice($data, 0, 3);
      }
      $this->dataWrapper->setSourceData($data);
      $this->dataWrapper->setTimeFrame([
        'start' => $start,
        'end' => $end,
      ]);
    }

    // Update schedule.
    $next = $this->schedule['current'] + 1;
    if ($next >= $this->steps) {
      // We reached the end. Build new one.
      $new_schedule = $this->buildSchedule(REQUEST_TIME);
    }
    else {
      // Update current step pointer.
      $new_schedule = $this->schedule;
      $new_schedule['current'] = $next;
    }

    // Save schedule.
    $this->state->set(self::SCHEDULE_KEY, $new_schedule);
  }

  /**
   * Get a schedule.
   *
   * @return array
   *   Schedule.
   */
  protected function getSchedule() {
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

}
