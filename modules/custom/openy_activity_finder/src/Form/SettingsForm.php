<?php

namespace Drupal\openy_activity_finder\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings Form for daxko.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Guzzle Http Client.
   *
   * @var GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \GuzzleHttp\Client $http_client
   *   The http_client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, Client $http_client, CacheBackendInterface $cache) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->httpClient = $http_client;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('http_client'),
      $container->get('cache.render')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_activity_finder_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openy_activity_finder.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_activity_finder.settings');

    $form_state->setCached(FALSE);

    $backend_options = [];
    if ($this->moduleHandler->moduleExists('search_api')) {
      $backend_options['openy_activity_finder.solr_backend'] = 'Solr Backend (local db)';
    }

    if ($this->moduleHandler->moduleExists('openy_daxko2')){
      $backend_options['openy_daxko2.openy_activity_finder_backend'] = $this->t('Daxko 2 (live API calls)');
    }

    $form['backend'] = [
      '#type' => 'select',
      '#options' => $backend_options,
      '#required' => TRUE,
      '#title' => $this->t('Backend for Activity Finder'),
      '#default_value' => $config->get('backend'),
      '#description' => $this->t('Search API backend for Activity Finder'),
    ];

    if ($this->moduleHandler->moduleExists('search_api')) {
      $search_api_indexes = $this->entityTypeManager
        ->getStorage('search_api_index')->loadByProperties();

      $indexes = [];
      if ($search_api_indexes) {
        foreach ($search_api_indexes as $index) {
          $indexes[$index->id()] = $index->get('name');
        }
      }

      $form['index'] = [
        '#type' => 'select',
        '#options' => $indexes,
        '#title' => $this->t('Search API index'),
        '#default_value' => $config->get('index'),
        '#description' => $this->t('Search API Index to use for SOLR backend.'),
        '#states' => [
          'visible' => [
            ':input[name="backend"]' => ['value' => 'openy_activity_finder.solr_backend'],
          ],
          'required' => [
            ':input[name="backend"]' => ['value' => 'openy_activity_finder.solr_backend'],
          ],
        ],
      ];
    }

    $form['ages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ages'),
      '#default_value' => $config->get('ages'),
      '#description' => $this->t('Ages mapping. One per line. "<number of months>,<age display label>". Example: "660,55+"'),
    ];


    $form['exclude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude category -- so we do not display Group Exercises'),
      '#default_value' => $config->get('exclude'),
      '#description' => $this->t('Provide ID of the Program Subcategory to exclude. You do not need to provide this if you use Daxko. Needed only for Solr backend.'),
    ];

    $form['disable_search_box'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Search Box'),
      '#default_value' => $config->get('disable_search_box'),
      '#description' => $this->t('When checked hides search text box (both for Activity Finder and Results page).'),
    ];

    $form['disable_spots_available'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Spots Available'),
      '#default_value' => $config->get('disable_spots_available'),
      '#description' => $this->t('When checked disables Spots Available feature on Results page.'),
    ];

    $form['hb_modal'] = [
      '#type' => 'details',
      '#title' => $this->t('HomeBranch - No results modal content.'),
      '#open' => TRUE,
    ];

    $form['hb_modal']['hb_modal_text0'] = [
      '#title' => $this->t('Title'),
      '#type' => 'textfield',
      '#default_value' => $config->get('hb_modal_text0') ? $config->get('hb_modal_text0') : '',
    ];

    $form['hb_modal']['hb_modal_text1'] = [
      '#title' => $this->t('Text line 1'),
      '#type' => 'textfield',
      '#default_value' => $config->get('hb_modal_text1') ? $config->get('hb_modal_text1') : '',
    ];

    $form['hb_modal']['hb_modal_text2'] = [
      '#title' => $this->t('Text line 2'),
      '#type' => 'textfield',
      '#default_value' => $config->get('hb_modal_text2') ? $config->get('hb_modal_text2') : '',
    ];

    $form['hb_modal']['hb_modal_text3'] = [
      '#title' => $this->t('Text line 3'),
      '#type' => 'textfield',
      '#default_value' => $config->get('hb_modal_text3') ? $config->get('hb_modal_text3') : '',
    ];

    $form['hb_modal']['hb_modal_text4'] = [
      '#title' => $this->t('Close modal button text'),
      '#type' => 'textfield',
      '#default_value' => $config->get('hb_modal_text4') ? $config->get('hb_modal_text4') : '',
    ];

    $form['hb_modal']['hb_modal_text5'] = [
      '#title' => $this->t('Start over button text'),
      '#type' => 'textfield',
      '#default_value' => $config->get('hb_modal_text5') ? $config->get('hb_modal_text5') : '',
    ];

    $form['collapse'] = [
      '#type' => 'details',
      '#title' => $this->t('Group collapse settings.'),
      '#open' => TRUE,
      '#description' => $this->t('Please select items to show them as Expanded on program search. Default state is collapsed'),
    ];
    $form['collapse']['schedule'] = [
      '#type' => 'details',
      '#title' => $this->t('Schedule preferences'),
      '#open' => TRUE,
    ];
    $options = [
      'disabled' => $this->t('Disabled'),
      'enabled_collapsed' => $this->t('Enabled - Collapsed'),
      'enabled_expanded' => $this->t('Enabled - Expanded'),
    ];
    $form['collapse']['schedule']['schedule_collapse_group'] = [
      '#title' => $this->t('Settings for whole group.'),
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => $config->get('schedule_collapse_group') ? $config->get('schedule_collapse_group') : 'disabled',
      '#description' => $this->t('Check this if you want default state for whole this group is "Collapsed"'),
    ];
    $form['collapse']['category'] = [
      '#type' => 'details',
      '#title' => $this->t('Activity preferences'),
      '#open' => TRUE,
    ];
    $form['collapse']['category']['category_collapse_group'] = [
      '#title' => $this->t('Settings for whole group.'),
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => $config->get('category_collapse_group') ? $config->get('category_collapse_group') : 'disabled',
      '#description' => $this->t('Check this if you want default state for whole this group is "Collapsed"'),
    ];
    $form['collapse']['locations'] = [
      '#type' => 'details',
      '#title' => $this->t('Location preferences'),
      '#open' => TRUE,
    ];
    $form['collapse']['locations']['locations_collapse_group'] = [
      '#title' => $this->t('Settings for whole group.'),
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => $config->get('locations_collapse_group') ? $config->get('locations_collapse_group') : 'disabled',
      '#description' => $this->t('Check this if you want default state for whole this group is "Collapsed"'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('openy_activity_finder.settings');

    $config->set('backend', $form_state->getValue('backend'))->save();
    $config->set('index', $form_state->getValue('index'))->save();
    $config->set('ages', $form_state->getValue('ages'))->save();
    $config->set('exclude', $form_state->getValue('exclude'))->save();
    $config->set('disable_search_box', $form_state->getValue('disable_search_box'))->save();
    $config->set('disable_spots_available', $form_state->getValue('disable_spots_available'))->save();
    $config->set('hb_modal_text0', $form_state->getValue('hb_modal_text0'))->save();
    $config->set('hb_modal_text1', $form_state->getValue('hb_modal_text1'))->save();
    $config->set('hb_modal_text2', $form_state->getValue('hb_modal_text2'))->save();
    $config->set('hb_modal_text3', $form_state->getValue('hb_modal_text3'))->save();
    $config->set('hb_modal_text4', $form_state->getValue('hb_modal_text4'))->save();
    $config->set('hb_modal_text5', $form_state->getValue('hb_modal_text5'))->save();
    $config->set('schedule_collapse_group', $form_state->getValue('schedule_collapse_group'))->save();
    $config->set('category_collapse_group', $form_state->getValue('category_collapse_group'))->save();
    $config->set('locations_collapse_group', $form_state->getValue('locations_collapse_group'))->save();
    $this->cache->deleteAll();

    parent::submitForm($form, $form_state);
  }

  /**
   * Return Data structure the same as in Program search.
   *
   * @return array
   */
  public function getActivityFinderDataStructure() {
    $request = $this->getRequest();
    $component = [];
    $url = Url::fromRoute('openy_activity_finder.get_results');
    $base_url = $request->getSchemeAndHttpHost();
    try {
      $response = $this->httpClient
        ->get($base_url . $url->toString());
      $data = $response->getBody();
    } catch (RequestException $e) {
      watchdog_exception('error', $e, $e->getMessage());
    }
    if ($data) {
      $data = json_decode($data);
      $component['facets'] = $data->facets;
      $component['groupedLocations'] = $data->groupedLocations;
      return $component;
    }
    return FALSE;
  }
}
