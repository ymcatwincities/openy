<?php

namespace Drupal\ymca_frontend;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Service for calculating winners.
 */
class YMCAMarchWinners implements YMCAMarchWinnersInterface {

  /**
   * Injected cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
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
          'mail' => $this->getWinnerMail($item),
        ];
      }
    }

    /* First Prize */
    if (!empty($list->first_prize)) {
      foreach ($list->first_prize as $item) {
        $winners['first_prize'][] = [
          'name' => $this->getWinnerName($item),
          'id' => $this->getWinnerId($item),
          'location' => $this->getWinnerLocation($item),
          'mail' => $this->getWinnerMail($item),
        ];
      }
    }

    /* Second Prize */
    if (!empty($list->second_prize)) {
      foreach ($list->second_prize as $item) {
        $location = $this->getWinnerLocation($item);
        $winners['second_prize'][$location] = [
          'name' => $this->getWinnerName($item),
          'id' => $this->getWinnerId($item),
          'location' => $location,
          'mail' => $this->getWinnerMail($item),
        ];
      }
      ksort($winners['second_prize']);
    }

    // Get list of locations.
    $locations = $this->getLocations();
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
            'mail' => $this->getWinnerMail($item),
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
   * Get winner mail.
   *
   * @param \stdClass $item
   *   Information about winner.
   *
   * @return string
   *   Winner mail.
   */
  public function getWinnerMail(\stdClass $item) {
    return strtolower($item->mail);
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

  /**
   * Identify winner by email.
   *
   * @return array|bool
   *   Title and name of the prize.
   */
  public function identifyIndividualPrize() {
    if (!isset($_GET['email'])) {
      return FALSE;
    }
    $email = strtolower($_GET['email']);
    $prizes = [
      'grand_prize' => [
        'title' => t('Grand Prize'),
        'prize' => t('Suite tickets to the Minnesota Timberwolves vs Dallas Mavericks April 3, 2016'),
      ],
      'first_prize' => [
        'title' => t('First Prize'),
        'prize' => [
          t("Shabazz Muhammed<br>signed basketball"),
          t("Andrew Wiggins<br>signed jersey"),
        ],
      ],
      'second_prize' => [
        'title' => t('Second Prize'),
        'prize' => t('3 month of Membership'),
      ],
      'third_prize' => [
        'title' => t('Third Prize'),
        'prize' => t('3 months of towel service'),
      ],
    ];
    $winners = $this->getWinners();
    // Search in grand prize.
    foreach ($winners['grand_prize'] as $winner) {
      if ($email == $winner['mail']) {
        return $prizes['grand_prize'];
      }
    }

    // Search in first prize.
    foreach ($winners['first_prize'] as $key => $winner) {
      if ($email == $winner['mail']) {
        return [
          'title' => $prizes['first_prize']['title'],
          'prize' => $prizes['first_prize']['prize'][$key],
        ];
      }
    }

    // Search in second prize.
    foreach ($winners['second_prize'] as $key => $winner) {
      if ($email == $winner['mail']) {
        return $prizes['second_prize'];
      }
    }

    // Search in third prize.
    foreach ($winners['third_prize']['winners'] as $location) {
      foreach ($location as $winner) {
        if ($email == $winner['mail']) {
          return $prizes['third_prize'];
        }
      }
    }

    return FALSE;
  }

}
