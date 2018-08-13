<?php

/**
 * @file
 * Defines the OpenY Profile install screen by modifying the install form.
 */

use Drupal\openy\Form\ConfigureProfileForm;
use Drupal\openy\Form\ContentSelectForm;
use Drupal\openy\Form\ThirdPartyServicesForm;
use Drupal\openy\Form\UploadFontMessageForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Implements hook_install_tasks().
 */
function openy_install_tasks() {
  return [
    'openy_select_features' => [
      'display_name' => t('Select installation type'),
      'display' => TRUE,
      'type' => 'form',
      'function' => ConfigureProfileForm::class,
    ],
    'openy_install_features' => [
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
  ];
}

/**
 * Create Google Translate block content.
 * Block already added from OpenY Google Translate module configs.
 */
function openy_gtranslate_place_blocks(array &$install_state) {
  $moduleHandler = \Drupal::service('module_handler');
  if (!$moduleHandler->moduleExists('openy_gtranslate')) {
    return ['operations' => []];
  }

  $themes_list = [
    'openy_rose' => '5a698466-f499-4dda-a084-4d61c1d0e902',
    'openy_lily' => '5a698466-f499-4dda-a084-4d61c1d0e777',
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
  // Maps selection to migrations.
  $map = [
    'complete' => [
      'openy_demo_nalert' => [],
      'openy_demo_nbranch' => [],
      'openy_demo_ncamp' => [],
      'openy_demo_nblog' => [],
      'openy_demo_nnews' => [],
      'openy_demo_nevent' => [],
      'openy_demo_nfacility' => [],
      'openy_demo_nlanding' => [],
      'openy_demo_nmbrshp' => [],
      'openy_demo_nprogram' => [],
      'openy_demo_ncategory' => [],
      'openy_demo_nclass' => [],
      'openy_demo_nsessions' => [],
      'openy_demo_menu_main' => [],
      'openy_demo_menu_footer' => [],
      'openy_demo_webform' => [],
      'openy_demo_ahb' => [],
      'openy_demo_nsocial_post' => [],
      'openy_demo_tcolor' => [],
      'openy_demo_tarea' => [],
      'openy_demo_tblog' => [],
      'openy_demo_tnews' => [ ],
      'openy_demo_tfacility' => [],
      'openy_demo_tamenities' => [],
      'openy_demo_bfooter' => [],
      'openy_demo_bmicrosites_menu' => [],
      'openy_demo_addthis' => [],
      'openy_demo_bsimple' => [],
      'openy_demo_bamenities' => [],
    ],
    'standard' => [
      'openy_demo_nalert' => [],
      'openy_demo_nlanding' => [],
      'openy_demo_menu_main' => [],
      'openy_demo_menu_footer' => [],
      'openy_demo_webform' => [],
      'openy_demo_ahb' => [],
      'openy_demo_tcolor' => [],
      'openy_demo_tamenities' => [],
      'openy_demo_bfooter' => [],
    ],
    'required' => [],
    'optional' => [
      'openy_demo_ahb' => [
        'openy_demo_entity_ahb',
      ],
      'openy_demo_nsocial_post' => [
        'openy_demo_node_social_post',
      ],
      'openy_demo_tcolor' => [
        'openy_demo_taxonomy_term_color',
      ],
      'openy_demo_tarea' => [
        'openy_demo_taxonomy_term_area',
      ],
      'openy_demo_tblog' => [
        'openy_demo_taxonomy_term_blog_category',
      ],
      'openy_demo_tnews' => [
        'openy_demo_taxonomy_term_news_category',
      ],
      'openy_demo_tfacility' => [
        'openy_demo_taxonomy_term_facility_type',
      ],
      'openy_demo_tfitness' => [
        'openy_demo_taxonomy_term_fitness_category',
      ],
      'openy_demo_tamenities' => [
        'openy_demo_taxonomy_term_amenities',
      ],
      'openy_demo_bfooter' => [
        'openy_demo_block_content_footer',
      ],
      'openy_demo_bmicrosites_menu' => [
        'openy_demo_block_microsites_menu',
      ],
      'openy_demo_addthis' => [],
      'openy_demo_bsimple' => [
        'openy_demo_block_content_simple',
      ],
      'openy_demo_bamenities' => [
        'openy_demo_block_content_amenities',
      ],
    ],
    'alerts' => [
      'openy_demo_nalert' => [
        'openy_demo_node_alert',
      ],
    ],
    'branches' => [
      'openy_demo_nbranch' => [
        'openy_demo_node_branch',
      ],
    ],
    'camps' => [
      'openy_demo_ncamp' => [
        'openy_demo_node_camp',
        'openy_demo_node_camp_blog',
      ],
    ],
    'blog' => [
      'openy_demo_nblog' => [
        'openy_demo_node_blog',
      ],
    ],
    'news' => [
      'openy_demo_nnews' => [
        'openy_demo_node_news',
        'openy_demo_news_landing',
        'openy_demo_menu_link_footer_news',
      ],
    ],
    'event' => [
      'openy_demo_nevent' => [
        'openy_demo_node_event',
        'openy_demo_event_landing',
      ],
    ],
    'facility' => [
      'openy_demo_nfacility' => [
        'openy_demo_node_facility',
      ],
    ],
    'landing' => [
      'openy_demo_nlanding' => [
        'openy_demo_node_landing',
      ],
    ],
    /*
    'campaign' => [
      'openy_demo_ncampaign' => [
        'openy_demo_node_campaign_page',
        'openy_demo_node_campaign',
      ],
    ],
    'interstitial' => [
      'openy_demo_ninterstitial' => [
        'openy_demo_node_interstitial_page',
      ],
    ],
    */
    'membership' => [
      'openy_demo_nmbrshp' => [
        'openy_demo_node_membership',
      ],
    ],
    'programs' => [
      'openy_demo_nprogram' => [
        'openy_demo_node_program',
      ],
    ],
    'categories' => [
      'openy_demo_ncategory' => [
        'openy_demo_node_program_subcategory',
      ],
    ],
    'activities' => [
      'openy_demo_nclass' => [
        'openy_demo_node_activity'
      ]
    ],
    'classes_01' => [
      'openy_demo_nclass' => [
        'openy_demo_node_class_01',
      ],
    ],
    'classes_02' => [
      'openy_demo_nclass' => [
        'openy_demo_node_class_02',
      ],
    ],
    'sessions_01' => [
      'openy_demo_nsessions' => [
        'openy_demo_node_session_01',
      ],
    ],
    'sessions_02' => [
      'openy_demo_nsessions' => [
        'openy_demo_node_session_02',
      ],
    ],
    'sessions_03' => [
      'openy_demo_nsessions' => [
        'openy_demo_node_session_03',
      ],
    ],
    'sessions_04' => [
      'openy_demo_nsessions' => [
        'openy_demo_node_session_04',
      ],
    ],
    'home_alt' => [
      'openy_demo_nhome_alt' => [
        'openy_demo_node_home_alt_landing',
      ],
    ],
    'menus' => [
      'openy_demo_menu_main' => [
        'openy_demo_menu_link_main',
      ],
      'openy_demo_menu_footer' => [
        'openy_demo_menu_link_footer',
      ],
    ],
    'webform' => [
      'openy_demo_webform' => [
        'openy_demo_webform_content',
      ],
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
  $preset = \Drupal::state()->get('openy_preset') ?: 'complete';
  $preset_tags = [
    'standard' => 'openy_standard_installation',
    'extended' => 'openy_extended_installation',
    'complete' => 'openy_complete_installation',
  ];
  $migration_tag = $preset_tags[$preset];

  if ($install_state['openy']['content']) {
    // If option  has been selected build demo modules installation operations array.
    _openy_import_content_helper($module_operations, $migrate_operations, $preset);
    // Build migrations import operation array.
    $migrate_operations[] = ['openy_import_migration', (array) $migration_tag ];
  }
  // @todo Fix below code for Import GroupExPro classes and PEF pages.
  // Import GroupExPro classes. They are not handled as content migration.
  // $importGxp = !empty($install_state['openy']['content']['gxp']);
  // unset($install_state['openy']['content']['gxp']);

  // Add demo content Program Event Framework landing pages manually. Do it as
  // so last step so menu items are in place.
  // $migrate_operations[] = ['openy_demo_nlanding_pef_pages', []];

  // if ($importGxp) {
  //   openy_enable_module('openy_gxp');
  //  $migrate_operations[] = ['openy_gxp_import_tc', []];
  // }
  
  // @todo Add home_alt if landing is not included.
  // Combine operations module enable before of migrations.
  return ['operations' => array_merge($module_operations, $migrate_operations)];
}

/**
 * Set the homepage whether from demo content or default one.
 */
function openy_set_frontpage(array &$install_state) {
  // Set homepage by node id but checking it first by title only.
  $query = \Drupal::entityQuery('node')
    ->condition('status', 1)
    ->condition('title', 'OpenY');
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
 * Demo content import helper.
 *
 * @param array $module_operations
 *   List of module operations.
 * @param array $migrate_operations
 *   List of migrate operations.
 * @param string $key
 *   Key of the section in the mapping.
 */
function _openy_import_content_helper(array &$module_operations, array &$migrate_operations, $key) {
  $modules = openy_demo_content_configs_map($key);
  if (empty($modules)) {
    return;
  }
  foreach ($modules as $key => $migrations) {
    $module_operations[] = ['openy_enable_module', (array) $key];
    foreach ($migrations as $migration) {
      $migrate_operations[] = ['openy_import_migration', (array) $migration];
    }
  }
}

/**
 * Enable module with demo content.
 *
 * @param string $module_name
 *   Module name.
 */
function openy_enable_module($module_name) {
  /** @var \Drupal\Core\Extension\ModuleInstaller $service */
  $service = \Drupal::service('module_installer');
  $service->install([$module_name]);
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
