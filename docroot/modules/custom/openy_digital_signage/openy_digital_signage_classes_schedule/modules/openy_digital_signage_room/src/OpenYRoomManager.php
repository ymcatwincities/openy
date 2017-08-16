<?php

namespace Drupal\openy_digital_signage_room;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a classes schedule manager.
 *
 * @ingroup openy_digital_signage_room
 */
class OpenYRoomManager implements OpenYRoomManagerInterface {

  use StringTranslationTrait;

  /**
   * Logger channel definition.
   */
  const CHANNEL = 'openy_digital_signage';

  /**
   * Collection name.
   */
  const STORAGE = 'openy_ds_room';

  /**
   * Config name.
   */
  const CONFIG = 'openy_digital_signage_room.settings';

  /**
   * The entity storage.
   *
   * @var EntityStorageInterface
   */
  protected $storage;

  /**
   * LoggerChannelFactoryInterface definition.
   *
   * @var LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The config factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory) {
    $this->logger = $logger_factory->get(self::CHANNEL);
    $this->storage = $entity_type_manager->getStorage(self::STORAGE);
    $this->configFactory = $config_factory;
  }

  /**
   * Returns field name of the id field of the external system.
   *
   * @param string $type
   *   The type.
   *
   * @return bool|string
   *   The field name of FALSE.
   */
  private function getFieldNameByType($type) {
    $field_name = FALSE;
    switch ($type) {
      case 'groupex':
        $field_name = 'groupex_id';
        break;

      case 'personify':
        $field_name = 'personify_id';
        break;

    }

    return $field_name;
  }

  /**
   * Returns default room status by external system type.
   *
   * @param string $type
   *   The type.
   *
   * @return bool
   *   The default status.
   */
  private function getDefaultStatusByType($type) {
    if (!in_array($type, ['groupex', 'personify'])) {
      return FALSE;
    }
    $config = $this->configFactory->get(self::CONFIG);
    return $config->get($type == 'groupex' ? 'groupex_default_status' : 'personify_default_status');
  }

  /**
   * {@inheritdoc}
   */
  public function getRoomByExternalId($id, $location_id, $type) {
    if (!$id || !$location_id) {
      return FALSE;
    }
    if (!$field_name = $this->getFieldNameByType($type)) {
      return FALSE;
    }

    $entities =  $this->storage->loadByProperties([
      $field_name => $id,
      'location' => $location_id,
    ]);

    return reset($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function getOrCreateRoomByExternalId($id, $location_id, $type) {
    $cache = &drupal_static('room_by_external_id', []);
    $cache_id = implode(':', [$id, $location_id, $type]);

    if (isset($cache[$cache_id])) {
      return $cache[$cache_id];
    }

    if (!$room = $this->getRoomByExternalId($id, $location_id, $type)) {
      $room = $this->createRoomByExternalId($id, $location_id, $type);
    }
    $cache[$cache_id] = $room;

    return $cache[$cache_id];
  }

  /**
   * {@inheritdoc}
   */
  public function createRoomByExternalId($name, $location_id, $type) {
    if (!$field_name = $this->getFieldNameByType($type)) {
      $this->logger->warning('OpenYRoomManager is asked to created room with incorrect type @type. The name is @name, location id is @locationid', [
        '@type' => $type,
        '@name' => $name,
        '@locationid' => $location_id,
      ]);
      return FALSE;
    }

    $data = [
      'created' => REQUEST_TIME,
      'title' => $name,
      'status' => $this->getDefaultStatusByType($type),
      'location' => [
        'target_id' => $location_id,
      ],
      'description' => $this->t('Automatically created during %type import', [
        '%type' => $type,
      ]),
      $field_name => $name,
    ];

    $room = $this->storage->create($data);
    $room->save();

    return $room;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalizedRoomOptions($location_id) {
    $room_entities = $this->storage->loadByProperties([
      'location' => $location_id,
      'status' => TRUE,
    ]);

    $options = ['_none' => $this->t('- None -')];
    foreach ($room_entities as $room_entity) {
      $options[$room_entity->id()] = $room_entity->label();
    }

    asort($options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllRoomOptions() {
    $room_entities = $this->storage->loadByProperties(['status' => TRUE]);
    $options = ['_none' => $this->t('- None -')];
    foreach ($room_entities as $room_entity) {
      $label = $room_entity->location->entity->label() . ' - ' . $room_entity->label();
      $options[$room_entity->id()] = $label;
    }

    asort($options);

    return $options;
  }

}
