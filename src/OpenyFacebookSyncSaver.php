<?php

namespace Drupal\openy_facebook_sync;

use Drupal\Core\Entity\EntityInterface;
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
   * Save service callback.
   */
  public function save() {
    $data = $this->wrapper->getSourceData();

    foreach ($data as $event) {
      // @todo Add setting to create nodes in certain status (published|unpublished).
      $stored_event_mappings = $this->eventMappingRepo->getByProperties([
        'field_fb_event_id' => $event['id'],
      ]);

      if ($stored_event_mappings) {
        // Current Event Hash.
        $hash = md5(serialize($event));
        foreach ($stored_event_mappings as $event_mapping_id => $event_mapping) {
          // Do update if hash differs.
          if ($event_mapping->get('field_event_hash')->value !== $hash) {
            $this->updateEvent($event_mapping, $event);
          }
        }
      }
      else {
        $this->createEvent($event);
      }
    }
  }

  /**
   * Prepare Event node default fields.
   *
   * @param array $event
   *   Event data.
   *
   * @return array
   *   Prepared Event data.
   */
  private function prepareEvent(array $event) {
    // Convert date values from 2017-04-08T19:00:00-0500 to 2017-04-08T19:00:00.

    $event_date_values = [
      'value' => \DateTime::createFromFormat('Y-m-d\TH:i:sO', $event['start_time'])
        ->format('Y-m-d\TH:i:s'),
      'end_value' => '',
    ];

    // Set end date value only if it exists.
    if (isset($event['end_time'])) {
      // End date value should not be null so fill it with start date.
      $event_date_values['end_value'] = \DateTime::createFromFormat('Y-m-d\TH:i:sO', $event['end_time'])
        ->format('Y-m-d\TH:i:s');
    }

    $event_node = [
      'type' => 'event',
      'title' => $event['name'],
      'uid' => self::DEFAULT_UID,
      'field_event_date_range' => $event_date_values,
    ];

    $publish_event = \Drupal::configFactory()->get('openy_facebook_sync.settings')->get('publish_event');
    $event_node['status'] = $publish_event;

    return $event_node;
  }

  /**
   * Create event node.
   *
   * @param array $event_data
   *   Event data.
   */
  private function createEvent(array $event_data) {
    $storage = $this->entityTypeManager->getStorage('node');

    // Create event node.
    $event = $storage->create($this->prepareEvent($event_data));

    // Create description paragraph.
    $paragraph = $this->createDescriptionParagraph([
      'field_prgf_sc_body' => $event_data['description'],
    ]);
    $paragraph_data = [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
    // Create Event registration paragraph for sidebar.
    $paragraph_event_reg = $this->createEventRegistrationParagraph();
    $sidebar_content = [
      [
        'target_id' => $paragraph_event_reg->id(),
        'target_revision_id' => $paragraph_event_reg->getRevisionId(),
      ]
    ];

    $event->field_landing_body->appendItem($paragraph_data);
    $event->field_sidebar_content->setValue($sidebar_content);

    $event->save();

    // Create mapping entity.
    $this->eventMappingRepo->create($event, $paragraph_data, $event_data);
  }

  /**
   * Update Event and EventMapping.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event_mapping
   *   Event Mapping Entity.
   * @param array $event_data
   *   Event Data to update.
   */
  private function updateEvent(EntityInterface $event_mapping, array $event_data) {
    $event_node = $this->prepareEvent($event_data);
    $storage = $this->entityTypeManager->getStorage('paragraph');
    // Update attached Description paragraph.
    $paragraph = $storage->load($event_mapping->get('field_desc_prgf_ref')->target_id);
    $paragraph->set('field_prgf_sc_body', $event_data['description']);
    $paragraph->save();

    $paragraph_data = [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
    // Update referenced Event node.
    $storage = $this->entityTypeManager->getStorage('node');
    $event = $storage->load($event_mapping->get('field_event_ref')->target_id);
    $event->set('field_event_date_range', $event_node['field_event_date_range']);
    $event->set('title', $event_node['title']);
    $event->save();
    // Update mapping entity.
    $this->eventMappingRepo->update($event_mapping, $paragraph_data, $event_data);
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

  /**
   * Create Event registration paragraph.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Paragraph entity.
   */
  private function createEventRegistrationParagraph() {
    $storage = $this->entityTypeManager->getStorage('paragraph');
    $paragraph = $storage->create(['type' => 'openy_event_registration']);
    $paragraph->save();
    return $paragraph;
  }

}
