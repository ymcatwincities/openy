<?php

namespace Drupal\openy_group_schedules;

/**
 * Class GroupexRequestTrait.
 *
 * @package Drupal\openy_group_schedules.
 */
trait GroupexRequestTrait {

  /**
   * Uri to make requests.
   *
   * @var string
   */
  public static $uri = 'http://api.groupexpro.com/schedule/embed';

  /**
   * Format of date filter.
   *
   * @var string
   */
  public static $dateFilterFormat = 'm/d/y';

  /**
   * Format for full date.
   *
   * @var string
   */
  public static $dateFullFormat = 'l, F j, Y';

  /**
   * Groupex ID garbage.
   *
   * @var string
   */
  public static $idStrip = 'DESC--[';

  /**
   * Morning time.
   *
   * @var int
   */
  public static $timeMorning = 6;

  /**
   * Afternoon time.
   *
   * @var int
   */
  public static $timeAfternoon = 12;

  /**
   * Evening time.
   *
   * @var int
   */
  public static $timeEvening = 17;

  /**
   * Make a request to GroupEx.
   *
   * @param array $options
   *   Request options.
   * @param bool $defaults
   *   TRUE includes default options. FALSE will not alter options.
   *
   * @return array|null
   *   Data, NULL on failure.
   */
  protected function request(array $options, $defaults = TRUE) {
    $status = \Drupal::config('groupex_form_cache.settings')->get('status');

    $all_options = $options;
    if ($defaults) {
      $all_options = array_merge_recursive($this->getDefaultOptions(), $options);
    }

    // Try to use cached data.
    if ($status == TRUE) {
      $manager = \Drupal::service('groupex_form_cache.manager');
      if ($data = $manager->getCache($all_options)) {
        return $data;
      }
    }

    try {
      $response = \Drupal::httpClient()->request('GET', GroupexRequestTrait::$uri, $all_options);
      $body = $response->getBody();
      $data = json_decode($body->getContents());

      if ($status == TRUE && !empty($manager)) {
        $manager->setCache($all_options, $data);
      }

      return $data;
    }
    catch (\Exception $e) {
      watchdog_exception('openy_group_schedules', $e);
      return NULL;
    }
  }

  /**
   * Return required defaults parameters for the request.
   *
   * @return array
   *   Options.
   */
  protected function getDefaultOptions() {
    $account = \Drupal::service('config.factory')->get('openy_group_schedules.settings')->get('account_id');
    return [
      'query' => [
        'a' => $account,
      ],
    ];
  }

}
