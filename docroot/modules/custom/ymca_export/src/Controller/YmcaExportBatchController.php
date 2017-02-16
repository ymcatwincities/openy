<?php

namespace Drupal\ymca_export\Controller;

/**
 * Provides YmcaExportBatchController for csv generation.
 */
class YmcaExportBatchController {

  /**
   * Batch page callback.
   */
  public function content() {
    $operations = [];
    $operations[] = ['ymca_export_process_pages', []];

    $batch = array(
      'title' => t('Exporting...'),
      'operations' => $operations,
      'finished' => 'ymca_export_finished_callback',
      'file' => drupal_get_path('module', 'ymca_export') . '/ymca_export.batch.inc',
    );

    batch_set($batch);
    return batch_process('admin/content/ymca-retention-members');
  }

}
