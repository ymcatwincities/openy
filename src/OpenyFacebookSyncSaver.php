<?php

namespace Drupal\openy_facebook_sync;

use Drupal\Core\Config\ConfigFactoryInterface;
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
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   */
  public function __construct(OpenyFacebookSyncWrapperInterface $wrapper, LoggerChannelInterface $logger, EntityTypeManagerInterface $entityTypeManager, EventMappingRepository $event_mapping_repo, ConfigFactoryInterface $config_factory) {
    $this->wrapper = $wrapper;
    $this->logger = $logger;
    $this->entityTypeManager = $entityTypeManager;
    $this->eventMappingRepo = $event_mapping_repo;
    $this->configFactory = $config_factory;
  }

  /**
   * Save service callback.
   */
  public function save() {
    $data = $this->wrapper->getSourceData();
    $additions = $updates = 0;
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
            $updates++;
          }
        }
      }
      else {
        $this->createEvent($event);
        $additions++;
      }

    }
    $this->logger->notice('Events imported: %total fetched, %number added, %update updated.', [
      '%total' => count($data),
      '%number' => $additions,
      '%update' => $updates
    ]);
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
    $event_date_values = [
      'value' => clone $event['start_time'],
      // End date value should not be null so fill it with start date.
      'end_value' => empty($event['end_time']) ? clone $event['start_time'] : clone $event['end_time'],
    ];

    // Convert date values from DateTime format to 2017-04-08T19:00:00 and update timezone to UTC.
    $utc_timezone = new \DateTimeZone('Etc/UTC');
    $format = 'Y-m-d\TH:i:s';
    $event_date_values['value']->setTimezone($utc_timezone);
    $event_date_values['value'] = $event_date_values['value']->format($format);
    $event_date_values['end_value']->setTimezone($utc_timezone);
    $event_date_values['end_value'] = $event_date_values['end_value']->format($format);

    $event_node = [
      'type' => 'event',
      'title' => $event['name'],
      'uid' => self::DEFAULT_UID,
      'field_event_date_range' => $event_date_values,
    ];

    $publish_event = $this->configFactory->get('openy_facebook_sync.settings')->get('publish_event');
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

    // Set locations reference for event.
    $locations = $this->getLocationsByFacebookPage($event_data['host']);
    if ($locations) {
      $location_value = [];
      foreach ($locations as $location) {
        $location_value[] = [
          'target_id' => $location->id(),
        ];
      }
      $event->field_locations_ref->setValue($location_value);
    }

    $event->save();
    $this->logger->notice('Created event @label id @id.', ['@id' => $event->id(), '@label' => $event->label()]);
    // Create mapping entity.
    $this->eventMappingRepo->create($event, $event_data);
  }

  /**
   * Update Event and EventMapping.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event_mapping
   *   Event Mapping Entity for event node that should be updated.
   * @param array $event_data
   *   Event data to update event node.
   */
  private function updateEvent(EntityInterface $event_mapping, array $event_data) {
    $event = $event_mapping->get('field_event_ref')->referencedEntities()[0];
    $event_node = $this->prepareEvent($event_data);

    // Update attached Description paragraph.
    if (!empty($event_data['description']) && !$event->get('field_landing_body')->isEmpty()) {
      $paragraph = $event->get('field_landing_body')->referencedEntities()[0];
      $paragraph->set('field_prgf_sc_body', $event_data['description']);
      $paragraph->save();
    }

    // Update referenced Event node.
    $event->set('field_event_date_range', $event_node['field_event_date_range']);
    $event->set('title', $event_node['title']);

    $locations = $this->getLocationsByFacebookPage($event_data['host']);
    if ($locations) {
      $location_value = [];
      foreach ($locations as $location) {
        $location_value[] = [
          'target_id' => $location->id(),
        ];
      }
      $event->field_locations_ref->setValue($location_value);
    }

    $event->save();
    $this->logger->notice('Updated event @label id @id.', ['@id' => $event->id(), '@label' => $event->label()]);
    // Update mapping entity.
    $this->eventMappingRepo->update($event_mapping, $event_data);
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

  /**
   * Return location nodes mapped to facebook page in openy_facebook_sync.locations_map.yml.
   *
   * @param array $page_data
   *   Facebook page data.
   *
   * @return \Drupal\Core\Entity\EntityInterface|array
   *   Node entities array of Branch or Camp type that Facebook page belongs.
   */
  private function getLocationsByFacebookPage(array $page_data) {
    $locations = [];
    $conf = $this->configFactory->get('openy_facebook_sync.locations_map');
    $map = $conf->get('map');
    $uuid = '';

    if (array_key_exists($page_data['id'], $map)) {
      $uuid = $map[$page_data['id']];
    }
    else {
      $default = $conf->get('default_location');
      if (!empty($default)) {
        $uuid = $default;
      }
    }

    if ($uuid) {
      $storage = $this->entityTypeManager->getStorage('node');
      $nodes = $storage->loadByProperties(['uuid' => $uuid]);
      if (!empty($nodes)) {
        foreach ($nodes as $node) {
          if (in_array($node->getType(), ['branch', 'camp'])) {
            $locations[] = $node;
          }
          else {
            $this->logger->warning('Location node @uuid not found during event import', ['@uuid' => $node->uuid()]);
            return NULL;
          }
        }
      }
    }

    return $locations;
  }

}
