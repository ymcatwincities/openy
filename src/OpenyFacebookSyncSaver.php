<?php

namespace Drupal\openy_facebook_sync;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\openy_mappings\EventMappingRepository;

/**
 * Class OpenyFacebookSyncSaver.
 *
 * @package Drupal\openy_facebook_sync
 */
class OpenyFacebookSyncSaver {

  use StringTranslationTrait;

  const DEFAULT_UID = 1;

  /**
   * Wrapper.
   *
   * @var \Drupal\openy_facebook_sync\OpenyFacebookSyncWrapperInterface
   */
  private $wrapper;

  /**
   * Logger Channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * EventMappingRepo.
   *
   * @var \Drupal\openy_mappings\EventMappingRepository
   */
  private $eventMappingRepo;

  /**
   * Constructor.
   *
   * @param \Drupal\openy_facebook_sync\OpenyFacebookSyncWrapperInterface $wrapper
   *   Wrapper.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger Channel.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param \Drupal\openy_mappings\EventMappingRepository $event_mapping_repo
   *   EventMappingRepo.
   */
  public function __construct(OpenyFacebookSyncWrapperInterface $wrapper, LoggerChannelInterface $logger, EntityTypeManagerInterface $entityTypeManager, EventMappingRepository $event_mapping_repo) {
    $this->wrapper = $wrapper;
    $this->logger = $logger;
    $this->entityTypeManager = $entityTypeManager;
    $this->eventMappingRepo = $event_mapping_repo;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $data = $this->wrapper->getSourceData();

    foreach ($data as $event) {
      // @todo Check whether we need update the node (paragraph).
      // @todo Add setting to create nodes in certain status (published|unpublished).

      // Create event node with title, start and end dates.
      $node = $this->createEvent([
        'title' => $event['name'],
        'start_date' => $event['start_time'],
        'end_date' => $event['end_time'],
      ]);

      // Create description paragraph.
      $paragraph = $this->createDescriptionParagraph([
        'field_prgf_sc_body' => $event['description'],
      ]);
      $paragraph_data = [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ];

      $node->field_landing_body->appendItem($paragraph_data);
      $node->save();

      // Create mapping entity.
      $this->eventMappingRepo->create($node, $paragraph_data, $event);
    }
  }

  /**
   * Create event node.
   *
   * @param array $data
   *   Event data.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Event node.
   */
  private function createEvent(array $data) {
    $storage = $this->entityTypeManager->getStorage('node');

    // Convert date values from 2017-04-08T19:00:00-0500 to 2017-04-08T19:00:00.
    $event_date_values = [
      'value' => \DateTime::createFromFormat('Y-m-d\TH:i:sO', $data['start_date'])->format('Y-m-d\TH:i:s'),
      'end_value' => \DateTime::createFromFormat('Y-m-d\TH:i:sO', $data['end_date'])->format('Y-m-d\TH:i:s'),
    ];

    // Create event node.
    $node = $storage->create([
      'type' => 'event',
      'title' => $data['title'],
      'uid' => self::DEFAULT_UID,
      'field_event_date_range' => $event_date_values,
    ]);

    $node->save();
    return $node;
  }

  /**
   * Create description paragraph.
   *
   * @param array $data
   *   Paragraph data.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Paragraph entity.
   */
  private function createDescriptionParagraph(array $data) {
    $storage = $this->entityTypeManager->getStorage('paragraph');
    $paragraph = $storage->create(['type' => 'simple_content']);

    foreach ($data as $field => $value) {
      $paragraph->set($field, $value);
    }

    $paragraph->save();
    return $paragraph;
  }

}
