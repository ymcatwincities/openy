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
   * @param string $location_id
   *   The reference to location.
   * @param string $type
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
   * @param string $location_id
   *   The reference to location.
   * @param string $type
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
   * @param string $location_id
   *   The reference to location.
   * @param string $type
   *   The name of external system.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface
   *   The room or FALSE if any parameter is incorrect.
   */
  public function createRoomByExternalId($name, $location_id, $type);

  /**
   * Returns the list of location rooms usable in options list.
   *
   * @param string $location_id
   *   The location id.
   *
   * @return array
   *   The array of options.
   */
  public function getLocalizedRoomOptions($location_id);

  /**
   * Returns the list of All rooms usable in options list.
   *
   * The location name comes before room name.
   *
   * @return array
   *   The array of options.
   */
  public function getAllRoomOptions();

}
