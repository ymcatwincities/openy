<?php

namespace Drupal\openy_digital_signage_personify_schedule\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\openy_digital_signage_personify_schedule\Entity\OpenYClassesPersonifySession;
use Drupal\openy_digital_signage_personify_schedule\OpenYSessionsPersonifyFetcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Import sessions from Personify.
 *
 * @ingroup openy_digital_signage_personify_schedule
 */
class PersonifyImportController extends ControllerBase {

  /**
   * Personify Fetcher.
   *
   * @var \Drupal\openy_digital_signage_personify_schedule\OpenYSessionsPersonifyFetcher
   */
  public $personifyFetcher;

  /**
   * Creates data fetcher service.
   *
   * @param OpenYSessionsPersonifyFetcher $personifyFetcher
   *   GroupEx Fetcher.
   */
  public function __construct(OpenYSessionsPersonifyFetcher $personifyFetcher) {
    $this->personifyFetcher = $personifyFetcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openy_digital_signage_personify_schedule.fetcher')
    );
  }

  /**
   * Run batch to import sessions from Personify.
   */
  public function importSessions() {
    $locations = $this->personifyFetcher->getLocations();
    if (empty($locations)) {
      drupal_set_message($this->t('Locations are not set in Personify settings. Please specify locations you want to use and try again.'), 'error');
      $url = new Url('entity.openy_ds_classes_session.collection');
      return new RedirectResponse($url->toString());
    }

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
    /* @var \Drupal\openy_digital_signage_personify_schedule\OpenYSessionsPersonifyFetcher $service */
    $service = \Drupal::service('openy_digital_signage_personify_schedule.fetcher');

    if (empty($context['results']['branch_codes'])) {
      $context['results']['branch_codes'] = $service->getLocationBranchCodes();
      $context['sandbox']['max'] = 1;
      $context['sandbox']['progress'] = 0;
    }

    $context['results']['feed'] = $service->fetchLocationsFeed($context['results']['branch_codes']);

    $context['sandbox']['progress']++;

    $context['message'] = \Drupal::translation()
      ->translate('Pulling Personify feeds: @progress out of @total', [
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
        ->condition('date.value', $context['sandbox']['datetime'], '<')
        ->condition('date.end_value', $context['sandbox']['datetime'], '>')
        ->count();
      $context['sandbox']['max'] = $query->execute();
      $context['sandbox']['current'] = 0;
      $context['sandbox']['progress'] = 0;
    }

    $query = \Drupal::entityQuery('openy_ds_class_personify_session')
      ->condition('id', $context['sandbox']['current'], '>')
      ->condition('date.value', $context['sandbox']['datetime'], '<')
      ->condition('date.end_value', $context['sandbox']['datetime'], '>')
      ->sort('id')
      ->range(0, 10);
    $ids = $query->execute();
    $storage = \Drupal::entityTypeManager()
      ->getStorage('openy_ds_class_personify_session');
    $entities = $storage->loadMultiple($ids);

    if (!$entities) {
      $context['sandbox']['progress'] = $context['sandbox']['max'];
    }

    foreach ($entities as $entity) {
      /* @var OpenYClassesPersonifySession $entity */
      $id = $entity->get('personify_id')->value;
      if (!isset($context['results']['feed'][$id])) {
        $context['results']['to_be_deleted'][] = $entity->id();
      }
      $context['sandbox']['current'] = $id;
      $context['sandbox']['progress']++;
    }

    $context['message'] = \Drupal::translation()
      ->translate('Checking removed sessions: @progress out of @total', [
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
    if (!empty($ids)) {
      $entity_manager = \Drupal::entityTypeManager();
      $storage = $entity_manager->getStorage('openy_ds_class_personify_session');
      $class_storage = $entity_manager->getStorage('openy_ds_classes_session');
      $entities = $storage->loadMultiple($ids);
      foreach ($entities as $entity) {
        $class = $class_storage->loadByProperties([
          'source_id' => $entity->personify_id->value,
        ]);
        if (!empty($class)) {
          $class = reset($class);
          $class->delete();
        }
      }
    }

    $context['sandbox']['progress'] += count($ids);

    $context['message'] = \Drupal::translation()
      ->translate('Checking removed sessions: @progress out of @total', [
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
      $context['sandbox']['max'] = count($context['results']['feed']);
      $context['sandbox']['progress'] = 0;
    }

    $feed_part = array_splice($context['results']['feed'], 0, 10);
    /* @var \Drupal\openy_digital_signage_personify_schedule\OpenYSessionsPersonifyFetcher $service */
    $service = \Drupal::service('openy_digital_signage_personify_schedule.fetcher');
    $service->processData($feed_part);
    if (!isset($context['results']['pulled'])) {
      $context['results']['pulled'] = 0;
    }
    $context['results']['pulled'] += count($feed_part);

    $context['sandbox']['progress'] += count($feed_part);

    $context['message'] = \Drupal::translation()
      ->translate('Importing pulled items: @progress out of @total', [
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
        ->formatPlural(count($results['branch_codes']), 'Imported all sessions for one location.', 'Imported all session for @count locations.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
