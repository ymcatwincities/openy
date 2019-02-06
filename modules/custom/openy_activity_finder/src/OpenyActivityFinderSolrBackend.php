<?php

namespace Drupal\openy_activity_finder;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\search_api\Entity\Index;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\Yaml\Parser;

/**
 * {@inheritdoc}
 */
class OpenyActivityFinderSolrBackend extends OpenyActivityFinderBackend {

  /**
   * Cache default.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entity_type_manager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Creates a new RepeatController.
   *
   * @param CacheBackendInterface $cache
   *   Cache default.
   * @param Connection $database
   *   The Database connection.
   * @param EntityTypeManager $entity_type_manager
   *   The EntityTypeManager.
   * @param DateFormatterInterface $date_formatter
   *   The Date formatter.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache, Connection $database, QueryFactory $entity_query, EntityTypeManager $entity_type_manager, DateFormatterInterface $date_formatter) {
    parent::__construct($config_factory);
    $this->cache = $cache;
    $this->database = $database;
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function runProgramSearch($parameters, $log_id) {
    // Make a request to Search API.
    $results = $this->doSearchRequest($parameters);

    // Get results count.
    $data['count'] = $results->getResultCount();

    // Get facets and enrich, sort data, add static filters.
    $data['facets'] = $this->getFacets($results);

    // Process results.
    $data['table'] = $this->processResults($results, $log_id);

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function doSearchRequest($parameters) {
    $index = Index::load('default');
    $query = $index->query();
    $parse_mode = \Drupal::service('plugin.manager.search_api.parse_mode')->createInstance('direct');
    $query->getParseMode()->setConjunction('OR');
    $query->setParseMode($parse_mode);
    $keys = !empty($parameters['keywords']) ? $parameters['keywords'] : '';
    $query->keys($keys);
    $query->setFulltextFields(['title']);
    $query->addCondition('status', 1);

    if (!empty($parameters['ages'])) {
      $ages = explode(',', rawurldecode($parameters['ages']));
      $db_or = $query->createConditionGroup('OR');
      foreach ($ages as $age) {
        $db_and = $query->createConditionGroup('AND');
        // Specific case, user selected e.g. '16+'.
        if (strpos($age, '+')) {
          $age = str_replace('+', '', $age);
          $db_and->addCondition('field_session_min_age', $age, '>=');
        }
        // Specific case, user selected less than 18 months (6, 12, 18).
        else if ($age <= 18) {
          $db_and->addCondition('field_session_min_age', $age, '>=');
          $db_and->addCondition('field_session_min_age', $age + 6, '<');
        }
        else {
          // Common case (1 year selection).
          $db_and->addCondition('field_session_min_age', $age, '>=');
          $db_and->addCondition('field_session_min_age', $age + 12, '<');
        }
        $db_or->addConditionGroup($db_and);
      }
      $query->addConditionGroup($db_or);
      // @todo: do we need to use max_age at all?
      //$conditions->addCondition('field_session_max_age', 0);
    }

    if (!empty($parameters['days'])) {
      $days = explode(',', rawurldecode($parameters['days']));
      $query->addCondition('field_session_time_days', $days, 'IN');
    }

    if (!empty($parameters['program_types'])) {
      $program_types = explode(',', rawurldecode($parameters['program_types']));
      $query->addCondition('field_category_program', $program_types, 'IN');
    }

    if (!empty($parameters['categories'])) {
      $categories_nids = explode(',', rawurldecode($parameters['categories']));
      // Map nids to titles.
      $category_program_info = $this->getCategoryProgramInfo();
      foreach ($categories_nids as $nid) {
        $categories[] = !empty($category_program_info[$nid]['title']) ? $category_program_info[$nid]['title'] : '';
      }
      $query->addCondition('field_activity_category', $categories, 'IN');
    }

    if (!empty($parameters['locations'])) {
      $locations_nids = explode(',', rawurldecode($parameters['locations']));
      // Map nids to titles.
      $locations_info = $this->getLocationsInfo();
      foreach ($locations_info as $key => $item) {
        if (in_array($item['nid'], $locations_nids)) {
          $locations[] = $key;
        }
      }
      $query->addCondition('field_session_location', $locations, 'IN');
    }

    $query->range(0, 25);
    $query->sort('search_api_relevance', 'DESC');
    $server = $index->getServerInstance();
    if ($server->supportsFeature('search_api_facets')) {
      $filters = $this->getFilters();
      $query->setOption('search_api_facets', $filters);
    }
    $query->addTag('af_search');
    $results = $query->execute();

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function processResults($results, $log_id) {
    $data = [];
    $locations_info = $this->getLocationsInfo();
    /** @var \Drupal\search_api\Item\Item $result_item */
    foreach ($results->getResultItems() as $result_item) {
      $entity = $result_item->getOriginalObject()->getValue();
      $fields = $result_item->getFields();
      $dates = $entity->field_session_time->referencedEntities();
      $schedule_items = [];
      foreach ($dates as $date) {
        $_period = $date->field_session_time_date->getValue()[0];
        $_from = DrupalDateTime::createFromTimestamp(strtotime($_period['value']));
        $_to = DrupalDateTime::createFromTimestamp(strtotime($_period['end_value']));
        $days = [];
        foreach ($date->field_session_time_days->getValue() as $time_days) {
          $days[] = $time_days['value'];
        }
        $schedule_items[] = [
          'days' => implode(', ', $days),
          'time' => $_from->format('H:i') .' - '. $_to->format('H:i'),
        ];
        $full_dates = $_from->format('m/d/Y') . ' - ' . $_to->format('m/d/Y');
      }

      $availability_status = 'closed';
      if (!$entity->field_session_online->isEmpty()) {
        $availability_status = $entity->field_session_online->value ? 'open' : 'closed';
      }

      $availability_note = '';
      if ($availability_status == 'closed') {
        $availability_note = t('Registration closed')->__toString();
      }

      $data[] = [
        'nid' => $entity->id(),
        'availability_note' => $availability_note,
        'availability_status' => $availability_status,
        'dates' => $full_dates,
        'schedule' => $schedule_items,
        'days' => $schedule_items[0]['days'],
        'times' => $schedule_items[0]['time'],
        'info' => [
          'id' => '',
          'type' => 'session',
          'name' => $fields['title']->getValues()[0]->getText(),
          'description' => html_entity_decode(strip_tags(text_summary($entity->field_session_description->value, $entity->field_session_description->format, 600))),
          'start_date' => '2019-02-18T00:00:00.0000000',
          'end_date' => '2019-02-18T00:00:00.0000000',
          'program' => [
            'id' => '',
            'type' => 'standard',
            'name' => $fields['field_category_program']->getValues()[0],
            'description' => '', ],
          'locations' => [
            0 => [
              'id' => '',
              'name' => $fields['field_session_location']->getValues()[0],
              'type' => $locations_info[$fields['field_session_location']->getValues()[0]]['type'],
              ],
            ],
          'groups' => [
            0 => ['id' => 'FG116', 'name' => 'Member', ],
            1 => ['id' => 'FG117', 'name' => 'Non-Member', ], ],
          'restrictions' => [
            'genders' => [
              0 => [
                'id' => $entity->field_session_gender->value,
                'name' => $entity->field_session_gender->value,
              ],
            ],
            'dob' => ['start' => '2006-02-19T00:00:00.0000000', 'end' => '2017-02-18T00:00:00.0000000', ],
            'age' => [
              'start' => $entity->field_session_min_age->value, 'end' => $entity->field_session_max_age->value, ],
            ],
          'times' => [
            0 => [
              'start' => '09:00',
              'end' => '12:45',
              ],
            ],
          'days_offered' => [
            0 => [
              'id' => '1',
              'name' => 'Monday',
              ],
            ],
          'highlights' => [],
          'registration' => [
            'start' => $entity->field_standard_registration_date->value,
            'end' => $entity->field_standard_registration_date->end_value,
            'state' => 'open',
            ],
          'score' => 0,
          ],
        'location' => $fields['field_session_location']->getValues()[0],
        'location_id' => '',
        'location_info' => $locations_info[$fields['field_session_location']->getValues()[0]],
        'log_id' => $log_id,
        'name' => $fields['title']->getValues()[0]->getText(),
        'offering_id' => '',
        'price' => 'Member: $' . $entity->field_session_mbr_price->value . '<br/>Non-Member: $' . $entity->field_session_nmbr_price->value,
        'program_id' => '',
        'link' => Url::fromRoute('openy_activity_finder.register_redirect', [
            'log' => $log_id,
          ],
          ['query' => [
            'url' => $entity->field_session_reg_link->uri,
          ],
        ])->toString(),
        'description' => html_entity_decode(strip_tags(text_summary($entity->field_session_description->value, $entity->field_session_description->format, 600))),
        'ages' => $entity->field_session_min_age->value . '-' . $entity->field_session_max_age->value . 'yrs',
        'gender' => $entity->field_session_gender->value,
        'location_name' => '',
        'location_address' => '',
        'location_phone' => '',
      ];
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getFacets($results) {
    $facets = $results->getExtraData('search_api_facets', []);
    $locationsInfo = $this->getLocationsInfo();
    $category_program_info = $this->getCategoryProgramInfo();

    // Add static Age filter.
    $facets['static_age_filter'] = $this->getAges();

    foreach ($facets as $f => $facet) {
      // Modify age filter.
      foreach ($facet as $i => $item) {
        if ($f == 'static_age_filter') {
          $facets[$f][$i] = [
            'filter' => $i,
            'label' => $item,
            'safe' => 'age_' . str_replace('+', '', $i) . '_months',
          ];
        }
      }
      foreach ($facet as $i => $item) {
        if (!empty($item['filter'])) {
          // Remove double quotes.
          $facets[$f][$i]['filter'] = str_replace('"', '', $item['filter']);
          // Add safe string for using in tag attributes.
          $facets[$f][$i]['safe'] = Html::cleanCssIdentifier($f . '_' . $item['filter']);
        }
      }
    }
    foreach ($facets as $f => $facet) {
      if ($f == 'locations') {
        foreach ($facet as $i => $item) {
          if (!empty($item['filter'])) {
            $facets[$f][$i]['id'] = $locationsInfo[$item['filter']]['nid'];
          }
        }
      }
      // Group field_activity_category facet by Program Type.
      if ($f == 'field_activity_category') {
        $grouped = [];
        foreach ($facet as $i => $item) {
          if (isset($category_program_info[$item['filter']])) {
            $grouped[$category_program_info[$item['filter']]][] = $item;
          }
          else {
            // On regular basis there are no results, keep for tracking vary case.
            $grouped['Others'][] = $item;
          }
        }
        $facets[$f] = !empty($grouped) ? $grouped : $facet;
      }
    }

    return $facets;
  }


  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    $filters = [
      'field_session_min_age' => [
        'field' => 'field_session_min_age',
        'limit' => 0,
        'operator' => 'AND',
        'min_count' => 0,
        'missing' => TRUE,
      ],
      'field_session_max_age' => [
        'field' => 'field_session_max_age',
        'limit' => 0,
        'operator' => 'AND',
        'min_count' => 0,
        'missing' => TRUE,
      ],
      'field_category_program' => [
        'field' => 'field_category_program',
        'limit' => 0,
        'operator' => 'AND',
        'min_count' => 0,
        'missing' => TRUE,
      ],
      'field_activity_category' => [
        'field' => 'field_activity_category',
        'limit' => 0,
        'operator' => 'AND',
        'min_count' => 0,
        'missing' => TRUE,
      ],
      'locations' => [
        'field' => 'field_session_location',
        'limit' => 0,
        'operator' => 'AND',
        'min_count' => 0,
        'missing' => TRUE,
      ],
      'days_of_week' => [
        'field' => 'field_session_time_days',
        'limit' => 0,
        'operator' => 'AND',
        'min_count' => 0,
        'missing' => TRUE,
      ],
    ];
    return $filters;
  }

  /**
   * Get referencing chain for Session -> Program info.
   */
  public function getCategoryProgramInfo() {
    $data = [];
    $cid = 'openy_activity_finder:activity_program_info';
    if ($cache = $this->cache->get($cid)) {
      $data = $cache->data;
    }
    else {
      $nids = $this->entityQuery
        ->get('node')
        ->condition('type','program_subcategory')
        ->execute();
      $nids_chunked = array_chunk($nids, 20, TRUE);
      foreach ($nids_chunked as $chunked) {
        $program_subcategories = $this->entityTypeManager->getStorage('node')->loadMultiple($chunked);
        if (!empty($program_subcategories)) {
          foreach ($program_subcategories as $program_subcategory_node) {
            if ($program_node = $program_subcategory_node->field_category_program->entity) {
              $data[$program_subcategory_node->id()] = [
                'title' => $program_subcategory_node->title->value,
                'program' => [
                  'nid' => $program_node->id(),
                  'title' => $program_node->title->value,
                ],
              ];
            }
          }
        }
      }
      $this->cache->set($cid, $data, 86400);
    }

    return $data;
  }

  /**
   * Get Locations Info.
   */
  public function getLocationsInfo() {
    $data = [];
    $cid = 'openy_activity_finder:locations_info';
    if ($cache = $this->cache->get($cid)) {
      $data = $cache->data;
    }
    else {
      $nids = $this->entityQuery
        ->get('node')
        ->condition('type', ['branch', 'camp'], 'IN')
        ->execute();
      $nids_chunked = array_chunk($nids, 20, TRUE);
      foreach ($nids_chunked as $chunked) {
        $locations = $this->entityTypeManager->getStorage('node')->loadMultiple($chunked);
        if (!empty($locations)) {
          foreach ($locations as $location) {
            $address = implode(', ', [
              $location->field_location_address->address_line1,
              $location->field_location_address->locality,
              $location->field_location_address->administrative_area,
              $location->field_location_address->postal_code,
            ]);
            $days = [];
            foreach ($location->field_branch_hours as $multi_hours) {
              $sub_hours = $multi_hours->getValue();
              $days = [
                [
                  0 => "Mon - Fri:",
                  1 => $sub_hours['hours_mon']
                ],
                [
                  0 => "Sat - Sun:",
                  1 => $sub_hours['hours_sat']
                ]
              ];
            }
            $data[$location->title->value] =[
              'type' => $location->bundle(),
              'address' => $address,
              'days' => $days,
              'email' => $location->field_location_email->value,
              'nid' => $location->id(),
              'phone' => $location->field_location_phone->value,
              'title' => $location->title->value
            ];
          }
        }
      }
      $this->cache->set($cid, $data, 86400);
    }

    return $data;
  }

  public function getCategoriesTopLevel() {
    $categories = [];
    $programInfo = $this->getCategoryProgramInfo();
    foreach ($programInfo as $key => $item) {
      $categories[$item['program']['nid']] = $item['program']['title'];
    }
    return array_values($categories);
  }

  public function getCategories() {
    $categories = [];
    $programInfo = $this->getCategoryProgramInfo();
    foreach ($programInfo as $key => $item) {
      $categories[$item['program']['nid']]['value'][] = [
        'value' => $key,
        'label' => $item['title']
      ];
      $categories[$item['program']['nid']]['label'] = $item['program']['title'];
    }
    return array_values($categories);
  }

  /**
   * Get the days of week.
   */
  public function getDaysOfWeek() {
    return [
      [
        'label' => 'Mon',
        'value' => 'monday',
      ],
      [
        'label' => 'Tue',
        'value' => 'tuesday',
      ],
      [
        'label' => 'Wed',
        'value' => 'wednesday',
      ],
      [
        'label' => 'Thu',
        'value' => 'thursday',
      ],
      [
        'label' => 'Fri',
        'value' => 'friday',
      ],
      [
        'label' => 'Sat',
        'value' => 'saturday',
      ],
      [
        'label' => 'Sun',
        'value' => 'sunday',
      ],
    ];
  }

  /**
   * @inheritdoc
   */
  public function getLocations() {
    $locations = [];
    $locationsInfo = $this->getLocationsInfo();
    foreach ($locationsInfo as $key => $item) {
      $locations[$item['type']]['value'][] = [
        'value' => $item['nid'],
        'label' => $key
      ];
      $locations[$item['type']]['label'] = ucfirst($item['type']);
    }
    return array_values($locations);
  }

  /**
   * @inheritdoc
   */
  public function getProgramsMoreInfo($request) {
    // Idea is that when we use Solr backend we have all the data
    // available in runProgramSearch() call so this call is not needed
    // meanwhile you can alter search results to set availability_status
    // to be empty so getProgramsMoreInfo call will be triggered and you
    // can alter its behavior. For example if you like to check availability
    // with live call to your CRM.
    return [];
  }


}
