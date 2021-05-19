<?php

namespace Drupal\openy_programs_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings Form for openy_programs_search.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_programs_search_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openy_programs_search.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_programs_search.settings');

    $form_state->setCached(FALSE);
    $form['#tree'] = TRUE;
    $form['actions'] = [
      '#type' => 'actions',
    ];

    /* @see \Drupal\openy_programs_search\DataStorage::getUrlFromOpenyProgramsSearchSettings */
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Daxko Client ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => t('Add your Daxko account id here. It is most likely a short number, like 1234.'),
    ];

    /* @see \Drupal\openy_programs_search\DataStorage::getDaxkoPageSource */
    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Daxko Domain'),
      '#default_value' => $config->get('domain'),
      '#description' => t('Add your Daxko base url here. It is most likely daxko.com.'),
    ];

    /* @see \Drupal\openy_programs_search\DataStorage::getChildCareRegistrationLink */
    /* @see \Drupal\openy_programs_search\DataStorage::getMapRateOptions */
    $form['base_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Daxko Base URL'),
      '#default_value' => $config->get('base_url'),
      '#description' => t('Add your Daxko base url here. It is most likely https://operations.daxko.com.'),
    ];

    /* @see \Drupal\openy_programs_search\DataStorage::getRegistrationLink */
    $form['registration_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Daxko Registration Path'),
      '#default_value' => $config->get('registration_path'),
      '#description' => t('Add your Daxko registration path. Something like /Online/{{ client_id }}/Programs/Search.mvc/details. Where the path will be prefixed by your Daxko Base URL and the {{ client_id }} token will be replaced by your Daxko Client ID.'),
    ];

    /* @see \Drupal\openy_programs_search\DataStorage::scrapeDaxkoSchoolsByProgram */
    $form['get_schools_by_program_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Daxko Get Schools By Program Path'),
      '#default_value' => $config->get('get_schools_by_program_path'),
      '#description' => t('Add your Daxko scrape schools by program path. Something like /Online/{{ client_id }}/Programs/ChildCareSearch.mvc/locations_by_program?program_id={{ program_id }}. Where the path will be prefixed by your Daxko Base URL, the {{ client_id }} token will be replaced by your Daxko Client ID, and {{ program_id }} will be replaced by program IDs.'),
    ];

    /* @see \Drupal\openy_programs_search\DataStorage::getCategories */
    $form['get_categories_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Daxko Categories Path'),
      '#default_value' => $config->get('get_categories_path'),
      '#description' => t('Add your Daxko Categories path. Something like /Online/{{ client_id }}/Programs/search.mvc/categories. Where the path will be prefixed by your Daxko Base URL, the {{ client_id }} token will be replaced by your Daxko Client ID.'),
    ];

    /* @see \Drupal\openy_programs_search\DataStorage::getMapCategoriesByBranch */
    $form['get_map_categories_by_branch_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Daxko Map Categories By Branch Path'),
      '#default_value' => $config->get('get_map_categories_by_branch_path'),
      '#description' => t('Add your Daxko Map Categories By Branch path. Something like /Online/{{ client_id }}/Programs/search.mvc/categories?branch_id={{ branch_id }}. Where the path will be prefixed by your Daxko Base URL, the {{ client_id }} token will be replaced by your Daxko Client ID, and {{ branch_id }} will be replaced by branch IDs.'),
    ];

    // Exclude Location Map Node ID's.
    /* @see \Drupal\openy_programs_search\DataStorage::getExcludeLocationMapIds */
    $exclude_location_map = $config->get('exclude_location_map');
    $num_exclude_location_map = $form_state->get('num_exclude_location_map');
    if (empty($num_exclude_location_map)) {
      if (count($exclude_location_map) < 1) {
        $num_exclude_location_map = 1;
        $exclude_location_map = [];
      }
      else {
        $num_exclude_location_map = count($exclude_location_map);
      }

      $form_state->set('num_exclude_location_map', $num_exclude_location_map);
    }

    $form['exclude_location_map_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Location Node ID\'s to exclude from Daxko location map'),
      '#prefix' => '<div id="exclude-location-map-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    for ($i = 0; $i < $num_exclude_location_map; $i++) {
      $form['exclude_location_map_fieldset']['exclude_location_map'][$i] = [
        '#type' => 'number',
        '#title' => t('Node ID'),
        '#default_value' => !empty($exclude_location_map[$i]) ? $exclude_location_map[$i] : '',
        '#min' => 1,
      ];
    }
    $form['exclude_location_map_fieldset']['actions']['add_exclude_location_map'] = [
      '#name' => 'add_exclude_location_map',
      '#type' => 'submit',
      '#value' => t('Add one more'),
      '#submit' => array('::addOneExcludeLocationMap'),
      '#ajax' => [
        'callback' => '::addmoreExcludeLocationMapCallback',
        'wrapper' => 'exclude-location-map-fieldset-wrapper',
      ],
    ];
    if ($num_exclude_location_map > 1) {
      $form['exclude_location_map_fieldset']['actions']['remove_exclude_location_map'] = [
        '#name' => 'remove_exclude_location_map',
        '#type' => 'submit',
        '#value' => t('Remove one'),
        '#submit' => array('::removeExcludeLocationMapCallback'),
        '#ajax' => [
          'callback' => '::addmoreExcludeLocationMapCallback',
          'wrapper' => 'exclude-location-map-fieldset-wrapper',
        ],
      ];
    }

    /* @see \Drupal\openy_programs_search\DataStorage::getDaxkoLocationMap */
    // Location name string replace for Daxko Location Map.
    $name_string_replace_location_map = $config->get('name_string_replace_location_map');
    $num_name_string_replace_location_map = $form_state->get('num_name_string_replace_location_map');
    if (empty($num_name_string_replace_location_map)) {
      if (count($name_string_replace_location_map) < 1) {
        $num_name_string_replace_location_map = 1;
        $name_string_replace_location_map = [];
      }
      else {
        $num_name_string_replace_location_map = count($name_string_replace_location_map);
      }

      $form_state->set('num_name_string_replace_location_map', $num_name_string_replace_location_map);
    }

    $form['name_string_replace_location_map_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Location name text find and replace for Daxko location map'),
      '#prefix' => '<div id="name-string-replace-location-map-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    for ($i = 0; $i < $num_name_string_replace_location_map; $i++) {
      $form['name_string_replace_location_map_fieldset']['name_string_replace_location_map'][$i]['find'] = [
        '#type' => 'textfield',
        '#title' => t('Find'),
        '#default_value' => !empty($name_string_replace_location_map[$i]['find']) ? $name_string_replace_location_map[$i]['find'] : '',
      ];
      $form['name_string_replace_location_map_fieldset']['name_string_replace_location_map'][$i]['replace'] = [
        '#type' => 'textfield',
        '#title' => t('Replace with'),
        '#default_value' => !empty($name_string_replace_location_map[$i]['replace']) ? $name_string_replace_location_map[$i]['replace'] : '',
      ];
    }
    $form['name_string_replace_location_map_fieldset']['actions']['add_name_string_replace_location_map'] = [
      '#name' => 'add_name_string_replace_location_map',
      '#type' => 'submit',
      '#value' => t('Add one more'),
      '#submit' => array('::addOneNameStringReplaceLocationMap'),
      '#ajax' => [
        'callback' => '::addmoreNameStringReplaceLocationMapCallback',
        'wrapper' => 'name-string-replace-location-map-fieldset-wrapper',
      ],
    ];
    if ($num_name_string_replace_location_map > 1) {
      $form['name_string_replace_location_map_fieldset']['actions']['remove_name_string_replace_location_map'] = [
        '#name' => 'remove_name_string_replace_location_map',
        '#type' => 'submit',
        '#value' => t('Remove one'),
        '#submit' => array('::removeNameStringReplaceLocationMapCallback'),
        '#ajax' => [
          'callback' => '::addmoreNameStringReplaceLocationMapCallback',
          'wrapper' => 'name-string-replace-location-map-fieldset-wrapper',
        ],
      ];
    }

    // Pinned Programs By Name.
    /* @see \Drupal\openy_programs_search\DataStorage::getLocationsByChildCareProgramId */
    $pinned_programs = $config->get('pinned_programs');
    $num_pinned_programs = $form_state->get('num_pinned_programs');
    if (empty($num_pinned_programs)) {
      if (count($pinned_programs) < 1) {
        $num_pinned_programs = 1;
        $pinned_programs = [];
      }
      else {
        $num_pinned_programs = count($pinned_programs);
      }

      $form_state->set('num_pinned_programs', $num_pinned_programs);
    }

    $form['pinned_programs_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Pinned programs by name for Daxko.'),
      '#prefix' => '<div id="pinned-programs-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    for ($i = 0; $i < $num_pinned_programs; $i++) {
      $form['pinned_programs_fieldset']['pinned_programs'][$i] = [
        '#type' => 'textfield',
        '#title' => t('Program Name'),
        '#default_value' => !empty($pinned_programs[$i]) ? $pinned_programs[$i] : '',
      ];
    }
    $form['pinned_programs_fieldset']['actions']['add_pinned_programs'] = [
      '#name' => 'add_pinned_programs',
      '#type' => 'submit',
      '#value' => t('Add one more'),
      '#submit' => array('::addOnePinnedProgram'),
      '#ajax' => [
        'callback' => '::addmorePinnedProgramCallback',
        'wrapper' => 'pinned-programs-fieldset-wrapper',
      ],
    ];
    if ($num_pinned_programs > 1) {
      $form['pinned_programs_fieldset']['actions']['remove_pinned_programs'] = [
        '#name' => 'remove_pinned_programs',
        '#type' => 'submit',
        '#value' => t('Remove one'),
        '#submit' => array('::removePinnedProgramCallback'),
        '#ajax' => [
          'callback' => '::addmorePinnedProgramCallback',
          'wrapper' => 'pinned-programs-fieldset-wrapper',
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $config \Drupal\Core\Config\Config */
    $config = \Drupal::service('config.factory')->getEditable('openy_programs_search.settings');

    $config->set('client_id', $form_state->getValue('client_id'))->save();
    $config->set('domain', $form_state->getValue('domain'))->save();

    if ($base_url = $form_state->getValue('base_url')) {
      if (preg_match("#https?://#", $base_url) === 0) {
        $base_url = 'https://' . $base_url;
      }
      $config->set('base_url', $base_url)->save();
    }

    $config->set('registration_path', $form_state->getValue('registration_path'))
      ->save();
    $config->set('get_schools_by_program_path', $form_state->getValue('get_schools_by_program_path'))
      ->save();
    $config->set('domain', $form_state->getValue('domain'))
      ->save();
    $config->set('get_categories_path', $form_state->getValue('get_categories_path'))
      ->save();
    $config->set('get_map_categories_by_branch_path', $form_state->getValue('get_map_categories_by_branch_path'))
      ->save();

    // Location exclude map.
    $exclude_location_map = $form_state->getValue(array('exclude_location_map_fieldset', 'exclude_location_map'));
    // Filter out empty exclude location nid values.
    $exclude_location_map = array_filter($exclude_location_map, function ($val) {
      return !empty($val);
    });
    // Sort alphanumerically.
    asort($exclude_location_map);
    // Reset the array keys in the filtered and reordered state.
    $exclude_location_map = array_values($exclude_location_map);
    $config->set('exclude_location_map', $exclude_location_map)->save();

    // Location name map.
    $name_string_replace_location_map = $form_state->getValue(array('name_string_replace_location_map_fieldset', 'name_string_replace_location_map'));
    // Filter out empty find location name text values.
    $name_string_replace_location_map = array_filter($name_string_replace_location_map, function ($val) {
      return !empty($val['find']);
    });
    // Leave order to allow process replacements.
    // Reset the array keys in the filtered and reordered state.
    $name_string_replace_location_map = array_values($name_string_replace_location_map);
    $config->set('name_string_replace_location_map', $name_string_replace_location_map)->save();

    // Pinned Programs.
    $pinned_programs = $form_state->getValue(array('pinned_programs_fieldset', 'pinned_programs'));
    // Filter out empty exclude location nid values.
    $pinned_programs = array_filter($pinned_programs, function ($val) {
      return !empty($val);
    });
    // Leave order to allow additional weighting by order.
    // Reset the array keys in the filtered and reordered state.
    $pinned_programs = array_values($pinned_programs);
    $config->set('pinned_programs', $pinned_programs)->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the exclude_location_map in it.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *  From exclude_location_map_fieldset.
   */
  public function addmoreExcludeLocationMapCallback(array &$form, FormStateInterface $form_state) {
    $num_exclude_location_map = $form_state->get('num_exclude_location_map');
    return $form['exclude_location_map_fieldset'];
  }

  /**
   * Submit handler for the "add-one-more" button for exclude_location_map.
   *
   * Increments the max counter and causes a rebuild.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function addOneExcludeLocationMap(array &$form, FormStateInterface $form_state) {
    $num_exclude_location_map = $form_state->get('num_exclude_location_map');
    $add_button = $num_exclude_location_map + 1;
    $form_state->set('num_exclude_location_map', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button for exclude_location_map.
   *
   * Decrements the max counter and causes a form rebuild.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function removeExcludeLocationMapCallback(array &$form, FormStateInterface $form_state) {
    $num_exclude_location_map = $form_state->get('num_exclude_location_map');
    if ($num_exclude_location_map > 1) {
      $remove_button = $num_exclude_location_map - 1;
      $form_state->set('num_exclude_location_map', $remove_button);
    }
    $form_state->setRebuild();
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the name_string_replace_location_map in it.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *   Form name_string_replace_location_map_fieldset.
   */
  public function addmoreNameStringReplaceLocationMapCallback(array &$form, FormStateInterface $form_state) {
    $num_name_string_replace_location_map = $form_state->get('num_name_string_replace_location_map');
    return $form['name_string_replace_location_map_fieldset'];
  }

  /**
   * Submit handler for the "add-one-more" button for name_string_replace_location_map.
   *
   * Increments the max counter and causes a rebuild.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function addOneNameStringReplaceLocationMap(array &$form, FormStateInterface $form_state) {
    $num_name_string_replace_location_map = $form_state->get('num_name_string_replace_location_map');
    $add_button = $num_name_string_replace_location_map + 1;
    $form_state->set('num_name_string_replace_location_map', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button for name_string_replace_location_map.
   *
   * Decrements the max counter and causes a form rebuild.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function removeNameStringReplaceLocationMapCallback(array &$form, FormStateInterface $form_state) {
    $num_name_string_replace_location_map = $form_state->get('num_name_string_replace_location_map');
    if ($num_name_string_replace_location_map > 1) {
      $remove_button = $num_name_string_replace_location_map - 1;
      $form_state->set('num_name_string_replace_location_map', $remove_button);
    }
    $form_state->setRebuild();
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the pinned_programs in it.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *   Form pinned_programs_fieldset.
   */
  public function addmorePinnedProgramCallback(array &$form, FormStateInterface $form_state) {
    $num_pinned_programs = $form_state->get('num_pinned_programs');
    return $form['pinned_programs_fieldset'];
  }

  /**
   * Submit handler for the "add-one-more" button for pinned_programs.
   *
   * Increments the max counter and causes a rebuild.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function addOnePinnedProgram(array &$form, FormStateInterface $form_state) {
    $num_pinned_programs = $form_state->get('num_pinned_programs');
    $add_button = $num_pinned_programs + 1;
    $form_state->set('num_pinned_programs', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button for pinned_programs.
   *
   * Decrements the max counter and causes a form rebuild.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function removePinnedProgramCallback(array &$form, FormStateInterface $form_state) {
    $num_pinned_programs = $form_state->get('num_pinned_programs');
    if ($num_pinned_programs > 1) {
      $remove_button = $num_pinned_programs - 1;
      $form_state->set('num_pinned_programs', $remove_button);
    }
    $form_state->setRebuild();
  }

}
