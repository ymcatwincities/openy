<?php

namespace Drupal\openy_activity_finder\Form;

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
   * Guzzle Http Client.
   *
   * @var GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a new Class.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The http_client.
   */
  public function __construct(Client $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
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

    $backend_options = [
      'openy_activity_finder.solr_backend' => 'Solr Backend (local db)',
    ];

    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('openy_daxko2')){
      $backend_options['openy_daxko2.openy_activity_finder_backend'] = 'Daxko 2 (live API calls)';
    }

    $form['backend'] = [
      '#type' => 'select',
      '#options' => $backend_options,
      '#required' => TRUE,
      '#title' => $this->t('Backend for Activity Finder'),
      '#default_value' => $config->get('backend'),
      '#description' => t(''),
    ];

    $form['ages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ages'),
      '#default_value' => $config->get('ages'),
      '#description' => t('Ages mapping. One per line. "<number of months>,<age display label>". Example: "660,55+"'),
    ];


    $form['exclude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude category -- so we do not display Group Exercises'),
      '#default_value' => $config->get('exclude'),
      '#description' => t('Provide ID of the Program Subcategory to exclude. You do not need to provide this if you use Daxko. Needed only for Solr backend.'),
    ];

    $form['disable_search_box'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Search Box'),
      '#default_value' => $config->get('disable_search_box'),
      '#description' => t('When checked hides search text box (both for Activity Finder and Results page).'),
    ];

    $form['disable_spots_available'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Spots Available'),
      '#default_value' => $config->get('disable_spots_available'),
      '#description' => t('When checked disables Spots Available feature on Results page.'),
    ];

    // Get structure of AF.
    $component = $this->getActivityFinderDataStructure();

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

    if (isset($component['facets']->days_of_week)) {
      $form['collapse']['schedule']['schedule_days'] = [
        '#title' => $this->t('Days'),
        '#type' => 'checkbox',
        '#default_value' => $config->get('schedule_days'),
      ];
    }
    if (isset($component['facets']->static_age_filter)) {
      $form['collapse']['schedule']['schedule_ages'] = [
        '#title' => $this->t('Ages'),
        '#type' => 'checkbox',
        '#default_value' => $config->get('schedule_ages'),
      ];
    }
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

    foreach ($component['facets']->field_category_program as $category) {
      if ($category->filter != '!') {
        $machine_name = 'category_' . str_replace(' ', '_', strtolower($category->filter));
        $form['collapse']['category'][$machine_name] = [
          '#title' => $category->filter,
          '#type' => 'checkbox',
          '#default_value' => $config->get($machine_name),
        ];
      }
    }
    foreach ($component['facets']->field_activity_category as $category) {
      if ($category->filter != '!') {
        $machine_name = 'category_' . str_replace(' ', '_', strtolower($category->filter));
        $form['collapse']['category'][$machine_name] = [
          '#title' => $category->filter,
          '#type' => 'checkbox',
          '#default_value' => $config->get($machine_name),
        ];
      }
    }

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

    foreach ($component['groupedLocations'] as $groupedLocation) {
      $machine_name = 'locations_' . strtolower($groupedLocation->label);
      $form['collapse']['locations'][$machine_name] = [
        '#title' => $groupedLocation->label,
        '#type' => 'checkbox',
        '#default_value' => $config->get($machine_name),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $config \Drupal\Core\Config\Config */
    $config = $this->config('openy_activity_finder.settings');

    $config->set('backend', $form_state->getValue('backend'))->save();

    $config->set('ages', $form_state->getValue('ages'))->save();

    $config->set('exclude', $form_state->getValue('exclude'))->save();

    $config->set('disable_search_box', $form_state->getValue('disable_search_box'))->save();

    $config->set('disable_spots_available', $form_state->getValue('disable_spots_available'))->save();

    $component = $this->getActivityFinderDataStructure();

    foreach ($component['groupedLocations'] as $groupedLocation) {
      $machine_name = 'locations_' . strtolower($groupedLocation->label);
      $config->set($machine_name, $form_state->getValue($machine_name))->save();
    }

    foreach ($component['facets']->field_category_program as $category) {
      if ($category->filter != '!') {
        $machine_name = 'category_' . str_replace(' ', '_', strtolower($category->filter));
        $config->set($machine_name, $form_state->getValue($machine_name))->save();
      }
    }

    foreach ($component['facets']->field_activity_category as $category) {
      if ($category->filter != '!') {
        $machine_name = 'category_' . str_replace(' ', '_', strtolower($category->filter));
        $config->set($machine_name, $form_state->getValue($machine_name))->save();
      }
    }

    $config->set('schedule_days', $form_state->getValue('schedule_days'))->save();
    $config->set('schedule_ages', $form_state->getValue('schedule_ages'))->save();

    $config->set('schedule_collapse_group', $form_state->getValue('schedule_collapse_group'))->save();
    $config->set('category_collapse_group', $form_state->getValue('category_collapse_group'))->save();
    $config->set('locations_collapse_group', $form_state->getValue('locations_collapse_group'))->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Return Data structure the same as in Program search.
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
    }
    catch (RequestException $e) {
      watchdog_exception('error', $e, $e->getMessage());
    }

    if ($data) {
      $data = json_decode($data);
      $component['facets'] = $data->facets;
      $component['groupedLocations'] = $data->groupedLocations;

      return $component;
    }
    return false;
  }
}
