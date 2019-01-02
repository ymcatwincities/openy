<?php

namespace Drupal\openy_daxko2;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use GuzzleHttp\Client;
use Drupal\openy_activity_finder\OpenyActivityFinderBackend;

class OpenyActivityFinderDaxkoBackend extends OpenyActivityFinderBackend {

  /**
   * Daxko configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $daxkoConfig;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * OpenyActivityFinderDaxkoBackend constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The EntityTypeManager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param CacheBackendInterface $cache
   *   Cache default.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entity_type_manager, CacheBackendInterface $cache) {
    parent::__construct($config_factory);
    $this->daxkoConfig = $config_factory->get('openy_daxko2.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
  }

  /**
   * @inheritdoc
   */
  public function runProgramSearch($parameters, $log_id) {
    $parameters += [
      'locations' => '',
      'keywords' => '',
      'next' => '',
      'days' => '',
      'ages' => '',
    ];

    $access_token = $this->getDaxkoToken();

    $get = [];
    $locationsConfig = $this->getDaxkoLocations();
    $locationArgument = $parameters['locations'];
    $locations = explode(',', $locationArgument);
    if (empty($locationArgument)) {
      $locations = array_keys($locationsConfig);
    }
    else {
      $locationsConfigFlip = array_flip($locationsConfig);
      $new_locations = [];
      foreach ($locations as $location_name) {
        if (isset($locationsConfigFlip[$location_name])) {
          $new_locations[] = $locationsConfigFlip[$location_name];
        }
      }
      $locations = $new_locations;
    }

    if (!empty($locations)) {
      $get['location_ids'] = implode(',', $locations);
    }

    $keywords = $parameters['keywords'];
    if (!empty($keywords)) {
      $get['keywords'] = $keywords;
    }

    if ($next = $parameters['next']) {
      $get['after'] = $next;
    }

    if ($days = $parameters['days']) {
      $get['days_offered'] = $days;
    }

    $ages = $parameters['ages'];
    if (!empty($ages)) {
      $ageGet = [];
      foreach (explode(',', $ages) as $age) {
        $ageGet[] = date('Y-m-d', strtotime('-' . $age . ' months'));
      }
      $get['birth_dates'] = implode(',', $ageGet);
    }

    if (isset($parameters['program_types']) && !empty($parameters['program_types'])) {
      $program_types = explode(',', $parameters['program_types']);
      $categories = $this->getCategories();
      $daxkoProgramIds = [];
      foreach ($categories as $categoryTopLevel) {
        if (!in_array($categoryTopLevel['label'], $program_types)) {
          continue;
        }
        foreach ($categoryTopLevel['value'] as $category) {
          $daxkoProgramIds[] = $category['value'];
        }
      }
      $get['category_ids'] = implode(',', $daxkoProgramIds);
    }

    if (isset($parameters['activities']) && !empty($parameters['activities'])) {
      $get['category_ids'] = $parameters['activities'];
    }

    // Include facets. We need locations for Activity Finder.
    $get['include_facets'] = TRUE;

    $time_start = microtime(true);
    $client = new Client(['base_uri' => $this->daxkoConfig->get('base_uri')]);
    $response = $client->request('GET', 'programs/offerings/search',
      [
        'query' => $get,
        'headers' => [
          'Authorization' => "Bearer " . $access_token
        ],
      ]);
    $time_end = microtime(true);
    $time = $time_end - $time_start;
    \Drupal::logger('openy_daxko2')->info('Daxko call. Time %times. URL %url', [
      '%time' => number_format($time, 2),
      '%url' => 'programs/offerings/search',
    ]);

    $programsResponse = json_decode((string) $response->getBody(), TRUE);

    $locationNodesData = $this->getLocationsInfoFromNodes();
    $config = \Drupal::service('config.factory')->getEditable('openy_daxko2.settings');
    $locations_config = $config->get('locations');
    $locations_rows = explode("\n", $locations_config);
    foreach ($locations_rows as $locations_row) {
      if (substr($locations_row, 0, 1) == '*') {
        continue;
      }
      $locations_row_fields = explode(',', $locations_row);
      // Remove id.
      array_shift($locations_row_fields);
      // Location name.
      $location_name = trim(array_shift($locations_row_fields));
      if (isset($locationNodesData[$location_name])) {
        continue;
      }
      $location_address = implode(',', $locations_row_fields);
      $locationNodesData[$location_name] = [
        'title' => $location_name,
        'address' => $location_address,
        'email' => '',
        'phone' => '',
        'days' => [],
      ];
    }

    $result = [];
    foreach ($programsResponse['offerings'] as $row) {
      $start_date = new \DateTime($row['start_date']);
      $end_date = new \DateTime($row['end_date']);
      $start_date_formatted = $start_date->format('M d');
      $end_date_formatted = $end_date->format('M d, Y');
      if ($start_date->format('Y') != $end_date->format('Y')) {
        $start_date_formatted = $start_date->format('M d, Y');
      }
      $times = '';
      if (isset($row['times'][0])) {
        $start = strtotime($row['times'][0]['start']);
        $end = strtotime($row['times'][0]['end']);
        if (date('a', $start) == date('a', $end)) {
          $times = date('g:i', $start) . '-' . date('g:ia', $end);
        }
        else {
          $times = date('g:ia', $start) . '-' . date('g:ia', $end);
        }
      }
      $days = [];
      if (isset($row['days_offered'][0])) {
        foreach ($row['days_offered'] as $day) {
          $days[] = $day['name'];
        }
      }

      foreach ($row['locations'] as $location_row) {
        $location_id = $location_row['id'];
        if (!in_array($location_id, $locations)) {
          continue;
        }

        // Enrich with pricing.
        $price = '';
        $cache_key = 'daxko-price-' . md5(
            $row['id'] . $row['program']['id'] . $location_id
          );
        if ($cache = $this->cache->get($cache_key)) {
          $price = $cache->data;
        }

        $location_name = $location_row['name'];
        $location_info = [
          'title' => $location_row['name'],
          'address' => '',
          'email' => '',
          'phone' => '',
          'days' => [],
        ];
        if (isset($locationNodesData[$location_name])) {
          $location_info = $locationNodesData[$location_name];
        }

        $availability_status = '';
        $availability_note = '';
        $cache_key = 'daxko-availability-' . md5(
            $row['id'] . $row['program']['id'] . $location_id
          );
        if ($cache = $this->cache->get($cache_key)) {
          $availability_status = $cache->data['status'];
          $availability_note = $cache->data['note'];
        }

        // Build register link.
        $program_id = $row['program']['id'];
        $offering_id = $row['id'];
        $register_link = 'https://ops1.operations.daxko.com/Online/' . $config->get(
            'client_id'
          ) . '/ProgramsV2/OfferingDetails.mvc?program_id=' . $program_id . '&offering_id=' . $offering_id . '&location_id=' . $location_id;

        $register_link_with_tracking = \Drupal\Core\Url::fromRoute(
          'openy_activity_finder.register_redirect',
          ['log' => $log_id],
          [
            'query' => [
              'url' => $register_link,
              'details' => $row['name'] . ' - ' . $row['program']['name'],
            ]
          ]
        )->toString();

        $result[] = [
          'location' => $location_name,
          'name' => $row['name'] . ' - ' . $row['program']['name'],
          'dates' => $start_date_formatted . ' - ' . $end_date_formatted,
          'times' => $times,
          'days' => implode(', ', $days),
          'offering_id' => $offering_id,
          'program_id' => $program_id,
          'location_id' => $location_id,
          'info' => var_export($row, TRUE),
          'price' => $price,
          'location_info' => $location_info,
          'availability_status' => $availability_status,
          'availability_note' => $availability_note,
          'register_link' => $register_link_with_tracking,
          'log_id' => $log_id,
        ];
      }
    }

    $pager = '';
    if (isset($programsResponse['after'])) {
      $pager = $programsResponse['after'];
    }

    $facets = $programsResponse['facets'];
    foreach ($facets as $facet_name => &$facet_data) {
      foreach ($facet_data as $key => &$facet) {
        $facet = [
          'name' => $facet['name'],
          'id' => $facet['id'],
          'count' => $facet['offering_count'],
        ];
      }
    }

    return [
      'count' => $programsResponse['total'],
      'table' => $result,
      'pager' => $pager,
      'facets' => $facets,
    ];
  }

  /**
   * Get the days of week.
   */
  public function getDaysOfWeek() {
    return [
      [
        'label' => 'Monday',
        'value' => '1',
      ],
      [
        'label' => 'Tuesday',
        'value' => '2',
      ],
      [
        'label' => 'Wednesday',
        'value' => '3',
      ],
      [
        'label' => 'Thursday',
        'value' => '4',
      ],
      [
        'label' => 'Friday',
        'value' => '5',
      ],
      [
        'label' => 'Saturday',
        'value' => '6',
      ],
      [
        'label' => 'Sunday',
        'value' => '7',
      ],
    ];
  }

  /**
   * @inheritdoc
   */
  public function getLocations() {
    // Need locations groupped to display in filter.
    return $this->getDaxkoLocations(TRUE);
  }

  public function getCategories() {
    // Need locations groupped to display in filter.
    return $this->getDaxkoCategories(TRUE);
  }

  public function getCategoriesTopLevel() {
    $categories_config = $this->daxkoConfig->get('categories');
    $categories = $this->parseGrouppedSetting($categories_config, TRUE);

    return array_keys($categories);
  }

  protected function getDaxkoCategories($group = FALSE) {
    $categories_config = $this->daxkoConfig->get('categories');
    $categories = $this->parseGrouppedSetting($categories_config, $group);
    if (!$group) {
      return $categories;
    }

    $numericalKeyedCategories = [];
    foreach ($categories as $label => $value) {
      $numericalKeyedCategories[] = ['value' => $value, 'label' => $label];
    }

    return $numericalKeyedCategories;
  }

  /**
   * Run querty to Daxko to get the token.
   */
  protected function getDaxkoToken() {
    $time_start = microtime(true);
    $client = new Client(['base_uri' => $this->daxkoConfig->get('base_uri')]);
    $response = $client->request('POST', 'partners/oauth2/token',
      [
        'form_params' => [
          'client_id' => $this->daxkoConfig->get('user'),
          'client_secret' => $this->daxkoConfig->get('pass'),
          'grant_type' => 'client_credentials',
          'scope' => 'client:' . $this->daxkoConfig->get('client_id'),
        ],
        'headers' => [
          'Authorization' => "Bearer " . $this->daxkoConfig->get('referesh_token')
        ],
      ]);
    $time_end = microtime(true);
    $time = $time_end - $time_start;
    \Drupal::logger('openy_daxko2')->info('Daxko call. Time %times. URL %url', [
      '%time' => number_format($time, 2),
      '%url' => 'partners/oauth2/token',
    ]);

    return json_decode((string) $response->getBody())->access_token;
  }

  /**
   * Load locations from the configuration.
   */
  protected function getDaxkoLocations($group = FALSE) {
    $locations_config = $this->daxkoConfig->get('locations');
    $locations = $this->parseGrouppedSetting($locations_config, $group);

    if (!$group) {
      return $locations;
    }

    $numericalKeyedLocations = [];
    foreach ($locations as $label => $value) {
      $numericalKeyedLocations[] = ['value' => $value, 'label' => $label];
    }

    return $numericalKeyedLocations;
  }

  protected function parseGrouppedSetting($config, $group) {
    $config_rows = explode("\n", $config);

    $result = [];
    $group_name = '';
    foreach ($config_rows as $row) {
      $row = trim($row);
      if (empty($row)) {
        continue;
      }
      if (!$group && substr($row, 0, 1) == '*') {
        continue;
      }
      if ($group && substr($row, 0, 1) == '*') {
        $row = trim($row);
        $group_name = trim($row, '*');
        continue;
      }
      list($id, $name, ) = explode(',', $row);
      $id = trim($id);
      $name = trim($name);
      if (!empty($id) && !empty($name)) {
        if ($group && $group_name) {
          $result[$group_name][] = ['value' => $id, 'label' => $name];
        }
        else {
          $result[$id] = $name;
        }
      }
    }

    return $result;
  }

  /**
   * Get detailed info about Location (aka branch).
   */
  protected function getLocationsInfoFromNodes() {
    $data = [];
    $tags = ['node_list'];
    $cid = 'openy_repeat:locations_info';
    if ($cache = $this->cache->get($cid)) {
      $data = $cache->data;
    }
    else {
      $nids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('type','branch')
        ->execute();
      $nids_chunked = array_chunk($nids, 20, TRUE);
      foreach ($nids_chunked as $chunk) {
        $branches = $this->entityTypeManager->getStorage('node')->loadMultiple($chunk);
        if (!empty($branches)) {
          foreach ($branches as $node) {
            $days = $node->get('field_branch_hours')->getValue();
            $locAddress = $node->get('field_location_address')->getValue();
            if (!empty($locAddress[0])) {
              $address = [];
              if (!empty($locAddress[0]['address_line1'])) {
                $address[] = $locAddress[0]['address_line1'];
              }
              if (!empty($locAddress[0]['locality'])) {
                $address[] = $locAddress[0]['locality'];
              }
              if (!empty($locAddress[0]['administrative_area'])) {
                $address[] = $locAddress[0]['administrative_area'];
              }
              if (!empty($locAddress[0]['postal_code'])) {
                $address[] = $locAddress[0]['postal_code'];
              }
              $address = implode(', ', $address);
            }
            $data[$node->title->value] = [
              'nid' => $node->nid->value,
              'title' => $node->title->value,
              'email' => $node->field_location_email->value,
              'phone' => $node->field_location_phone->value,
              'address' => $address,
              'days' => !empty($days[0]) ? $this->getFormattedHours($days[0]) : [],
            ];
            $tags[] = 'node:' . $node->nid->value;
          }
        }
      }
      $this->cache->set($cid, $data, CacheBackendInterface::CACHE_PERMANENT, $tags);
    }

    return $data;
  }

  protected function getFormattedHours($data) {
    $lazy_hours = $groups = $rows = [];
    foreach ($data as $day => $value) {
      // Do not process label. Store it name for later usage.
      if ($day == 'hours_label') {
        continue;
      }

      $day = str_replace('hours_', '', $day);
      $value = $value ? $value : 'closed';
      $lazy_hours[$day] = $value;
      if ($groups && end($groups)['value'] == $value) {
        $array_keys = array_keys($groups);
        $group = &$groups[end($array_keys)];
        $group['days'][] = $day;
      }
      else {
        $groups[] = [
          'value' => $value,
          'days' => [$day],
        ];
      }
    }

    foreach ($groups as $group_item) {
      $title = sprintf('%s - %s', ucfirst(reset($group_item['days'])), ucfirst(end($group_item['days'])));
      if (count($group_item['days']) == 1) {
        $title = ucfirst(reset($group_item['days']));
      }
      $hours = $group_item['value'];
      $rows[] = [$title . ':', $hours];
    }

    return $rows;
  }

}