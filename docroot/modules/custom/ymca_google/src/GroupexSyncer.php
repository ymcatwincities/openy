<?php

namespace Drupal\ymca_google;

use Drupal\Core\State\StateInterface;
use Drupal\ymca_groupex\GroupexDataFetcherInterface;

/**
 * Class GroupexSyncer.
 *
 * @package Drupal\ymca_google
 */
class GroupexSyncer implements GroupexSyncerInterface {

  /**
   * The name of key to store schedule.
   */
  const scheduleKey = 'ymca_google_syncer_schedule';

  /**
   * State.
   *
   * @var StateInterface
   */
  protected $state;

  /**
   * Groupex data fetcher.
   *
   * @var GroupexDataFetcherInterface
   */
  protected $fetcher;


  /**
   * Default interval (week).
   *
   * @var int
   */
  protected $interval = 604800;

  /**
   * Default length (weeks).
   *
   * @var int
   */
  protected $length = 3;

  /**
   * Schedule.
   *
   * @var array
   */
  private $schedule = [];

  /**
   * GroupexSyncer constructor.
   *
   * @param StateInterface $state
   *   State.
   * @param GroupexDataFetcherInterface $fetcher
   *   Groupex data fetcher.
   */
  public function __construct(StateInterface $state, GroupexDataFetcherInterface $fetcher) {
    $this->state = $state;
    $this->fetcher = $fetcher;

    $this->schedule = $this->getSchedule();
  }

  /**
   * {@inheritdoc}
   */
  public function sync() {
    // Fetch Groupex data.
    $data = $this->fetcher->fetch(
      $this->schedule['steps'][$this->schedule['current']]['start'],
      $this->schedule['steps'][$this->schedule['current']]['end']
    );

    // @todo Save data here...

    // Update schedule.
    $next = $this->schedule['current'] + 1;
    if ($next > $this->length) {
      // We reached the end. Build new one.
      $new_schedule = $this->buildSchedule(REQUEST_TIME);
    }
    else {
      // Update current step pointer.
      $new_schedule = $this->schedule;
      $new_schedule['current'] = $next;
    }

    // Save schedule.
    $this->state->set(self::scheduleKey, $new_schedule);
  }

  /**
   * Get a schedule.
   * @return array
   */
  protected function getSchedule() {
    if (!$schedule = $this->state->get(self::scheduleKey)) {
      $schedule = $this->buildSchedule(REQUEST_TIME);
      $this->state->set(self::scheduleKey, $schedule);
    }

    $this->schedule = $schedule;
    return $this->schedule;
  }

  /**
   * Build schedule.
   *
   * @param null $start
   *   Start timestamp.
   *
   * @return array
   *   Schedule.
   */
  protected function buildSchedule($start = NULL) {
    if (is_null($start)) {
      $start = REQUEST_TIME;
    }

    $schedule = [
      'steps' => [],
      'current' => 0,
    ];

    for ($i = 0; $i < $this->length; $i++) {
      if ($i == 0) {
        $schedule['steps'][$i]['start'] = REQUEST_TIME;
      }
      else {
        $schedule['steps'][$i]['start'] = $schedule['steps'][$i - 1]['end'];
      }
      $schedule['steps'][$i]['end'] = $schedule['steps'][$i]['start'] + $this->interval;
    }

    return $schedule;
  }

}
