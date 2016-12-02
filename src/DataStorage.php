<?php

namespace Drupal\ygh_programs_search;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\daxko\DaxkoClientInterface;

/**
 * Class DataStorage.
 */
class DataStorage implements DataStorageInterface {

  /**
   * Daxko client.
   *
   * @var \Drupal\daxko\DaxkoClientInterface
   */
  protected $client;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * DataStorage constructor.
   *
   * @param \Drupal\daxko\DaxkoClientInterface $client
   *   Daxko client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend.
   */
  public function __construct(DaxkoClientInterface $client, CacheBackendInterface $cache) {
    $this->client = $client;
    $this->cache = $cache;
  }

  /**
   * Get locations.
   *
   * @return array
   *   Locations.
   */
  public function getLocations() {
    $locations = [];

    $cid = 'ygh_programs_search_get_locations';
    if ($cache = $this->cache->get($cid)) {
      $locations = $cache->data;
    }
    else {
      $branches = $this->client->getBranches(['limit' => 100]);
      foreach ($branches as $branch) {
        $locations[$branch->id] = $branch->name;
      }
      $this->cache->set($cid, $locations);
    }

    return $locations;
  }

  /**
   * Get programs by Location ID.
   *
   * @param int $location_id
   *   Location ID.
   *
   * @return array
   *   List of programs.
   */
  public function getProgramsByLocation($location_id) {
    $programs = [];

    $cid = 'ygh_programs_search_get_programs_by_location_' . $location_id;
    if ($cache = $this->cache->get($cid)) {
      $programs = $cache->data;
    }
    else {
      $items = $this->client->getSessions(['branch' => $location_id]);
      foreach ($items as $program) {
        $programs[$program->id] = $program->programName;
      }
      $this->cache->set($cid, $programs);
    }

    return $programs;
  }

  /**
   * Get registration link By Program.
   *
   * @param int $program_id
   *   Program ID.
   *
   * @return string
   *   Registration link.
   */
  public function getRegistrationLinkByProgram($program_id) {
    // @todo Implement this.
    return 'LINK';
  }

}
