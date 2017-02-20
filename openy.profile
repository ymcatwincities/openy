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
    'branches' => [
      'openy_demo_nbranch' => [
        'openy_demo_node_branch',
      ],
    ],
    'blog' => [
      'openy_demo_nblog' => [
        'openy_demo_node_blog',
      ],
    ],
    'landing' => [
      'openy_demo_nlanding' => [
        'openy_demo_node_landing',
      ],
    ],
    'programs' => [
      'openy_demo_nprogram' => [
        'openy_demo_node_program',
      ],
      'openy_demo_ncategory' => [
        'openy_demo_node_program_subcategory',
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
  $batch = [];

  // Run required migrations.
  _openy_import_content_helper($batch, 'required');

  // Run optional migrations only if at least one option has been selected.
  if (!empty($install_state['openy']['content'])) {
    _openy_import_content_helper($batch, 'optional');
  }

  // Add home_alt if landing is not included.
  if (!in_array('landing', $install_state['openy']['content'])) {
    $install_state['openy']['content'][] = 'home_alt';
  }

  // Run migrations for selected content.
  foreach ($install_state['openy']['content'] as $content) {
    _openy_import_content_helper($batch, $content);
  }

  return $batch;
}

/**
 * Demo content import helper.
 *
 * @param array $batch
 *   List of batch operations.
 * @param string $key
 *   Key of the section in the mapping.
 */
function _openy_import_content_helper(array &$batch, $key) {
  $modules = openy_demo_content_configs_map($key);
  if (empty($modules)) {
    return;
  }
  foreach ($modules as $key => $migrations) {
    $batch['operations'][] = ['openy_enable_module', (array) $key];
    foreach ($migrations as $migration) {
      $batch['operations'][] = ['openy_import_migration', (array) $migration];
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
