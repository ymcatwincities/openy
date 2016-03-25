<?php

namespace Drupal\ymca_frontend;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Interface YMCAMarchWinnersInterface.
 */
class YMCAMarchWinners implements YMCAMarchWinnersInterface {

  /**
   * Injected cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface;
   */
  protected $cache;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The injected cache backend for caching data.
   */
  public function __construct(CacheBackendInterface $cache) {
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getWinners() {
    $cache_key = 'ymca_march_winners';
    $cache = $this->cache->get($cache_key);
    if ($cache) {
      return $cache->data;
    }

    $winners = [
      'grand_prize' => [],
      'first_prize' => [],
      'second_prize' => [],
      'third_prize' => [],
    ];
    // Get list of winners.
    $json = file_get_contents(drupal_get_path('module', 'ymca_frontend') . '/files/winners.json');
    $list = json_decode($json);

    /* Grand prize. */
    if (!empty($list->grand_prize)) {
      foreach ($list->grand_prize as $item) {
        $winners['grand_prize'][] = [
          'name' => $this->getWinnerName($item),
          'id' => $this->getWinnerId($item),
          'location' => $this->getWinnerLocation($item),
        ];
      }
    }

    /** First Prize */
    if (!empty($list->first_prize)) {
      foreach ($list->first_prize as $item) {
        $winners['first_prize'][] = [
          'name' => $this->getWinnerName($item),
          'id' => $this->getWinnerId($item),
          'location' => $this->getWinnerLocation($item),
        ];
      }
    }

    // Get list of locations.
    $locations = $this->getLocations();

    /* Second Prize */
    if (!empty($list->second_prize)) {
      $sub_locations = [];
      $location_winners = [];
      foreach ($list->second_prize as $key => $location) {
        $sub_locations[$key] = isset($locations[$key]) ? $locations[$key]['name'] : t('No Name');
        foreach ($location as $item) {
          $location_winners[$key][] = [
            'name' => $this->getWinnerName($item),
            'id' => $this->getWinnerId($item),
          ];
        }
      }
      asort($sub_locations);
      $winners['second_prize']['locations'] = $sub_locations;
      $winners['second_prize']['winners'] = $location_winners;
    }

    /* Third prize */
    if (!empty($list->third_prize)) {
      $sub_locations = [];
      $location_winners = [];
      foreach ($list->third_prize as $key => $location) {
        $sub_locations[$key] = isset($locations[$key]) ? $locations[$key]['name'] : t('No Name');
        foreach ($location as $item) {
          $location_winners[$key][] = [
            'name' => $this->getWinnerName($item),
            'id' => $this->getWinnerId($item),
          ];
        }
      }
      asort($sub_locations);
      $winners['third_prize']['locations'] = $sub_locations;
      $winners['third_prize']['winners'] = $location_winners;
    }

    $this->cache->set($cache_key, $winners);

    return $winners;
  }

  /**
   * Get winner name.
   *
   * @param \stdClass $item
   *   Information about winner.
   *
   * @return string
   *   Winner name.
   */
  public function getWinnerName(\stdClass $item) {
    $name = '';
    if (!empty($item->first_name)) {
      $name .= ucfirst($item->first_name);
    }
    if (!empty($item->last_name)) {
      $name .= ' ' . substr($item->last_name, 0, 1) . '.';
    }
    if (empty($name)) {
      $name = t('No name');
    }
    return $name;
  }

  /**
   * Get winner id.
   *
   * @param \stdClass $item
   *   Information about winner.
   *
   * @return string
   *   Winner id.
   */
  public function getWinnerId(\stdClass $item) {

    return substr($item->membership_id, -4, 4);
  }

  /**
   * Get winner location name.
   *
   * @param \stdClass $item
   *   Information about winner.
   *
   * @return string
   *   Location name.
   */
  public function getWinnerLocation(\stdClass $item) {
    // Get list of locations.
    $locations = $this->getLocations();
    return isset($locations[$item->usr_branch]) ? $locations[$item->usr_branch]['name'] : t('No name');
  }

  /**
   * Get list of locations.
   *
   * @return array
   *   List of locations.
   */
  public function getLocations() {
    static $locations;
    if (!empty($locations)) {
      return $locations;
    }
    $config_locations = \Drupal::config('ymca_frontend.locations')
      ->getRawData();
    $locations = [];
    foreach ($config_locations as $item) {
      if (!empty($item['personify_brcode'])) {
        $locations[$item['personify_brcode']] = $item;
      }
    }
    return $locations;
  }

}
