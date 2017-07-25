<?php

namespace Drupal\openy_digital_signage_groupex_schedule\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Import sessions from GroupEx Pro.
 *
 * @ingroup openy_digital_signage_groupex_schedule
 */
class GroupExImportController extends ControllerBase {

  /**
   * Run batch to import sessions from GroupEx Pro.
   */
  public function importSessions() {
    $operations = [
      [
        [get_class($this), 'processBatch'],
        [],
      ],
    ];
    $batch = [
      'title' => t('Import Sessions from GroupEx Pro'),
      'operations' => $operations,
      'finished' => [get_class($this), 'finishBatch'],
    ];
    batch_set($batch);

    $url = Url::fromRoute('view.digital_signage_classes_sessions.sessions_listing');
    return batch_process($url);
  }

  /**
   * Processes the import sessions from GroupEx Pro.
   *
   * @param array $context
   *   The batch context.
   */
  public static function processBatch(&$context) {
    if (empty($context['sandbox'])) {
      $config = \Drupal::configFactory()
        ->get('openy_digital_signage_groupex_schedule.settings');
      $locations = $config->get('locations');
      $context['sandbox']['locations'] = array_values($locations);
      $context['sandbox']['max'] = count($locations);
      $context['sandbox']['progress'] = 0;
    }

    $location = $context['sandbox']['locations'][$context['sandbox']['progress']];

    /* @var \Drupal\openy_digital_signage_groupex_schedule\OpenYSessionsGroupExFetcher $service */
    $service = \Drupal::service('openy_digital_signage_groupex_schedule.fetcher');
    $service->fetchLocation($location);
    $context['results'][] = $location;

    $context['sandbox']['progress']++;

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Finish batch.
   *
   * @param bool $success
   *   Status.
   * @param array $results
   *   Results.
   * @param array $operations
   *   List of performed operations.
   */
  public static function finishBatch($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()
        ->formatPlural(count($results), 'Imported all sessions for one location.', 'Imported all session for @count locations.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
