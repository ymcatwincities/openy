<?php

namespace Drupal\openy_digital_signage_personify_schedule\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Import sessions from Personify.
 *
 * @ingroup openy_digital_signage_personify_schedule
 */
class PersonifyImportController extends ControllerBase {

  /**
   * Run batch to import sessions from Personify.
   */
  public function importSessions() {
    $operations = [
      [[get_class($this), 'fetchFeeds'], []],
      [[get_class($this), 'checkDeleted'], []],
      [[get_class($this), 'removeDeleted'], []],
      [[get_class($this), 'processBatch'], []],
    ];
    $batch = [
      'title' => t('Import Sessions from Personify'),
      'operations' => $operations,
      'finished' => [get_class($this), 'finishBatch'],
    ];
    batch_set($batch);

    $url = Url::fromRoute('view.digital_signage_classes_sessions.sessions_listing');
    return batch_process($url);
  }

  /**
   * Fetches Personify feeds.
   *
   * @param array $context
   *   The batch context.
   */
  public static function fetchFeeds(&$context) {
    $service = \Drupal::service('openy_digital_signage_personify_schedule.fetcher');

    if (empty($context['results']['locations'])) {
      $locations = $service->getLocations();
      $context['results']['locations'] = array_values($locations);
      $context['sandbox']['max'] = count($locations);
      $context['sandbox']['progress'] = 0;
    }

    $location = $context['results']['locations'][$context['sandbox']['progress']];

    /* @var \Drupal\openy_digital_signage_personify_schedule\OpenYSessionsPersonifyFetcher $service */
    $context['results']['feeds'][$location] = $service->fetchLocationFeed($location);

    $context['sandbox']['progress']++;

    $context['message'] = \Drupal::translation()->translate('Pulling Personify feeds: @progress out of @total', [
      '@progress' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['max'],
    ]);

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Fetches Personify feeds.
   *
   * @param array $context
   *   The batch context.
   */
  public static function checkDeleted(&$context) {
    if (!isset($context['sandbox']['max'])) {
      $context['results']['to_be_deleted'] = [];
      $date = new \DateTime();
      $date->setTimestamp(REQUEST_TIME);
      $context['sandbox']['datetime'] = $date->format(DATETIME_DATETIME_STORAGE_FORMAT);

      $query = \Drupal::entityQuery('openy_ds_class_personify_session')
        ->condition('date_time.value', $context['sandbox']['datetime'], '>')
        ->count();
      $context['sandbox']['max'] = $query->execute();
      $context['sandbox']['current'] = 0;
      $context['sandbox']['progress'] = 0;
    }

    $query = \Drupal::entityQuery('openy_ds_class_personify_session')
      ->condition('id', $context['sandbox']['current'], '>')
      ->condition('date_time.value', $context['sandbox']['datetime'], '>')
      ->sort('id')
      ->range(0, 10);
    $ids = $query->execute();
    $storage = \Drupal::entityTypeManager()->getStorage('openy_ds_class_personify_session');
    $entities = $storage->loadMultiple($ids);

    if (!$entities) {
      $context['sandbox']['progress'] = $context['sandbox']['max'];
    }

    foreach ($entities as $entity) {
      $id = $entity->personify_id->value;
      $location = $entity->location->target_id;
      $loc = \Drupal::service('ymca_mappings.location_repository')->findByLocationId($location);
      if (!isset($context['results']['feeds'][$loc->id()][$id])) {
        $context['results']['to_be_deleted'][] = $id;
      }
      $context['sandbox']['current'] = $id;
      $context['sandbox']['progress']++;
    }

    $context['message'] = \Drupal::translation()->translate('Checking removed sessions: @progress out of @total', [
      '@progress' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['max'],
    ]);

    if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Fetches Personify feeds.
   *
   * @param array $context
   *   The batch context.
   */
  public static function removeDeleted(&$context) {
    if (!isset($context['sandbox']['max'])) {
      $context['sandbox']['max'] = count($context['results']['to_be_deleted']);
      $context['sandbox']['progress'] = 0;
    }

    $ids = array_splice($context['results']['to_be_deleted'], 0, 10);
    $storage = \Drupal::entityTypeManager()->getStorage('openy_ds_class_personify_session');
    $entities = $storage->loadMultiple($ids);
    $storage->delete($entities);

    $context['sandbox']['progress'] += count($ids);

    $context['message'] = \Drupal::translation()->translate('Checking removed sessions: @progress out of @total', [
      '@progress' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['max'],
    ]);

    if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Processes the import sessions from Personify.
   *
   * @param array $context
   *   The batch context.
   */
  public static function processBatch(&$context) {
    if (empty($context['results']['pulled'])) {
      $context['sandbox']['max'] = 0;
      foreach ($context['results']['feeds'] as $location_feed) {
        $context['sandbox']['max'] += count($location_feed);
      }
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['location'] = 0;
    }

    $location = $context['results']['locations'][$context['sandbox']['location']];
    if (!$context['results']['feeds'][$location]) {
      $context['sandbox']['location']++;
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
      return;
    }

    $feed_part = array_splice($context['results']['feeds'][$location], 0, 30);
    /* @var \Drupal\openy_digital_signage_personify_schedule\OpenYSessionsPersonifyFetcher $service */
    $service = \Drupal::service('openy_digital_signage_personify_schedule.fetcher');
    $service->processData($feed_part, $location);
    if (!isset($context['results']['pulled'][$location])) {
      $context['results']['pulled'][$location] = 0;
    }
    $context['results']['pulled'][$location] += count($feed_part);

    $context['sandbox']['progress'] += count($feed_part);

    $context['message'] = \Drupal::translation()->translate('Importing pulled items: @progress out of @total', [
      '@progress' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['max'],
    ]);

    if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
    else {
      $context['finished'] = 1;
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
        ->formatPlural(count($results['pulled']), 'Imported all sessions for one location.', 'Imported all session for @count locations.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
