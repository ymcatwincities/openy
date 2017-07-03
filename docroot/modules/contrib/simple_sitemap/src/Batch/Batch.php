<?php

namespace Drupal\simple_sitemap\Batch;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 *
 */
class Batch {

  use StringTranslationTrait;

  /**
   * @var array
   */
  protected $batch;

  /**
   * @var array
   */
  protected $batchInfo;

  const BATCH_TITLE = 'Generating XML sitemap';
  const BATCH_INIT_MESSAGE = 'Initializing batch...';
  const BATCH_ERROR_MESSAGE = 'An error has occurred. This may result in an incomplete XML sitemap.';
  const BATCH_PROGRESS_MESSAGE = 'Processing @current out of @total link types.';

  /**
   * Batch constructor.
   */
  public function __construct() {
    $this->batch = [
      'title' => $this->t(self::BATCH_TITLE),
      'init_message' => $this->t(self::BATCH_INIT_MESSAGE),
      'error_message' => $this->t(self::BATCH_ERROR_MESSAGE),
      'progress_message' => $this->t(self::BATCH_PROGRESS_MESSAGE),
      'operations' => [],
    // __CLASS__ . '::finishGeneration' not working possibly due to a drush error.
      'finished' => [__CLASS__, 'finishGeneration'],
    ];
  }

  /**
   * @param array $batch_info
   */
  public function setBatchInfo(array $batch_info) {
    $this->batchInfo = $batch_info;
  }

  /**
   * Starts the batch process depending on where it was requested from.
   */
  public function start() {
    switch ($this->batchInfo['from']) {

      case 'form':
        // Start batch process.
        batch_set($this->batch);
        break;

      case 'drush':
        // Start drush batch process.
        batch_set($this->batch);
        $this->batch =& batch_get();
        $this->batch['progressive'] = FALSE;
        drush_log($this->t(self::BATCH_INIT_MESSAGE), 'status');
        drush_backend_batch_process();
        break;

      case 'backend':
        // Start backend batch process.
        batch_set($this->batch);
        $this->batch =& batch_get();
        $this->batch['progressive'] = FALSE;
        // todo: Does not take advantage of batch API and eventually runs out of memory on very large sites. Use queue API instead?
        batch_process();
        break;

      case 'nobatch':
        // Call each batch operation the way the Drupal batch API would do, but
        // within one process (so in fact not using batch API here, just
        // mimicking it to avoid code duplication).
        $context = [];
        foreach ($this->batch['operations'] as $i => $operation) {
          $operation[1][] = &$context;
          call_user_func_array($operation[0], $operation[1]);
        }
        $this->finishGeneration(TRUE, $context['results'], []);
        break;
    }
  }

  /**
   * Adds an operation to the batch.
   *
   * @param string $processing_method
   * @param array $data
   */
  public function addOperation($processing_method, array $data) {
    $this->batch['operations'][] = [
      __CLASS__ . '::' . $processing_method, [$data, $this->batchInfo],
    ];
  }

  /**
   * Batch callback function which generates urls to entity paths.
   *
   * @param array $entity_info
   * @param array $batch_info
   * @param array &$context
   *
   * @see https://api.drupal.org/api/drupal/core!includes!form.inc/group/batch/8
   */
  public static function generateBundleUrls(array $entity_info, array $batch_info, &$context) {
    \Drupal::service('simple_sitemap.batch_url_generator')
      ->setContext($context)
      ->setBatchInfo($batch_info)
      ->generateBundleUrls($entity_info);
  }

  /**
   * Batch callback function which generates urls to custom paths.
   *
   * @param array $custom_paths
   * @param array $batch_info
   * @param array &$context
   *
   * @see https://api.drupal.org/api/drupal/core!includes!form.inc/group/batch/8
   */
  public static function generateCustomUrls(array $custom_paths, array $batch_info, &$context) {
    \Drupal::service('simple_sitemap.batch_url_generator')
      ->setContext($context)
      ->setBatchInfo($batch_info)
      ->generateCustomUrls($custom_paths);
  }

  /**
   * Callback function called by the batch API when all operations are finished.
   *
   * @param $success
   * @param $results
   * @param $operations
   *
   * @see https://api.drupal.org/api/drupal/core!includes!form.inc/group/batch/8
   */
  public static function finishGeneration($success, $results, $operations) {
    \Drupal::service('simple_sitemap.batch_url_generator')
      ->finishGeneration($success, $results, $operations);
  }

}
