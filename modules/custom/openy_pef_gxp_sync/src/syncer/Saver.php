<?php

namespace Drupal\openy_pef_gxp_sync\syncer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
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
   * @var int
   */
  protected $programSubcategory;

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
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Saver constructor.
   *
   * @param \Drupal\openy_pef_gxp_sync\syncer\WrapperInterface $wrapper
   *   Wrapper.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   Logger channel.
   * @param \Drupal\openy_pef_gxp_sync\OpenYPefGxpMappingRepository $mappingRepository
   *   Mapping repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   */
  public function __construct(WrapperInterface $wrapper, LoggerChannelInterface $loggerChannel, OpenYPefGxpMappingRepository $mappingRepository, EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->wrapper = $wrapper;
    $this->logger = $loggerChannel;
    $this->mappingRepository = $mappingRepository;
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;

    $openyGxpConfig = $this->configFactory->get('openy_gxp.settings');
    $this->programSubcategory = $openyGxpConfig->get('activity');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function save() {
    $this->logger->info('%name started.', ['%name' => get_class($this)]);
    $this->wrapper->setSavedHashes();

    $data = $this->wrapper->getDataToCreate();
    if (empty($data)) {
      $this->logger->info('%name finished. Nothing new to create.', ['%name' => get_class($this)]);
    }

    foreach ($data as $locationId => $locationData) {
      foreach ($locationData as $classId => $classItems) {
        foreach ($classItems as $class) {

          try {
            $session = $this->createSession($class);
            $mapping = $this->entityTypeManager->getStorage('openy_pef_gxp_mapping')->create(
              [
                'session' => $session,
                'product_id' => $classId,
                'location_id' => $locationId,
              ]
            );
            $mapping->save();
          }
          catch (Exception $exception) {
            $this->logger
              ->error(
                'Failed to create a session with error message: @message',
                ['@message' => $exception->getMessage()]
              );
            continue;
          }
        }
      }
    }
    $this->logger->info('%name finished.', ['%name' => get_class($this)]);
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
    catch (Exception $exception) {
      $message = sprintf(
        'Failed to get class for Groupex class %s with message %s',
        $class['class_id'],
        $exception->getMessage()
      );
      throw new Exception($message);
    }

    // Get session time paragraph.
    try {
      $sessionTime = $this->getSessionTime($class);
    }
    catch (Exception $exception) {
      $message = sprintf(
        'Failed to get session time for Groupex class %s with message %s',
        $class['class_id'],
        $exception->getMessage()
      );
      throw new Exception($message);
    }

    // Get session_exclusions.
    $sessionExclusions = $this->getSessionExclusions($class);

    // Get register link.
    $sessionRegLink = $this->getSessionRegLink($class);

    $session = Node::create([
      'uid' => 1,
      'lang' => 'und',
      'type' => 'session',
      'title' => $class['title'],
    ]);

    $session->set('field_session_class', $sessionClass);
    $session->set('field_session_time', $sessionTime);
    $session->set('field_session_exclusions', $sessionExclusions);
    $session->set('field_session_location', ['target_id' => $class['location_id']]);
    $session->set('field_session_room', $class['studio']);
    $session->set('field_session_instructor', $class['instructor']);
    $session->set('field_session_reg_link', $sessionRegLink);

    $session->setUnpublished();

    $session->save();
    $this->logger
      ->debug(
        'Session has been created. ID: %id, Location ID: %loc_id, Class ID: %class_id',
        [
          '%id' => $session->id(),
          '%loc_id' => $class['location_id'],
          '%class_id' => $class['class_id'],
        ]
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
   *
   * @throws \Exception
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
  * Get register link.
  *
  * @param array $class
  *   Class properties.
  *
  * @return array
  *   Link field array.
  *
  * @throws \Exception
  */
  private function getSessionRegLink(array $class) {
    $regLink = [];

    if (isset($class['reservation_id']) && !empty($class['reservation_id'])) {
      $regLink = [
        'uri' => 'https://www.groupexpro.com/gxp/reservations/start/index/' . $class['reservation_id'],
        'title' => 'Sign Up',
      ];
    }

    return $regLink;
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
    $siteTimezone = new \DateTimeZone(date_default_timezone_get());
    $gmtTimezone = new \DateTimeZone('GMT');

    $startTime = new \DateTime($class['start_date'] . ' ' . $times['start_time'] . ':00', $siteTimezone);
    $startTime->setTimezone($gmtTimezone);

    $endTime = new \DateTime($class['end_date'] . ' ' . $times['end_time'] . ':00', $siteTimezone);
    $endTime->setTimezone($gmtTimezone);

    $startDate = $startTime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $endDate = $endTime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    $days = [];
    if (isset($times['day']) && !empty($times['day'])) {
      $days[] = strtolower($times['day']);
    }

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
   *   Class data.
   *
   * @return array
   *   Class references.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function getClass(array $class) {
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    // Try to get existing activity.
    $existingActivities = $nodeStorage->getQuery()
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
            'format' => 'full_html',
          ],
        ],
        'field_activity_category' => [['target_id' => $this->programSubcategory]],
      ]);

      $activity->save();
    }
    else {
      // Use the first found existing one.
      $activityId = reset($existingActivities);
      $activity = $nodeStorage->load($activityId);
    }

    // Try to find class.
    $existingClasses = $nodeStorage->getQuery()
      ->condition('title', $class['title'])
      ->condition('field_class_activity', $activity->id())
      ->condition('field_class_description', $class['description'])
      ->execute();

    if (!empty($existingClasses)) {
      $classId = reset($existingClasses);
      $class = $nodeStorage->load($classId);
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
            'format' => 'full_html',
          ],
        ],
        'field_class_activity' => [
          [
            'target_id' => $activity->id(),
          ],
        ],
        'field_content' => $paragraphs,
      ]);

      $class->save();
    }

    return [
      'target_id' => $class->id(),
      'target_revision_id' => $class->getRevisionId(),
    ];
  }

}
