<?php

namespace Drupal\openy_digital_signage_schedule;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\openy_digital_signage_schedule\Entity\OpenYSchedule;
use Drupal\openy_digital_signage_screen\Entity\OpenYScreen;
use Drupal\openy_digital_signage_screen\Entity\OpenYScreenInterface;

/**
 * Class OpenYScheduleManager.
 */
class OpenYScheduleManager implements OpenYScheduleManagerInterface {

  /**
   * Logger channel definition.
   */
  const CHANNEL = 'openy_digital_signage';

  /**
   * Collection name.
   */
  const STORAGE = 'openy_digital_signage_schedule';

  /**
   * The query factory.
   *
   * @var QueryFactory
   */
  protected $entityQuery;

  /**
   * The entity type manager.
   *
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity storage.
   *
   * @var EntityStorageInterface
   */
  protected $storage;

  /**
   * LoggerChannelFactoryInterface definition.
   *
   * @var LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $entity_query, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get(self::CHANNEL);
    $this->storage = $this->entityTypeManager->getStorage(self::STORAGE);
  }

  /**
   * {@inheritdoc}
   */
  public function dummy() {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getUpcomingScreenContents(OpenYSchedule $schedule, $timespan, $now = NULL, $include_disabled = FALSE) {
    if (!$now) {
      $now = time();
    }

    $query = $this->entityQuery->get('openy_digital_signage_sch_item');
    $query->condition('schedule', $schedule->id());
    if (!$include_disabled) {
      $query->condition('status', 1);
    }
    $entity_ids = $query->execute();

    if (!$entity_ids) {
      return [];
    }

    $schedule_items = $this->entityTypeManager
      ->getStorage('openy_digital_signage_sch_item')
      ->loadMultiple($entity_ids);

    $schedule = [];
    $now_formatted = date('H:i:s', $now);
    $today = date('Y-m-d', $now);
    $tomorrow = date('Y-m-d', strtotime($today . ' +1day'));
    foreach ($schedule_items as $schedule_item) {
      if ($is_override = !$schedule_item->show_date->value) {
        $override_from = strtotime($schedule_item->get('date')->value);
        $override_to = strtotime($schedule_item->get('date')->end_value . ' + 1 day');
        if ($override_to <= $now || $override_from >= $now + $timespan) {
          continue;
        }
      }

      $from_ts = strtotime($schedule_item->get('time_slot')->value . 'z');
      $to_ts = strtotime($schedule_item->get('time_slot')->end_value . 'z');
      $from_time = date('H:i:s', $from_ts);
      $to_time = date('H:i:s', $to_ts);
      $date = $today;
      if ($to_time < $now_formatted) {
        $date = $tomorrow;
      }
      $from = $date . 'T' . $from_time;
      $to = $date . 'T' . $to_time;
      $from_ts = strtotime($from);
      $to_ts = strtotime($to);
      if ($is_override && $from_ts < $override_from) {
        $date = $tomorrow;
        $from = $date . 'T' . $from_time;
        $to = $date . 'T' . $to_time;
        $from_ts = strtotime($from);
        $to_ts = strtotime($to);
      }
      $schedule[$from] = [
        'item' => $schedule_item,
        'from' => $from,
        'to' => $to,
        'from_ts' => $from_ts,
        'to_ts' => $to_ts,
        'override' => $is_override,
      ];

    }

    ksort($schedule);

    return $schedule;
  }

  /**
   * Calculates days with overrides for the given month.
   *
   * @param \Drupal\openy_digital_signage_schedule\Entity\OpenYSchedule $schedule
   *   The schedule object.
   * @param int $year
   *   The year.
   * @param int $month
   *   The month number.
   *
   * @return array
   *   Array of dates.
   */
  public function daysWithOverrides(OpenYSchedule $schedule, $year, $month) {
    $day_interval = new \DateInterval('P1D');
    $next_year = $year;
    $next_month = $month + 1;
    if ($next_month > 12) {
      $next_month = 1;
      $next_year++;
    }
    $first_days_of_month = sprintf('%d-%02d-%02d', $year, $month, 1);
    $first_day_of_next_month = sprintf('%d-%02d-%02d', $next_year, $next_month, 1);
    $date_first = new \DateTime($first_days_of_month);
    $date_last = new \DateTime($first_day_of_next_month);
    $date_last->sub($day_interval);

    // Retrive all override schedule items that fit the given month.
    $query = $this->entityQuery->get('openy_digital_signage_sch_item');
    $query->condition('schedule', $schedule->id());
    $query->condition('show_date', 0);
    $query->condition('date__value', $first_day_of_next_month, '<');
    $query->condition('date__end_value', $first_days_of_month, '>=');
    $entity_ids = $query->execute();

    if (!$entity_ids) {
      return [];
    }

    $schedule_items = $this->entityTypeManager
      ->getStorage('openy_digital_signage_sch_item')
      ->loadMultiple($entity_ids);

    $days = [];
    foreach ($schedule_items as $schedule_item) {
      $override_from = $schedule_item->get('date')->value;
      $override_to = $schedule_item->get('date')->end_value;
      $datetime_from = new \DateTime($override_from);
      $datetime_to = new \DateTime($override_to);
      $datetime_to->add($day_interval);
      $date_period = new \DatePeriod($datetime_from, $day_interval, $datetime_to);
      foreach ($date_period as $date) {
        if ($date < $date_first || $date > $date_last) {
          continue;
        }
        $date_formatted = $date->format('Y-m-d');
        $days[$date_formatted] = $date_formatted;
      }
    }

    return $days;
  }

  /**
   * {@inheritdoc}
   */
  public function getScreenUpcomingScreenContents(OpenYScreen $screen, $timespan, $now = NULL) {
    if (!$now) {
      $now = time();
    }
    $now = $now - $now % 3600;

    $schedule = $this->getUpcomingScreenContents($screen->screen_schedule->entity, $timespan, $now);
    foreach ($schedule as &$item) {
      $item['screen_content'] = $item['item']->content->entity;
      $item['id'] = $item['item']->id();
      $item['type'] = 'schedule_item';
    }
    unset($item);

    $priority = [
      'fallback' => 0,
      'regular' => 1,
      'override' => 2,
    ];

    // Init slots with fallback content.
    $slots = [
      $now => [
        'from' => strtotime('today'),
        'to' => $now + $timespan,
        'type' => 'fallback',
        'content' => $screen->fallback_content->entity,
        'content-id' => $screen->fallback_content->entity->id(),
        'priority' => $priority['fallback'],
        'item' => [
          'from' => '12:00:00am',
          'to' => '12:00:00am',
        ],
      ],
    ];

    foreach ($schedule as $item) {
      $item_type = $item['override'] ? 'override' : 'regular';

      // Search for the intersections with existing slots.
      $intersections = [];
      foreach ($slots as $key => $slot) {
        if ($item['from_ts'] < $slot['to'] && $item['to_ts'] > $slot['from']) {
          $intersections[] = $key;
        }
      }

      // Process each intersection.
      foreach ($intersections as $intersection) {
        $slot = &$slots[$intersection];
        if ($priority[$item_type] < $slot['priority']) {
          continue;
        }
        if ($priority[$item_type] == $slot['priority'] && $item['from_ts'] < $slot['item']['from']) {
          continue;
        }

        $contains = $item['from_ts'] > $slot['from'] && $item['to_ts'] < $slot['to'];
        $covers = $item['from_ts'] <= $slot['from'] && $item['to_ts'] >= $slot['to'];

        // The existing slot contains the whole new slot.
        if ($contains) {
          // Create new slot for the rest of the existing slot.
          $slot_copy = (array) $slot;
          $slot_copy['from'] = $item['to_ts'];
          $slots[$item['to_ts']] = $slot_copy;

          // Cut down the existing slot.
          $slot['to'] = $item['from_ts'];

          // Create new slot for the override.
          $slots[$item['from_ts']] = [
            'from' => $item['from_ts'],
            'to' => $item['to_ts'],
            'type' => $item_type,
            'content' => $item['screen_content'],
            'content-id' => $item['screen_content']->id(),
            'priority' => $priority[$item_type],
            'item' => $item,
          ];
        }
        // The new slot covers the whole existing slot.
        elseif ($covers) {
          // Replace covered slot content.
          $slot['type'] = $item_type;
          $slot['content'] = $item['screen_content'];
          $slot['content-id'] = $item['screen_content']->id();
          $slot['priority'] = $priority[$item_type];
          $slot['item'] = $item;
        }
        // The slots have intersection.
        else {
          if ($slot['from'] < $item['from_ts']) {
            // Create new slot for the override.
            $slots[$item['from_ts']] = [
              'from' => $item['from_ts'],
              'to' => $slot['to'],
              'type' => $item_type,
              'content' => $item['screen_content'],
              'content-id' => $item['screen_content']->id(),
              'priority' => $priority[$item_type],
              'item' => $item,
            ];
            // Cut down the existing slot.
            $slot['to'] = $item['from_ts'];
          }
          else {
            // Create new slot for the rest of the existing slot.
            $slot_copy = (array) $slot;
            $slot_copy['from'] = $item['to_ts'];
            $slots[$item['to_ts']] = $slot_copy;
            // Create new slot for the override.
            $slots[$intersection] = [
              'from' => $slot['from'],
              'to' => $item['to_ts'],
              'type' => $item_type,
              'content' => $item['screen_content'],
              'content-id' => $item['screen_content']->id(),
              'priority' => $priority[$item_type],
              'item' => $item,
            ];
          }
        }
        unset($slot);
        ksort($slots);
      }

      // Loop thru the found slots and union equal.
      $keys = array_keys($slots);
      $key = reset($keys);
      while (TRUE) {
        $slot = &$slots[$key];

        // The next slot doesn't exists - end of the list reached.
        if (!isset($slots[$slot['to']])) {
          break;
        }

        $key = $slot['to'];

        // Fallbacks don't need to be combined.
        if ($slot['type'] == 'fallback') {
          continue;
        }

        $next_slot = $slots[$slot['to']];

        // Skip fallbacks.
        if ($next_slot['type'] == 'fallback') {
          continue;
        }

        // Compare slots.
        if ($slot['item']['id'] != $next_slot['item']['id']) {
          continue;
        }

        // Equal slots found - combine.
        $slot['to'] = $next_slot['to'];
        unset($slots[$next_slot['from']]);
        $key = $slot['to'];

        // The next slot doesn't exists - end of the list reached.
        if (!isset($slots[$key])) {
          break;
        }
      }
      unset($slot);
    }

    // Rework content-id for better changes identifications.
    foreach ($slots as &$slot) {
      $id_array = [
        $slot['type'],
        $slot['from'],
        $slot['to'],
        $slot['content-id'],
      ];
      if ($slot['type'] !== 'fallback') {
        $id_array[] = $slot['item']['id'];
      }
      $slot['content-id'] = implode(':', $id_array);
    }

    return $slots;
  }

}
