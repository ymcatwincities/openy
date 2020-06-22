<?php

/**
 * @file
 * Defines the Open Y Profile install screen by modifying the install form.
 */

use Drupal\openy\Form\ConfigureProfileForm;
use Drupal\openy\Form\ContentSelectForm;
use Drupal\openy\Form\SearchSelectForm;
use Drupal\openy\Form\SearchSolrForm;
use Drupal\openy\Form\TermsOfUseForm;
use Drupal\openy\Form\ThemeSelectForm;
use Drupal\openy\Form\ThirdPartyServicesForm;
use Drupal\openy\Form\UploadFontMessageForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Implements hook_install_tasks().
 */
function openy_install_tasks(&$install_state) {
  return [
    'openy_terms_of_use' => [
      'display_name' => t('Terms and Conditions'),
      'display' => TRUE,
      'run' => INSTALL_TASK_RUN_IF_REACHED
    ],
    'openy_select_search' => [
      'display_name' => t('Select search service'),
      'display' => TRUE,
      'type' => 'form',
      'function' => SearchSelectForm::class,
    ],
    'openy_install_search' => [
      'display_name' => t('Install search'),
      'type' => 'batch',
    ],
    'openy_solr_search' => [
      'display_name' => t('Configure Solr Search'),
      'display' => TRUE,
      'type' => 'form',
      'function' => SearchSolrForm::class,
    ],
    'openy_google_search' => [
      'type' => 'batch',
    ],
    'openy_select_features' => [
      'display_name' => t('Select installation type'),
      'display' => TRUE,
      'type' => 'form',
      'function' => ConfigureProfileForm::class,
    ],
    'openy_install_features' => [
      'type' => 'batch',
    ],
    'openy_select_theme' => [
      'display_name' => t('Select theme'),
      'display' => TRUE,
      'type' => 'form',
      'function' => ThemeSelectForm::class,
    ],
    'openy_install_theme' => [
      'type' => 'batch',
    ],
    'openy_select_content' => [
      'display_name' => t('Import demo content'),
      'display' => TRUE,
      'type' => 'form',
      'function' => ContentSelectForm::class,
    ],
    'openy_import_content' => [
      'type' => 'batch',
    ],
    'openy_set_frontpage' => [
      'type' => 'batch',
    ],
    'openy_discover_broken_paragraphs' => [
      'type' => 'batch',
    ],
    'openy_fix_configured_paragraph_blocks' => [
      'type' => 'batch',
    ],
    'openy_third_party_services' => [
      'display_name' => t('3rd party services'),
      'display' => TRUE,
      'type' => 'form',
      'function' => ThirdPartyServicesForm::class,
    ],
    'openy_upload_font_message' => [
      'display_name' => t('Read font info'),
      'display' => TRUE,
      'type' => 'form',
      'function' => UploadFontMessageForm::class,
    ],
    'openy_gtranslate_place_blocks' => [
      'type' => 'batch',
    ],
    'openy_install_finish' => [
      'type' => 'batch',
    ],
    'openy_terms_and_condition_db_save' => [
      'display' => FALSE,
    ],
  ];
}

/**
 * Create Google Translate block content.
 * Block already added from Open Y Google Translate module configs.
 */
function openy_gtranslate_place_blocks(array &$install_state) {
  $moduleHandler = \Drupal::service('module_handler');
  if (!$moduleHandler->moduleExists('openy_gtranslate')) {
    return ['operations' => []];
  }

  $themes_list = [
    'openy_rose' => '5a698466-f499-4dda-a084-4d61c1d0e902',
    'openy_lily' => '5a698466-f499-4dda-a084-4d61c1d0e777',
    'openy_carnation' => '32fa8958-20d7-41e0-9e7c-a5768bf6dfac',
  ];
  /** @var \Drupal\Core\Entity\EntityTypeManager $entityTypeManager */
  $entityTypeManager = \Drupal::service('entity_type.manager');
  foreach ($themes_list as $theme => $uuid) {
    /** @var \Drupal\block_content\Entity\BlockContent $blockContent */
    $blockContent = $entityTypeManager->getStorage('block_content')->create([
      'type' => 'openy_gtranslate_block',
      'info' => t('Google Translate Block'),
      'uuid' => $uuid,
    ]);
    $blockContent->save();
  }
  return ['operations' => []];
}

/**
 * Mapping for demo content configs.
 *
 * @param null|string $key
 *   Name of the section with demo content.
 *
 * @return array
 *   Mapping array.
 */
function openy_demo_content_configs_map($key = NULL) {
  // Maps installation presets to demo content.
  $map = [
    'complete' => [
      'openy_demo_nalert',
      'openy_demo_nbranch',
      'openy_demo_ncamp',
      'openy_demo_nblog',
      'openy_demo_nnews',
      'openy_demo_nevent',
      'openy_demo_nfacility',
      'openy_demo_nlanding',
      'openy_demo_nmbrshp',
      'openy_demo_nprogram',
      'openy_demo_ncategory',
      'openy_demo_nclass',
      'openy_demo_nsessions',
      'openy_demo_menu',
      'openy_demo_menu_main',
      'openy_demo_menu_footer',
      'openy_demo_webform',
      'openy_demo_ahb',
      'openy_demo_nsocial_post',
      'openy_demo_tcolor',
      'openy_demo_tarea',
      'openy_demo_tblog',
      'openy_demo_tnews',
      'openy_demo_tfacility',
      'openy_demo_tamenities',
      'openy_demo_bfooter',
      'openy_demo_bmicrosites_menu',
      'openy_demo_addthis',
      'openy_demo_bsimple',
      'openy_demo_bamenities',
      'openy_demo_tfitness',
      'openy_demo_taxonomy',
      // @todo Uncomment this code when errors with Campaign demo content are fixed.
      /*
      'openy_demo_ncampaign',
      'openy_demo_ninterstitial',
      */
    ],
    'standard' => [
      'openy_demo_nalert',
      'openy_demo_nlanding',
      'openy_demo_menu',
      'openy_demo_nnews',
      'openy_demo_menu_main',
      'openy_demo_menu_footer',
      'openy_demo_webform',
      'openy_demo_ahb',
      'openy_demo_tcolor',
      'openy_demo_tamenities',
      'openy_demo_bfooter',
      'openy_demo_taxonomy',
    ],
    'standard_virtual_y' => [
      'openy_gc_demo',
    ],
    'extended' => [
      'openy_demo_nalert',
      'openy_demo_nbranch',
      'openy_demo_nevent',
      'openy_demo_nnews',
      'openy_demo_nlanding',
      'openy_demo_nmbrshp',
      'openy_demo_menu',
      'openy_demo_menu_main',
      'openy_demo_menu_footer',
      'openy_demo_webform',
      'openy_demo_ahb',
      'openy_demo_tnews',
      'openy_demo_tcolor',
      'openy_demo_tarea',
      'openy_demo_tamenities',
      'openy_demo_bfooter',
      'openy_demo_addthis',
      'openy_demo_bsimple',
      'openy_demo_bamenities',
      'openy_demo_taxonomy',
    ],

  ];

  return array_key_exists($key, $map) ? $map[$key] : [];
}

/**
 * Create batch for enabling features.
 *
 * @param array $install_state
 *   Installation parameters.
 *
 * @return array
 *   Batch.
 */
function openy_install_features(array &$install_state) {
  $module_operations = [];

  $preset = $install_state['openy']['preset'];
  \Drupal::state()->set('openy_preset', $preset);
  $modules = ConfigureProfileForm::getModulesToInstallWithDependencies($preset);

  foreach ($modules as $module) {
    $module_operations[] = ['openy_enable_module', (array) $module];
  }

  return ['operations' => $module_operations];
}

/**
 * Create batch for installing search.
 *
 * @param array $install_state
 *   Installation parameters.
 *
 * @return array
 *   Batch.
 */
function openy_install_search(array &$install_state) {
  $state = \Drupal::state();
  $module = $install_state['openy']['search']['service'];
  $files = \Drupal::service('extension.list.module')->getList();
  if (isset($install_state['openy']['search']['search_api_server'])) {
    $server = $install_state['openy']['search']['search_api_server'];
    if ($module == 'openy_search_api' && $server == 'solr') {
      $state->set('openy_show_solr_config', '1');
    }
  };

  if (isset($install_state['openy']['search']['google_search_engine_id'])) {
    $state->set('google_search_engine_id', $install_state['openy']['search']['google_search_engine_id']);
  };
  if ($files[$module]->requires) {
    $modules = array_merge(array_keys($files[$module]->requires), (array) $module);
  }
  foreach ($modules as $module) {
    $module_operations[] = ['openy_enable_module', (array) $module];
  }
  return ['operations' => $module_operations];
}

/**
 * Create batch for write google custom search id to configuration.
 *
 * @param array $install_state
 *   Installation parameters.
 */
function openy_google_search(array &$install_state) {
  // Set Google Custom Search Engine ID.
  if (!empty(\Drupal::state()->get('google_search_engine_id'))) {
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('openy_google_search.settings');
    $config->set('google_engine_id', \Drupal::state()->get('google_search_engine_id'));
    $config->save();
  }
}

/**
 * Create batch for install and set default theme.
 *
 * @param array $install_state
 *   Installation parameters.
 *
 * @return array
 *   Batch.
 */
function openy_install_theme(array &$install_state) {
  $theme = $install_state['openy']['theme'];
  if (function_exists(  'drush_print')) {
    drush_print(dt('Theme: %theme', ['%theme' => $theme]));
  }
  $config_factory = Drupal::configFactory();
  // Set the default theme.
  $config_factory
    ->getEditable('system.theme')
    ->set('default', $theme)
    ->save(TRUE);
  $theme_operations[] = ['openy_enable_theme', (array) $theme];
  return ['operations' => $theme_operations];
}

/**
 * Create batch for content import.
 *
 * @param array $install_state
 *   Installation parameters.
 *
 * @return array
 *   Batch.
 */
function openy_import_content(array &$install_state) {
  $module_operations = [];
  $migrate_operations = [];
  $uninstall_operations = [];
  $preset = \Drupal::state()->get('openy_preset') ?: 'complete';
  if (function_exists('drush_print')) {
    drush_print(dt('Preset: %preset', ['%preset' => $preset]));
  }
  $preset_tags = [
    'standard' => 'openy_standard_installation',
    'standard_virtual_y' => 'openy_standard_installation',
    'extended' => 'openy_extended_installation',
    'complete' => 'openy_complete_installation',
  ];
  $migration_tag = $preset_tags[$preset];

  if ($install_state['openy']['content']) {
    // If option has been selected build demo modules installation operations array.
    _openy_import_content_helper($module_operations, $preset);
    // Add migration import by tag to migration operations array.
    $migrate_operations[] = ['openy_import_migration', (array) $migration_tag];
    if ($preset == 'complete') {
      // Add demo content Program Event Framework landing pages manually.
      // Do it as the last step so menu items are in place.
      $migrate_operations[] = ['openy_demo_nlanding_pef_pages', []];
      // Add demo content Activity Finder landing pages manually.
      // Do it as the last step so menu items are in place.
      $migrate_operations[] = ['openy_demo_nlanding_af_pages', []];
    }
    // Build demo modules uninstall array to disable migrations with demo content.
    _openy_remove_migrations_helper($uninstall_operations, $preset);
    if (function_exists('drush_print')) {
      drush_print(dt('Demo content enabled'));
    }
  }
  else {
    // Add homepage alternative if demo content is not enabled.
    $module_operations[] = ['openy_enable_module', (array) 'openy_demo_nhome_alt'];
    $migrate_operations[] = ['openy_import_migration', (array) 'openy_demo_home_alt'];
    $uninstall_operations[] = ['openy_uninstall_module', (array) 'openy_demo_home_alt'];
  }

  // Combine operations module enable before of migrations.
  return ['operations' => array_merge($module_operations, $migrate_operations, $uninstall_operations)];
}

/**
 * Set the homepage whether from demo content or default one.
 */
function openy_set_frontpage(array &$install_state) {
  // Set homepage by node id but checking it first by title only.
  $query = \Drupal::entityQuery('node')
    ->condition('status', 1)
    ->condition('title', 'Open Y');
  $nids = $query->execute();
  $config_factory = Drupal::configFactory();
  $config_factory->getEditable('system.site')->set('page.front', '/node/' . reset($nids))->save();

  return ['operations' => []];
}

/**
 * Fix broken paragraphs which for some reason weren't discovered.
 *
 * @see https://www.drupal.org/node/2889297
 * @see https://www.drupal.org/node/2889298
 */
function openy_discover_broken_paragraphs(array &$install_state) {
  /**
   * Reset data for broken paragraphs using block fields from plugin module.
   *
   * @param array $tables
   * @param string $plugin_id_field
   * @param string $config_field
   */
  $process_paragraphs = function (array $tables, $plugin_id_field, $config_field) {
    foreach ($tables as $table) {
      if (!\Drupal::database()->schema()->tableExists($table)) {
        continue;
      }
      // Select all paragraphs that have "broken" as plugin_id.
      $query = \Drupal::database()->select($table, 'ptable');
      $query->fields('ptable');
      $query->condition('ptable.' . $plugin_id_field, 'broken');
      $broken_paragraphs = $query->execute()->fetchAll();

      // Update to correct plugin_id based on data array.
      foreach ($broken_paragraphs as $paragraph) {
        $data = unserialize($paragraph->{$config_field});
        $query = \Drupal::database()->update($table);
        $query->fields([
          $plugin_id_field => $data['id'],
        ]);
        $query->condition('bundle', $paragraph->bundle);
        $query->condition('entity_id', $paragraph->entity_id);
        $query->condition('revision_id', $paragraph->revision_id);
        $query->condition('langcode', $paragraph->langcode);
        $query->execute();
      }
    }
  };

  $process_paragraphs([
    'paragraph__field_prgf_block',
    'paragraph_revision__field_prgf_block',
  ],
    'field_prgf_block_plugin_id',
    'field_prgf_block_plugin_configuration'
  );
  $process_paragraphs([
    'paragraph__field_prgf_schedules_ref',
    'paragraph_revision__field_prgf_schedules_ref',
  ],
    'field_prgf_schedules_ref_plugin_id',
    'field_prgf_schedules_ref_plugin_configuration'
  );
  $process_paragraphs([
    'paragraph__field_prgf_location_finder',
    'paragraph_revision__field_prgf_location_finder',
  ],
    'field_prgf_location_finder_plugin_id',
    'field_prgf_location_finder_plugin_configuration'
  );
  $process_paragraphs([
    'paragraph__field_prgf_location_finder',
    'paragraph_revision__field_prgf_location_finder',
  ],
    'field_prgf_location_finder_plugin_id',
    'field_prgf_location_finder_plugin_configuration'
  );
  $process_paragraphs([
    'paragraph__field_branch_contacts_info',
    'paragraph_revision__field_branch_contacts_info',
  ],
    'field_branch_contacts_info_plugin_id',
    'field_branch_contacts_info_plugin_configuration'
  );
}

/**
 * Add Block configuration to Branch demo content Group Schedules paragraphs.
 *
 * @see openy_discover_broken_paragraphs().
 */
function openy_fix_configured_paragraph_blocks(array &$install_state) {
  $tables = [
    'paragraph__field_prgf_schedules_ref',
    'paragraph_revision__field_prgf_schedules_ref',
  ];

  foreach ($tables as $table) {
    if (!\Drupal::database()->schema()->tableExists($table)) {
      continue;
    }
    $query = \Drupal::database()
      ->select($table, 'ptable');
    $query->fields('ptable');
    $query->condition('ptable.bundle', 'group_schedules');
    $query->join('node__field_content', 'content', 'content.field_content_target_id = ptable.entity_id');
    $query->condition('content.bundle', 'branch');
    $group_schedule_paragraphs = $query->execute()->fetchAll();

    $location_ids = ['1036', '204', '203', '202'];
    // Update to correct plugin_id based on data array.
    foreach ($group_schedule_paragraphs as $paragraph) {
      $data = unserialize($paragraph->field_prgf_schedules_ref_plugin_configuration);
      $data['enabled_locations'] = array_pop($location_ids);
      $data['label_display'] = 0;
      $query = \Drupal::database()->update($table);
      $query->fields([
        'field_prgf_schedules_ref_plugin_configuration' => serialize($data),
      ]);
      $query->condition('bundle', $paragraph->bundle);
      $query->condition('entity_id', $paragraph->entity_id);
      $query->condition('revision_id', $paragraph->revision_id);
      $query->condition('langcode', $paragraph->langcode);
      $query->execute();
    }
  }
}

/**
 * Run final Open Y profile install procedures.
 */
function openy_install_finish(array &$install_state) {
  // Rerun install all available optional config that appeared after
  // installation of Open Y modules and themes.
  // We need to run this because during a drupal installation
  // optional configuration is installed only once at the
  // end of the core installation process.
  \Drupal::service('config.installer')->installOptionalConfig();
  // Disable the default 'frontpage' Views configuration since it is unused. Due to the fact that this Views
  // configuration is stored in the optional folder there is no way to disable it before optional configs are installed.
  $view = \Drupal::entityTypeManager()->getStorage('view')->load('frontpage');
  if ($view) {
    $view->disable();
    $view->save();
  }
}

/**
 * Demo content import helper.
 *
 * @param array $module_operations
 *   List of module operations.
 * @param string $key
 *   Key of the section in the mapping.
 */
function _openy_import_content_helper(array &$module_operations, $key) {
  $modules = openy_demo_content_configs_map($key);
  if (empty($modules)) {
    return;
  }
  foreach ($modules as $module) {
    $module_operations[] = ['openy_enable_module', (array) $module];
  }
}

/**
 * Demo content migrations remove helper.
 *
 * @param array $module_operations
 *   List of module operations.
 * @param string $key
 *   Key of the section in the mapping.
 */
function _openy_remove_migrations_helper(array &$module_operations, $key) {
  $modules = openy_demo_content_configs_map($key);
  if (empty($modules)) {
    return;
  }
  foreach ($modules as $module) {
    $module_operations[] = ['openy_uninstall_module', (array) $module];
  }
}

/**
 * Enable module.
 *
 * @param string $module_name
 *   Module name.
 *
 * @throws \Drupal\Core\Extension\MissingDependencyException
 */
function openy_enable_module($module_name) {
  /** @var \Drupal\Core\Extension\ModuleInstaller $service */
  $service = \Drupal::service('module_installer');
  $service->install([$module_name]);
}

/**
 * Enable theme.
 *
 * @param string $theme_name
 *   Module name.
 *
 * @throws \Drupal\Core\Extension\ExtensionNameLengthException
 */
function openy_enable_theme($theme_name) {
  /** @var \Drupal\Core\Extension\ThemeInstaller $service */
  $service = \Drupal::service('theme_installer');
  $service->install([$theme_name]);
}

/**
 * Uninstall module.
 *
 * @param string $module_name
 *   Module name.
 */
function openy_uninstall_module($module_name) {
  /** @var \Drupal\Core\Extension\ModuleInstaller $service */
  $service = \Drupal::service('module_installer');
  $service->uninstall([$module_name]);
}

/**
 * Import migrations with specified tag.
 *
 * @param string $migration_tag
 *   Migration tag.
 */
function openy_import_migration($migration_tag) {
  $importer = \Drupal::service('openy_migrate.importer');
  $importer->importByTag($migration_tag);
}

/**
 * Implements hook_form_FORM_ID_alter.
 *
 * This will change the description text for the site slogan field.
 *
 * @param $form
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 * @param $form_id
 */
function openy_form_system_site_information_settings_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  $form['site_information']['site_slogan']['#description'] = t("This will display your association name in the header as per Y USA brand guidelines. Try to use less than 27 characters. The text may get cut off on smaller devices.");
}

/**
 * Implements hook_preprocess_block().
 */
function openy_preprocess_block(&$variables) {
  $variables['base_path'] = base_path();

  // Prevent some blocks from caching
  $preventCacheBlocks = [
    'system_breadcrumb_block',
  ];
  if (in_array($variables['plugin_id'], $preventCacheBlocks)) {
    $variables['#cache']['max-age'] = 0;
  }
}

/**
 * Implements hook_form_FORM_ID_alter.
 *
 * Add description how to use CSS Editor on the theme configuration page.
 */
function openy_form_system_theme_settings_alter(&$form, FormStateInterface $form_state, $form_id) {
  if (isset($form['css_editor'])) {
    // Add short manual how to use CSS Editor inside the theme.
    $types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    $css_node_selectors = array_map(function ($type) {
      return str_replace('_', '-', $type);
    }, array_keys($types));

    $css_editor_info = [
      '#prefix' => '<div class="description">',
      '#markup' => t('In order to change CSS on each particular page you
      should use the following selectors:<br/>
      - .page-node-type-{node type};<br/>
      - .node-id-{node ID};<br/>
      - .path-frontpage.<br/><br/>
      The existing node types are: ' . implode($css_node_selectors, ', ') .'.
      '),
      '#suffix' => '</div>'
    ];
    $form['css_editor']['css_editor_info'] = $css_editor_info;
  }
}

/**
 * Implements hook_help().
 *
 * Add description how to use CSS Editor to help.
 */
function openy_help($route_name, RouteMatchInterface $route_match) {
  switch ($route_name) {
    case 'system.theme_settings_theme':
      $theme = $route_match->getParameter('theme');
      $config = \Drupal::configFactory()->getEditable('css_editor.theme.' . $theme);

      if ($config->get('enabled')) {
        return '<p>' . t('If you need to change CSS on some pages independently, you may use Custom CSS configuration.') . '</p>';
      }
      else {
        return '<p>' . t('If you need to change CSS on some pages independently, you should enable Custom CSS functionality.') . '</p>';
      }

      break;
  }
}

/**
 * Implements hook_install_tasks_alter().
 */
function openy_install_tasks_alter(&$tasks, &$install_state) {
  $new_tasks = [];

  // Looks like we don't have another way to put T&C on the first page.
  $new_tasks['openy_terms_of_use'] = $tasks['openy_terms_of_use'];
  $tasks = array_merge($new_tasks, $tasks);

  // Remove 3rd party services installation task for standard preset.
  if (!empty(\Drupal::state()->get('openy_preset')) &&
    \Drupal::state()->get('openy_preset') == 'standard' &&
    isset($tasks["openy_third_party_services"])) {
      unset($tasks["openy_third_party_services"]);
  }
  // Remove Solr configure installation task for non search_api sorl service.
  if (!\Drupal::state()->get('openy_show_solr_config')) {
    unset($tasks["openy_solr_search"]);
  }

  if (isset($install_state['openy']['search']['service']) &&
    $install_state['openy']['search']['service'] == 'none' ) {
    unset($tasks["openy_install_search"]);
    unset($tasks["openy_solr_search"]);
  }

}

/**
 * Displays the Terms and Conditions form.
 *
 * @param $install_state
 *   An array of information about the current installation state. The T&C flag
 *   will be added here.
 *
 * @return mixed
 *   A T&C form if T&C has not been accepted.
 */
function openy_terms_of_use(&$install_state) {
  // Open Y classes are not included yet on the first installation page,
  // because profile is not installed.
  // That's why we should include T&C form manually.
  if (!class_exists('\Drupal\openy\Form\TermsOfUseForm')) {
    $path = drupal_get_path('profile', 'openy');
    require_once $path . '/src/Form/TermsOfUseForm.php';
  }

  if (!empty($install_state['parameters']['terms_and_conditions'])) {
    return;
  }

  if ($install_state['interactive']) {
    return install_get_form('Drupal\openy\Form\TermsOfUseForm', $install_state);
  }
}

/**
 * Saves T&C accepted version to db. We can't do it on the first step,
 * because db is not configured yet.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 */
function openy_terms_and_condition_db_save(&$install_state) {
  $config_factory = \Drupal::configFactory();
  $config = $config_factory->getEditable('openy.terms_and_conditions.schema');
  $config->set('version', TermsOfUseForm::TERMS_OF_USE_VERSION);
  $config->set('accepted_version', time());
  $config->save();
}
