<?php

namespace Drupal\openy_digital_signage_room;

/**
 * Interface OpenYRoomManagerInterface.
 *
 * @ingroup openy_digital_signage_room
 */
interface OpenYRoomManagerInterface {

  /**
   * Retrieves Room entity by GroupEx Pro or Personify room id.
   *
   * @param string $id
   *   The external id.
   * @param string $location
   *   The reference to location.
   * @param string $room
   *   The name of external system.
   *
   * @return mixed
   *   The room id.
   */
  public function getRoomByExternalId($id, $location_id, $type);

  /**
   * Retrieves Room entity by GroupEx Pro or Personify room id or create new.
   *
   * @param string $id
   *   The external id.
   * @param string $location
   *   The reference to location.
   * @param string $room
   *   The name of external system.
   *
   * @return mixed
   *   The room id.
   */
  public function getOrCreateRoomByExternalId($id, $location_id, $type);

  /**
   * Creates new room by given name, location id and type.
   *
   * @param string $name
   *   The external id.
   * @param string $location
   *   The reference to location.
   * @param string $room
   *   The name of external system.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface
   *   The room or FALSE if any parameter is incorrect.
   */
  public function createRoomByExternalId($name, $location_id, $type);

}
