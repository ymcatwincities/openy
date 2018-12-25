<?php

namespace Drupal\openy_pef_gxp_sync\syncer;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\openy_pef_gxp_sync\Entity\OpenYPefGxpMapping;
use Drupal\openy_pef_gxp_sync\OpenYPefGxpMappingRepository;

/**
 * Class Saver.
 *
 * @package Drupal\openy_pef_gxp_sync\syncer
 */
class Saver implements SaverInterface {

  /**
   * Wrapper.
   *
   * @var \Drupal\openy_pef_gxp_sync\syncer\WrapperInterface
   */
  protected $wrapper;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * Program subcategory.
   *
   * @var integer
   */
  protected $programSubcategory;

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Mapping repository.
   *
   * @var \Drupal\openy_pef_gxp_sync\OpenYPefGxpMappingRepository
   */
  protected $mappingRepository;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Saver constructor.
   *
   * @param \Drupal\openy_pef_gxp_sync\syncer\WrapperInterface $wrapper
   *   Wrapper.
   * @param \Drupal\Core\Logger\LoggerChannel $loggerChannel
   *   Logger.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Config.
   * @param \Drupal\openy_pef_gxp_sync\OpenYPefGxpMappingRepository $mappingRepository
   *   Mapping repository.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(WrapperInterface $wrapper, LoggerChannel $loggerChannel, ImmutableConfig $config, OpenYPefGxpMappingRepository $mappingRepository, EntityTypeManager $entityTypeManager) {
    $this->wrapper = $wrapper;
    $this->logger = $loggerChannel;
    $this->config = $config;
    $this->mappingRepository = $mappingRepository;
    $this->entityTypeManager = $entityTypeManager;

    $config = \Drupal::configFactory()->get('openy_gxp.settings');
    $this->programSubcategory = $config->get('activity');
  }

  /**
   * {@inheritdoc}
   */
  public function clean() {
    $data = $this->wrapper->getProcessedData();
    if (!$this->config->get('is_production')) {
      $data = array_slice($data, 0, 2);
    }

    // Get session IDs from source data.
    $sourceIds = array_map(function ($item) {
      return $item['class_id'];
    }, $data);

    // Get session IDs from mapping items.
    $mappings = \Drupal::database()->select('openy_pef_gxp_mapping', 'mapping')
      ->fields('mapping', ['session', 'product_id'])
      ->execute()
      ->fetchAllAssoc('product_id');
    $existingIds = array_keys($mappings);

    $nodeStorage = $this->entityTypeManager->getStorage('node');

    // Compare the arrays and remove orphaned local sessions.
    $diff = array_diff($existingIds, $sourceIds);

    // Foreach all found ids and delete corresponding sessions.
    foreach ($diff as $productIdToDelete) {
      $mappingToDelete = $mappings[$productIdToDelete];
      $existingSession = $nodeStorage->load($mappingToDelete->session);
      if ($existingSession) {
        $message = 'The source data with class ID @class for session @session was not found. The session will be deleted.';
        $this->logger->info($message, [
          '@class' => $productIdToDelete,
          '@session' => $existingSession->id(),
        ]);
        $nodeStorage->delete([$existingSession]);
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $data = $this->wrapper->getProcessedData();

    if (!$this->config->get('is_production')) {
      $data = array_slice($data, 0, 2);
    }

    $nodeStorage = $this->entityTypeManager->getStorage('node');

    // Loop over processed data and create session entities.
    foreach ($data as $item) {
      $hash = (string) crc32(serialize($item));

      // Check if corresponding session exists and is up to date.
      $mappingItems = $this->mappingRepository->getMappingByHash($hash);
      if ($mappingItems) {

        // Compare source item & existing one.
        $mappingItem = reset($mappingItems);
        if ($mappingItem->hash->value === $hash) {
          // Item exists and identical. Skipping...
          continue;
        }

        // Source data is changed. Let's remove current item.
        $existingSession = $nodeStorage->load($mappingItem->session->target_id);
        if ($existingSession) {
          $message = 'The source data with class ID @class for session @session was updated. The session will be recreated.';
          $this->logger->info($message, [
            '@class' => $item['class_id'],
            '@session' => $existingSession->id(),
          ]);
          $nodeStorage->delete([$existingSession]);
        }
      }

      try {
        $session = $this->createSession($item);
        $mapping  = OpenYPefGxpMapping::create(
          [
            'session' => $session,
            'hash' => crc32(serialize($item)),
            'product_id' => $item['class_id'],
          ]
        );
        $mapping->save();
      }
      catch (\Exception $exception) {
        $this->logger
          ->error(
            'Failed to create a session with error message: @message',
            ['@message' => $exception->getMessage()]
          );
        continue;
      }
    }
  }

  /**
   * Create session.
   *
   * @param array $class
   *   Class properties.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   Session node.
   *
   * @throws \Exception
   */
  private function createSession(array $class) {
    // Get/Create class.
    try {
      $sessionClass = $this->getClass($class);
    }
    catch (\Exception $exception) {
      $message = sprintf(
        'Failed to get class for Groupex class %s with message %s',
        $class['class_id'],
        $exception->getMessage()
      );
      throw new \Exception($message);
    }

    // Get session time paragraph.
    try {
      $sessionTime = $this->getSessionTime($class);
    }
    catch (\Exception $exception) {
      $message = sprintf(
        'Failed to get session time for Groupex class %s with message %s',
        $class['class_id'],
        $exception->getMessage()
      );
      throw new \Exception($message);
    }

    // Get session_exclusions.
    $sessionExclusions = $this->getSessionExclusions($class);

    $session = Node::create([
      'uid' => 1,
      'lang' => 'und',
      'type' => 'session',
      'title' => $class['title'],
    ]);

    $session->set('field_session_class', $sessionClass);
    $session->set('field_session_time', $sessionTime);
    $session->set('field_session_exclusions', $sessionExclusions);
    $session->set('field_session_location', ['target_id' => $class['ygtc_location_id']]);
    $session->set('field_session_room', $class['studio']);
    $session->set('field_session_instructor', $class['instructor']);

    $session->setUnpublished();

    $session->save();
    $this->logger
      ->debug(
        'Session has been created. ID: @id',
        ['@id' => $session->id()]
      );

    return $session;
  }

  /**
   * Get session exclusions.
   *
   * @param array $class
   *   Class properties.
   *
   * @return array
   *   Exclusions.
   */
  private function getSessionExclusions(array $class) {
    $exclusions = [];
    if (isset($class['exclusions'])) {
      foreach ($class['exclusions'] as $exclusion) {
        $exclusionStart = (new \DateTime($exclusion . '00:00:00'))->format('Y-m-d\TH:i:s');
        $exclusionEnd = (new \DateTime($exclusion . '24:00:00'))->format('Y-m-d\TH:i:s');
        $exclusions[] = [
          'value' => $exclusionStart,
          'end_value' => $exclusionEnd,
        ];
      }
    }

    return $exclusions;
  }

  /**
   * Create session time paragraph.
   *
   * @param array $class
   *   Class properties.
   *
   * @return array
   *   Paragraph ID & Revision ID.
   *
   * @throws \Exception
   */
  private function getSessionTime(array $class) {
    $times = $class['patterns'];

    // Convert to UTC timezone to save to database.
    $siteTimezone = new \DateTimeZone(drupal_get_user_timezone());
    $gmtTimezone = new \DateTimeZone('GMT');

    $startTime = new \DateTime($class['start_date'] . ' ' . $times['start_time'] . ':00', $siteTimezone);
    $startTime->setTimezone($gmtTimezone);

    $endTime = new \DateTime($class['end_date'] . ' ' . $times['end_time'] . ':00', $siteTimezone);
    $endTime->setTimezone($gmtTimezone);

    $startDate = $startTime->format(DATETIME_DATETIME_STORAGE_FORMAT);
    $endDate = $endTime->format(DATETIME_DATETIME_STORAGE_FORMAT);

    if (!isset($times['day']) || empty($times['day'])) {
      throw new \Exception(sprintf('Day was not found for the class %s', $class['class_id']));
    }

    $days[] = strtolower($times['day']);

    $paragraph = Paragraph::create(['type' => 'session_time']);
    $paragraph->set('field_session_time_days', $days);
    $paragraph->set('field_session_time_date', ['value' => $startDate, 'end_value' => $endDate]);
    $paragraph->isNew();
    $paragraph->save();

    return [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
  }

  /**
   * Create class or use existing.
   *
   * @param array $class
   *   Class properties.
   *
   * @return array
   *   Class.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function getClass(array $class) {
    // Try to get existing activity.
    $existingActivities = \Drupal::entityQuery('node')
      ->condition('title', $class['category'])
      ->condition('type', 'activity')
      ->condition('field_activity_category', $this->programSubcategory)
      ->execute();

    if (!$existingActivities) {
      // No activities found. Create one.
      $activity = Node::create([
        'uid' => 1,
        'lang' => 'und',
        'type' => 'activity',
        'title' => $class['category'],
        'field_activity_description' => [
          [
            'value' => $class['description'],
            'format' => 'full_html'
          ]
        ],
        'field_activity_category' => [['target_id' => $this->programSubcategory]],
      ]);

      // @todo Check whether we need unpublish the entity.
      $activity->save();
    }
    else {
      // Use the first found existing one.
      $activityId = reset($existingActivities);
      $activity = Node::load($activityId);
    }

    // Try to find class.
    $existingClasses = \Drupal::entityQuery('node')
      ->condition('title', $class['title'])
      ->condition('field_class_activity', $activity->id())
      ->condition('field_class_description', $class['description'])
      ->execute();

    if (!empty($existingClasses)) {
      $classId = reset($existingClasses);
      $class = Node::load($classId);
    }
    else {
      $paragraphs = [];
      foreach (['class_sessions', 'branches_popup_class'] as $type) {
        $paragraph = Paragraph::create(['type' => $type]);
        $paragraph->isNew();
        $paragraph->save();
        $paragraphs[] = [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }
      $class = Node::create([
        'uid' => 1,
        'lang' => 'und',
        'type' => 'class',
        'title' => $class['title'],
        'field_class_description' => [
          [
            'value' => $class['description'],
            'format' => 'full_html'
          ]
        ],
        'field_class_activity' => [
          [
            'target_id' => $activity->id()
          ]
        ],
        'field_content' => $paragraphs,
      ]);

      // @todo Check whether we need unpublish the entity.
      $class->save();
    }

    return [
      'target_id' => $class->id(),
      'target_revision_id' => $class->getRevisionId(),
    ];
  }

}
