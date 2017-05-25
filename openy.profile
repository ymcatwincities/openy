<?php

/**
 * @file
 * Defines the OpenY Profile install screen by modifying the install form.
 */

use Drupal\openy\Form\ContentSelectForm;
use Drupal\openy\Form\ThirdPartyServicesForm;

/**
 * Implements hook_install_tasks().
 */
function openy_install_tasks() {
  return [
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
    'openy_third_party_services' => [
      'display_name' => t('3rd party services'),
      'display' => TRUE,
      'type' => 'form',
      'function' => ThirdPartyServicesForm::class,
    ],
  ];
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
    'required' => [],
    'optional' => [
      'openy_demo_tcolor' => [
        'openy_demo_taxonomy_term_color',
      ],
      'openy_demo_tarea' => [
        'openy_demo_taxonomy_term_area',
      ],
      'openy_demo_tblog' => [
        'openy_demo_taxonomy_term_blog_category',
      ],
      'openy_demo_tfacility' => [
        'openy_demo_taxonomy_term_facility_type',
      ],
      'openy_demo_bfooter' => [
        'openy_demo_block_content_footer',
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
    'membership' => [
      'openy_demo_nmbrshp' => [
        'openy_demo_node_membership',
      ],
    ],
    'programs' => [
      'openy_demo_nprogram' => [
        'openy_demo_node_program',
      ],
      'openy_demo_ncategory' => [
        'openy_demo_node_program_subcategory',
        'openy_demo_node_session',
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

  if (!empty($install_state['openy']['content']['webform'])) {
    // Install webform feature - it's not handled as content migration.
    openy_enable_module('openy_demo_webform');
    unset($install_state['openy']['content']['webform']);
  }

  // Build required migrations operations arrays.
  _openy_import_content_helper($module_operations, $migrate_operations, 'required');

  // Build optional migrations operations arrays, only if at least one option
  // has been selected.
  if (!empty($install_state['openy']['content'])) {
    _openy_import_content_helper($module_operations, $migrate_operations, 'optional');
  }

  // Add home_alt if landing is not included.
  if (!in_array('landing', $install_state['openy']['content'])) {
    $install_state['openy']['content'][] = 'home_alt';
  }

  // Build migrations operations arrays, for selected content.
  foreach ($install_state['openy']['content'] as $content) {
    _openy_import_content_helper($module_operations, $migrate_operations, $content);
  }

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
 * Import single migration (with dependencies).
 *
 * @param string $migration_id
 *   Migration ID.
 */
function openy_import_migration($migration_id) {
  $importer = \Drupal::service('openy_migrate.importer');
  $importer->import($migration_id);
}
