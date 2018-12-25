<?php

/**
 * Implements hook_drush_command().
 */
function openy_gxp_drush_command() {
  $commands['openy-gxp-sync'] = [
    'description' => 'Reimport all GroupExPro classes',
    'aliases' => ['gxp-sync'],
    'arguments' => [],
    'examples' => [
      'drush gxp-sync' => 'Reimports all the GroupExPro classes',
    ],
  ];

  return $commands;
}

/**
 * Command 'openy-gxp-sync'.
 */
function drush_openy_gxp_sync() {
  // First rollback all the data completely.
  drush_migrate_tools_migrate_rollback('gxp_offerings_import');

  // Next run the import.
  $form_state = new \Drupal\Core\Form\FormState();
  \Drupal::formBuilder()->submitForm('Drupal\openy_gxp\Form\ImportForm', $form_state);
  dt('Import from GroupExPro completed.');
}
