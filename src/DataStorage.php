<?php

namespace Drupal\ygh_programs_search;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Url;
use Drupal\daxko\DaxkoClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

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
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $http;

  /**
   * The crawler.
   *
   * @var \Symfony\Component\DomCrawler\Crawler
   */
  protected $crawler;

  /**
   * DataStorage constructor.
   *
   * @param \Drupal\daxko\DaxkoClientInterface $client
   *   Daxko client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend.
   * @param \GuzzleHttp\Client $http
   *   The http client.
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The crawler.
   */
  public function __construct(DaxkoClientInterface $client, CacheBackendInterface $cache, Client $http, Crawler $crawler) {
    $this->client = $client;
    $this->cache = $cache;
    $this->http = $http;
    $this->crawler = $crawler;
  }

  /**
   * Get schools by location.
   *
   * @param int $id
   *   Location ID.
   *
   * @return array
   *   A list of schools.
   */
  public function getSchoolsByLocation($id) {
    $items = [
      '1' => 'School #1',
      '2' => 'School #2',
      '3' => 'School #3',
    ];

    return $items;
  }

  /**
   * Get programs by school.
   *
   * @param int $id
   *   School ID.
   *
   * @return array
   *   A list of programs.
   */
  public function getProgramsBySchool($id) {
    $items = [
      '1' => 'Program #1',
      '2' => 'Program #2',
      '3' => 'Program #3',
    ];

    return $items;
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
    $data = [];

    $cid = 'ygh_programs_search_get_programs_by_location_' . $location_id;
    if ($cache = $this->cache->get($cid)) {
      $data = $cache->data;
    }
    else {
      $data = $this->client->getPrograms(['branch' => $location_id]);
      $this->cache->set($cid, $data);
    }

    return $data;
  }

  /**
   * Get sessions by Program ID & Location ID.
   *
   * @param int $program_id
   *   Program ID.
   * @param int $location_id
   *   Location ID.
   *
   * @return array
   *   List of sessions.
   */
  public function getSessionsByProgramAndLocation($program_id, $location_id) {
    $data = [];

    $cid = 'ygh_programs_search_get_sessions_by_program_and_location_' . $program_id . '_' . $location_id;
    if ($cache = $this->cache->get($cid)) {
      $data = $cache->data;
    }
    else {
      $data = $this->client->getSessions(
        [
          'program' => $program_id,
          'branch' => $location_id,
        ]
      );
      $this->cache->set($cid, $data);
    }

    return $data;
  }

  /**
   * Get registration link.
   *
   * @param int $program_id
   *   Program ID.
   * @param int $session_id
   *   Session ID.
   *
   * @return string
   *   Registration link.
   */
  public function getRegistrationLink($program_id, $session_id) {
    $uri = 'https://operations.daxko.com/Online/4003/Programs/Search.mvc/details';

    $query = [
      'program_id' => $program_id,
      'session_ids' => $session_id,
    ];

    $path = Url::fromUri(
      $uri,
      [
        'query' => $query,
        'absolute' => TRUE,
        'https' => TRUE,
      ]
    );

    $link = \Drupal::l('link', $path);
    return $link;
  }

  /**
   * Get child registration link.
   *
   * @param int $school_id
   *   School ID.
   * @param int $program_id
   *   Program ID.
   *
   * @return string
   *   Registration link.
   */
  public function getChildRegistrationLink($school_id, $program_id) {
    return 'link';
  }

  /**
   * Get custom location stem mapping.
   *
   * The function was used by legacy code.
   *
   * @param array $locations
   *   Array of location IDs.
   *
   * @return array
   *   Mapping.
   */
  protected function customLocationStem(array $locations) {
    $locexp = array();

    foreach ($locations as $k => $location) {
      $locexp[$k] = explode(' ', trim($location));
      if (count($locexp[$k]) == 1) {
        $locexp[$k] = explode('-', trim($location));
      }
      $initial = array();
      foreach ($locexp[$k] as $exp) {
        $initial[] = substr($exp, 0, 1);
      }
      if (count($initial) > 1) {
        $locexp[$k][] = implode('', $initial);
      }
      if (count($initial) > 2) {
        array_pop($initial);
        $locexp[$k][] = implode('', $initial);
      }
    }

    return $locexp;
  }

  /**
   * Return array of location names (stripped).
   *
   * The function was used by legacy code.
   *
   * @return array
   *   List of locations.
   */
  protected function getDaxkoLocationMap() {
    $cid = 'ygh_programs_search_get_daxko_location_map';
    if ($cache = $this->cache->get($cid)) {
      $data = $cache->data;
    }
    else {
      $locations_data = $branches = $this->client->getBranches(['limit' => 100]);;
      $data = [];
      // @todo Make this configurable.
      // @todo Should we really skip that?
      $skip_branches = [
        107,
        93,
        117,
        129,
        169,
        330,
        333,
        329,
        332,
        331,
        335,
        362,
      ];

      foreach ($locations_data as $location) {
        if (in_array($location->id, $skip_branches)) {
          continue;
        }
        $name = str_replace('Family YMCA', '', $location->name);
        $name = str_replace('YMCA', '', $name);
        $name = str_replace('@6800', '', $name);
        $name = trim($name);
        $data[$location->id] = $name;
      }

      asort($data);
      $this->cache->set($cid, $data);
    }

    return $data;
  }

  /**
   * Get Location IDs by Childcare Program ID.
   *
   * @param int $program_id
   *   Program ID.
   *
   * @return array
   *   List of location IDs.
   */
  public function getLocationsByChildCareProgramId($program_id) {
    $programMap = [];

    $cid = 'ygh_' . __METHOD__;
    if ($cache = $this->cache->get($cid)) {
      $programMap = $cache->data;
    }
    else {
      $locations = $this->getDaxkoLocationMap();
      $locsr = array_flip($locations);
      $locexp = $this->customLocationStem($locations);

      $programs = $this->client->getChildCarePrograms();
      foreach ($programs as $program) {
        $loc = strstr($program->name, ' ', TRUE);

        if (isset($locsr[$loc])) {
          $programMap[$program->id][$locsr[$loc]] = $locsr[$loc];
        }
        else {
          $ranking = array();
          foreach ($locexp as $k => $exps) {

            $ranking[$k] = 0;
            if (substr($program->name, 0, 1) == substr($exps[0], 0, 1)) {
              if ((strpos($program->name, "South Lake Houston") !== FALSE)) {
                $ranking[$k] += -10;
              }
              $ranking[$k] += .25;
            }

            foreach ($exps as $exp) {
              if (!empty($exp)) {
                $smatch = strpos($program->name, $exp);
                if ($smatch !== FALSE) {
                  $ranking[$k] += 2 / ($smatch + 1);
                }
              }
            }
          }

          asort($ranking);
          $ranking = array_reverse($ranking, TRUE);
          $top_rank = -1;

          foreach ($ranking as $lid => $rank) {
            if ($top_rank == -1 || $top_rank == $rank) {
              $top_rank = $rank;
              $programMap[$program->id][$lid] = $lid;
            }
          }
        }
      }

      $this->cache->set($cid, $programMap);
    }

    return $programMap[$program_id];
  }

  /**
   * Get schools by ChildCare program ID.
   *
   * @param int $program_id
   *   Program ID.
   *
   * @return array
   *   Array of schools and IDs.
   */
  public function getSchoolsByChildCareProgramId($program_id) {
    $cid = 'ygh_' . __METHOD__ . $program_id;
    if ($cache = $this->cache->get($cid)) {
      $data = $cache->data;
    }
    else {
      $link = 'https://operations.daxko.com/Online/4003/Programs/ChildCareSearch.mvc/locations_by_program?program_id=' . $program_id;
      $source = $this->getDaxkoPageSource($link);

      $this->crawler->clear();
      $this->crawler->addHtmlContent($source);
      $items = $this->crawler->filter('div.two-column-container ul li a');
      $data = $items->each(function ($item) {
        // Get Location ID from href.
        $keys = [];
        $parse = parse_url($item->attr('href'));
        parse_str($parse['query'], $keys);

        return [
          'name' => $item->text(),
          'id' => $keys['location_id'],
        ];
      });


      $this->cache->set($cid, $data);
    }

    return $data;
  }

  /**
   * Get page source for Daxko html page.
   *
   * @param string $url
   *   Url of the page.
   *
   * @return string
   *   Page source.
   */
  protected function getDaxkoPageSource($url) {
    $contents = '';

    // @todo Add try/catch.
    $options = ['allow_redirects' => FALSE];
    $res = $this->http->request('GET', $url, $options);
    $cookies = $res->getHeader('Set-Cookie');

    $final = [];
    $domain = '.daxko.com';
    foreach ($cookies as $cookie) {
      $parts = explode(';', $cookie);
      foreach ($parts as $part) {
        $parts2 = explode('=', $part);
        if (!empty($parts2[1])) {
          $final[trim($parts2[0])] = trim($parts2[1]);
          if (strtolower($parts2[0]) == 'domain') {
            $domain = $parts2[1];
          }
        }
      }
    }

    $jar = new CookieJar();
    foreach ($final as $key => $value) {
      if (strtolower($key) != 'domain') {
        $setcookie = new SetCookie();
        $setcookie->setName($key);
        $setcookie->setValue($value);
        $setcookie->setDomain($domain);
        $jar->setCookie($setcookie);
      }
    }

    // @todo Add try/catch.
    $options = ['allow_redirects' => FALSE, 'cookies' => $jar];
    $res2 = $this->http->request('GET', $url, $options);
    $contents = $res2->getBody()->getContents();

    return $contents;
  }

}
