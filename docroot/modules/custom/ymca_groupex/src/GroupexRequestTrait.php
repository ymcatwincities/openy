<?php

namespace Drupal\ymca_groupex;

/**
 * Class GroupexRequestTrait.
 *
 * @package Drupal\ymca_groupex.
 */
trait GroupexRequestTrait {

  /**
   * Uri to make requests.
   *
   * @var string
   */
  public static $uri = 'http://api.groupexpro.com/schedule/embed';

  /**
   * Account ID.
   *
   * @var int
   */
  public static $account = 3;

  /**
   * Format of date filter.
   *
   * @var string
   */
  public static $dateFilterFormat = 'm/d/y';

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
   *
   * @return array
   *   Data.
   */
  protected function request($options) {
    $client = \Drupal::httpClient();
    $data = [];
    $options_defaults = [
      'query' => [
        'a' => GroupexRequestTrait::$account,
      ],
    ];

    try {
      $response = $client->request('GET', GroupexRequestTrait::$uri, array_merge_recursive($options_defaults, $options));
      $body = $response->getBody();
      $data = json_decode($body->getContents());
    }
    catch (\Exception $e) {
      watchdog_exception('ymca_groupex', $e);
    }

    return $data;
  }

}
