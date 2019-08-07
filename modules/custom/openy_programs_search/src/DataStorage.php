<?php

namespace Drupal\openy_programs_search;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Url;
use Drupal\daxko\DaxkoClientInterface;
use Drupal\openy_socrates\OpenyCronServiceInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class DataStorage.
 */
class DataStorage implements DataStorageInterface, OpenyCronServiceInterface {

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function runCronServices() {
    $this->resetCache();
    $this->warmCache();
  }

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
  public function __construct(DaxkoClientInterface $client, CacheBackendInterface $cache, Client $http, Crawler $crawler, ConfigFactoryInterface $config_factory) {
    $this->client = $client;
    $this->cache = $cache;
    $this->http = $http;
    $this->crawler = $crawler;
    $this->configFactory = $config_factory;
  }

  /**
   * Delete all caches.
   */
  public function resetCache() {
    $this->cache->deleteAll();
  }

  /**
   * Warm up all cache.
   *
   * @ingroup cache
   */
  public function warmCache() {
    $this->getAllChildCarePrograms();
    $this->getMapRateOptions();
    $this->getMapSchoolsProgramIds();
    $this->getMapLocationsPrograms();
  }

  /**
   * Get URL from Config openy_programs_search.settings.
   *
   * @param $name string
   *   Configuration item name.
   * @param array $token_replace
   *   Key value pair or from => to.
   *
   * @return array|mixed|string
   */
  private function getUrlFromOpenyProgramsSearchSettings($name, $token_replace = []) {
    $config = $this->configFactory->get('openy_programs_search.settings');
    if (empty($value = $config->get($name))) {
      $value = '';
    }
    $base_url = $config->get('base_url');
    $client_id = $config->get('client_id');
    $token_replace += ['{{ client_id }}' => $client_id];

    switch ($name) {
      case 'base_url':
        return $base_url;
        break;
      case 'registration_path':
      case 'get_schools_by_program_path':
      case 'get_categories_path':
      case 'get_map_categories_by_branch_path':
        return $base_url . strtr($value, $token_replace);;
        break;
    }

    return '';
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
    $schools = [];

    $cid = __METHOD__ . $id;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $programs = $this->getChildCareProgramsByLocation($id);
    foreach ($programs as $program) {
      $schools_data = $this->getSchoolsByChildCareProgramId($program);
      foreach ($schools_data as $school) {
        $schools[$school['id']] = $school['name'];
      }
    }

    $this->cache->set($cid, $schools);
    return $schools;
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
  public function getChildCareProgramsBySchool($id) {
    $map = [];

    $cid = __METHOD__;
    if ($cache = $this->cache->get($cid)) {
      $map = $cache->data;
    }
    else {
      $programs_all = $this->getAllChildCarePrograms();
      foreach ($programs_all as $program) {
        $school_data = $this->getSchoolsByChildCareProgramId($program->id);
        foreach ($school_data as $school) {
          $map[$school['id']][$program->id] = $program->name;
        }
      }

      $this->cache->set($cid, $map);
    }

    return $map[$id];
  }

  /**
   * Get locations.
   *
   * @return array
   *   Locations.
   */
  public function getLocations() {
    $locations = [];

    $cid = __METHOD__;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $branches = $this->client->getBranches(['limit' => 100]);
    foreach ($branches as $branch) {
      $locations[$branch->id] = $branch->name;
    }

    $this->cache->set($cid, $locations);
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
    $cid = __METHOD__ . $location_id;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $data = $this->client->getPrograms(['branch' => $location_id]);

    $this->cache->set($cid, $data);
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
    $cid = __METHOD__ . $program_id . $location_id;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $params = [
      'program' => $program_id,
      'branch' => $location_id,
    ];

    $data = $this->client->getSessions($params);

    $this->cache->set($cid, $data);
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
    $uri = $this->getUrlFromOpenyProgramsSearchSettings('registration_path');

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

    return $path->toString();
  }

  /**
   * Get child registration link.
   *
   * @param int $school_id
   *   School ID.
   * @param int $program_id
   *   Program ID.
   * @param int $context_id
   *   Rate option ID.
   *
   * @return string
   *   Registration link.
   */
  public function getChildCareRegistrationLink($school_id, $program_id, $context_id) {
    $domain = $this->getUrlFromOpenyProgramsSearchSettings('base_url');

    $map = $this->getMapRateOptions();
    $item = $map[$school_id][$program_id][$context_id];
    return $domain . $item['registration_url'];
  }

  /**
   * Get Location IDs by Childcare Program ID.
   *
   * @param int $program_id
   *   Program ID.
   *
   * @return array
   *   List of location IDs.
   *
   * @ingroup legacy
   */
  public function getLocationsByChildCareProgramId($program_id) {
    $programMap = [];

    $cid = __METHOD__ . $program_id;
    if ($cache = $this->cache->get($cid)) {
      $programMap = $cache->data;
    }
    else {
      $config = $this->configFactory->get('openy_programs_search.settings');
      $pinned_programs = $config->get('pinned_programs');
      $locations = $this->getDaxkoLocationMap();
      $locsr = array_flip($locations);
      $locexp = $this->customLocationStem($locations);

      $programs = $this->getAllChildCarePrograms();
      foreach ($programs as $program) {
        $loc = strstr($program->name, ' ', TRUE);

        if (isset($locsr[$loc])) {
          $programMap[$program->id][$locsr[$loc]] = $locsr[$loc];
        }
        else {
          $ranking = [];
          foreach ($locexp as $k => $exps) {

            $ranking[$k] = 0;
            if (substr($program->name, 0, 1) == substr($exps[0], 0, 1)) {
              if (($i = count($pinned_programs)) > 0) {
                foreach ($pinned_programs as $pinned_program) {
                  if ((strpos($program->name, $pinned_program) !== FALSE)) {
                    // Allow the order of the pinned programs be used as an
                    // additional weight.
                    $ranking[$k] += -10 -$i;
                  }
                  $i -= 1;
                }
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

          // Reverse sort the ranking.
          arsort($ranking);
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
    $data = [];

    $map = $this->getMapSchoolsProgramIds();
    if (isset($map[$program_id])) {
      $data = $map[$program_id];
    }

    return $data;
  }

  /**
   * Return childcare program IDs by Location.
   *
   * @param int $location_id
   *   Location ID.
   *
   * @return array
   *   List of program IDs.
   */
  public function getChildCareProgramsByLocation($location_id) {
    $data = [];

    $map = $this->getMapLocationsPrograms();
    if (isset($map[$location_id])) {
      $data = $map[$location_id];
    }

    return $data;
  }

  /**
   * Get rate options by location ID and program ID.
   *
   * @param int $school_id
   *   Location ID.
   * @param int $program_id
   *   Program ID.
   *
   * @return array
   *   List of rates for the program.
   */
  public function getChildCareProgramRateOptions($school_id, $program_id) {
    $map = $this->getMapRateOptions();
    return $map[$school_id][$program_id];
  }

  /**
   * Get all available ChildCare programs.
   *
   * @return array
   *   A list of programs.
   *
   * @ingroup cache
   */
  protected function getAllChildCarePrograms() {
    $cid = __METHOD__;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $programs = $this->client->getChildCarePrograms();
    $this->cache->set($cid, $programs);

    return $programs;
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
    $config = $this->configFactory->get('openy_programs_search.settings');

    // @todo Add try/catch.
    $options = ['allow_redirects' => FALSE];
    $res = $this->http->request('GET', $url, $options);
    $cookies = $res->getHeader('Set-Cookie');

    $final = [];
    $domain = '.' . $config->get('domain');
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

  /**
   * Return the map of rate options by school and program.
   *
   * @return array
   *   Map. [school_id][program_id][[rate_option]]
   *
   * @ingroup cache
   */
  protected function getMapRateOptions() {
    $map = [];

    $cid = __METHOD__;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $domain = $this->getUrlFromOpenyProgramsSearchSettings('base_url');

    // Here we'll iterate over each program and scrape data for each school.
    // We should be careful in order not to make a high load on Daxko.
    $programs = $this->getAllChildCarePrograms();
    foreach ($programs as $program) {
      // Get a list of schools for particular program.
      $schools = $this->scrapeDaxkoSchoolsByProgram($program->id);

      // @todo Check whether we were scraping correct page (with schools).
      $school_pages = $schools->each(function ($school) {
        $href = $school->attr('href');
        return [
          'url' => $href,
          'id' => $this->getQueryParam('location_id', $href),
        ];
      });

      // Scrape each school page to get the rates url.
      foreach ($school_pages as $school_page) {
        $url = $domain . $school_page['url'];
        $source = $this->getDaxkoPageSource($url);
        $this->crawler->clear();
        $this->crawler->addHtmlContent($source);
        $rate_list = $this->crawler->filter('#session-list-table tr.childcare-rate');

        // Scrape each rate item.
        $rate_data = $rate_list->each(function ($rate_data_item) {
          // Get context ID && session name.
          $link = $rate_data_item->filter('a.session-name');
          return [
            'context_id' => $this->getQueryParam('context_id', $link->attr('href')),
            'name' => $link->text(),
            'registration_url' => $link->attr('href'),
          ];
        });

        // Fill in map dada.
        if (!isset($map[$school_page['id']][$program->id])) {
          $map[$school_page['id']][$program->id] = [];
        }

        foreach ($rate_data as $rate_data_item) {
          $map[$school_page['id']][$program->id][$rate_data_item['context_id']] = $rate_data_item;
        }
      }
    }

    $this->cache->set($cid, $map);
    return $map;
  }

  /**
   * Get specified query param from url.
   *
   * @param string $param
   *   Param name.
   * @param string $url
   *   Param name.
   *
   * @return mixed
   *   Param value or FALSE.
   */
  protected function getQueryParam($param, $url) {
    $keys = [];
    $parse = parse_url($url);
    parse_str($parse['query'], $keys);

    if (isset($keys[$param])) {
      return $keys[$param];
    }

    return FALSE;
  }

  /**
   * Get scraped schools object by program ID.
   *
   * @param int $program_id
   *   Program ID.
   *
   * @return \Symfony\Component\DomCrawler\Crawler
   *   Scraped schools.
   */
  protected function scrapeDaxkoSchoolsByProgram($program_id) {
    $link = $this->getUrlFromOpenyProgramsSearchSettings('get_schools_by_program_path', ['{{ program_id }}' => $program_id]);
    $source = $this->getDaxkoPageSource($link);

    $this->crawler->clear();
    $this->crawler->addHtmlContent($source);
    return $this->crawler->filter('div.two-column-container ul li a');
  }

  /**
   * Return array of location names (stripped).
   *
   * @return array
   *   List of locations.
   *
   * @ingroup legacy
   */
  protected function getDaxkoLocationMap() {
    $cid = __METHOD__;
    if ($cache = $this->cache->get($cid)) {
      $data = $cache->data;
    }
    else {
      $locations_data = $branches = $this->client->getBranches(['limit' => 100]);
      $data = [];
      $skip_branches = $this->getExcludeLocationMapIds();

      foreach ($locations_data as $location) {
        if (in_array($location->id, $skip_branches)) {
          continue;
        }
        $name = $location->name;
        $find_replaces = $this->getFindReplaceLocationMapNameText();
        foreach ($find_replaces as $find_replace) {
          if (empty($find_replace['find'])) {
            continue;
          }
          $name = str_replace($find_replace['find'], $find_replace['replace'], $name);
        }
        $name = trim($name);
        $data[$location->id] = $name;
      }

      asort($data);
      $this->cache->set($cid, $data);
    }

    return $data;
  }

  /**
   * Get find and exclude configuration values for exclude_location_map.
   *
   * @return array
   *   Array of nids.
   */
   protected function getExcludeLocationMapIds() {
    $config = $this->configFactory->get('openy_programs_search.settings');
    $exclude_location_map = $config->get('exclude_location_map');

    return is_array($exclude_location_map) ? $exclude_location_map : [];
  }

  /**
   * Get find and replace configuration values for name_string_replace_location_map.
   *
   * @return array
   *   Array of [['find' => 'X']['replace => 'Y']] arrays.
   */
  private function getFindReplaceLocationMapNameText() {
    $config = $this->configFactory->get('openy_programs_search.settings');
    $exclude_location_map = $config->get('name_string_replace_location_map');

    return is_array($exclude_location_map) ? $exclude_location_map : [];
  }

  /**
   * Return map for program IDs and schools.
   *
   * @return array
   *   The list of mappings.
   *
   * @ingroup cache
   */
  protected function getMapSchoolsProgramIds() {
    $map = [];

    $cid = __METHOD__;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $programs = $this->getAllChildCarePrograms();
    foreach ($programs as $program) {
      $schools = $this->scrapeDaxkoSchoolsByProgram($program->id);
      $schools_data = $schools->each(function ($item) {
        return [
          'name' => $item->text(),
          'id' => $this->getQueryParam('location_id', $item->attr('href')),
        ];
      });

      foreach ($schools_data as $school) {
        $map[$program->id][$school['id']] = $school;
      }
    }

    $this->cache->set($cid, $map);
    return $map;
  }

  /**
   * Get custom location stem mapping.
   *
   * @param array $locations
   *   Array of location IDs.
   *
   * @return array
   *   Mapping.
   *
   * @ingroup legacy
   */
  protected function customLocationStem(array $locations) {
    $locexp = [];

    foreach ($locations as $k => $location) {
      $locexp[$k] = explode(' ', trim($location));
      if (count($locexp[$k]) == 1) {
        $locexp[$k] = explode('-', trim($location));
      }
      $initial = [];
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
   * Return map for locations and programs.
   *
   * @return array
   *   The list of mappings.
   *
   * @ingroup cache
   */
  protected function getMapLocationsPrograms() {
    $map = [];

    $cid = __METHOD__;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $programs = $this->getAllChildCarePrograms();
    foreach ($programs as $program) {
      $locations = $this->getLocationsByChildCareProgramId($program->id);
      foreach ($locations as $lid) {
        $map[$lid][$program->id] = $program->id;
      }
    }

    $this->cache->set($cid, $map);
    return $map;
  }

  /**
   * Get all categories.
   *
   * Some categories has different ids. Example: Camp -> cc_category_ids=41
   *
   * @todo Fix categories without IDs.
   */
  public function getCategories() {
    $cid = __METHOD__;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $link = $this->getUrlFromOpenyProgramsSearchSettings('get_categories_path');
    $result = $this->scrapeCategoryList($link);
    $data = array_combine(array_values($result), array_values($result));

    $this->cache->set($cid, $data);
    return $data;
  }

  /**
   * Get map of categories per branch.
   */
  public function getMapCategoriesByBranch() {
    $cid = __METHOD__;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $locations = $this->getLocations();
    $data = [];
    foreach ($locations as $location_id => $location_name) {
      $link = $this->getUrlFromOpenyProgramsSearchSettings('get_map_categories_by_branch_path', ['{{ branch_id }}' => $location_id]);
      $data[$location_id] = $this->scrapeCategoryList($link);
    }

    $this->cache->set($cid, $data);
    return $data;
  }

  /**
   * Scrape list of categories from the page.
   *
   * @param string $link
   *   Link to the page.
   *
   * @return array
   *   List of the categories.
   */
  private function scrapeCategoryList($link) {
    $source = $this->getDaxkoPageSource($link);
    $this->crawler->clear();
    $this->crawler->addHtmlContent($source);
    $list = $this->crawler->filter('div.two-column-container ul li a');
    $items = $list->each(function ($list_item) {
      return [
        'id' => $this->getQueryParam('category_ids', $list_item->attr('href')),
        'title' => $list_item->text(),
      ];
    });

    // Make the array developer friendly.
    $result = [];
    foreach ($items as $item) {
      if ($item['id']) {
        $result[$item['id']] = $item['title'];
      }
    }

    return $result;
  }

  /**
   * Get programs by branch and category.
   *
   * @param int $branch_id
   *   Branch ID.
   * @param string $category
   *   Category IDs.
   *
   * @return array
   *   Programs.
   */
  public function getProgramsByBranchAndCategory($branch_id, $category) {
    $cid = __METHOD__ . $branch_id . md5($category);
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $params = [
      'branch' => $branch_id,
      'tag' => $category,
      'limit' => 10000,
    ];

    $data = [];
    $result = $this->client->getSessions($params);
    foreach ($result as $item) {
      $program = new \stdClass();
      $program->id = $item->programId;
      $program->name = $item->programName;
      $data[] = $program;
    }

    $this->cache->set($cid, $data);
    return $data;
  }

  /**
   * Get categories by Branch.
   *
   * @param int $branch_id
   *   Branch ID.
   *
   * @return array
   *   Categories list.
   */
  public function getCategoriesByBranch($branch_id) {
    $categories = [];

    $map = $this->getMapCategoriesByBranch();
    if (isset($map[$branch_id])) {
      $categories = $map[$branch_id];
    }

    $data = array_combine(array_values($categories), array_values($categories));
    return $data;
  }

}
