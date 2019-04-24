<?php

namespace Drupal\openy_group_schedules;

use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactory;

/**
 * GroupEx Pro helper.
 *
 * @package Drupal\openy_group_schedules.
 */
class GroupexHelper {

  use GroupexRequestTrait;

  /**
   * PDF print uri.
   */
  const PRINT_URI = 'https://www.groupexpro.com/schedule/print.php';

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The Config Factory.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Get PDF link to location schedule.
   *
   * @param int $location
   *   Location ID.
   * @param int|bool $timestamp
   *   Timestamp.
   * @param int|bool $category
   *   Category.
   *
   * @return \Drupal\Core\Url
   *   Link.
   */
  public function getPdfLink($location, $timestamp = FALSE, $category = FALSE) {
    $account = $this->configFactory->get('openy_group_schedules.settings')->get('account_id');
    $query = [
      'font' => 'larger',
      'account' => $account,
      'l' => $location,
    ];

    if ($timestamp) {
      $query['week'] = strtotime('Monday this week', $timestamp);
    }

    if ($category) {
      $query['c'] = $category;
    }

    return Url::fromUri(self::PRINT_URI, ['query' => $query]);
  }

}
