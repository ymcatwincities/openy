<?php

namespace Drupal\personify_mindbody_sync;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Personify MindBody Cache entities.
 *
 * @ingroup personify_mindbody_sync
 */
interface PersonifyMindbodyCacheInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Personify MindBody Cache name.
   *
   * @return string
   *   Name of the Personify MindBody Cache.
   */
  public function getName();

  /**
   * Sets the Personify MindBody Cache name.
   *
   * @param string $name
   *   The Personify MindBody Cache name.
   *
   * @return \Drupal\personify_mindbody_sync\PersonifyMindbodyCacheInterface
   *   The called Personify MindBody Cache entity.
   */
  public function setName($name);

  /**
   * Gets the Personify MindBody Cache creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Personify MindBody Cache.
   */
  public function getCreatedTime();

  /**
   * Sets the Personify MindBody Cache creation timestamp.
   *
   * @param int $timestamp
   *   The Personify MindBody Cache creation timestamp.
   *
   * @return \Drupal\personify_mindbody_sync\PersonifyMindbodyCacheInterface
   *   The called Personify MindBody Cache entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Personify MindBody Cache published status indicator.
   *
   * Unpublished Personify MindBody Cache are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Personify MindBody Cache is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Personify MindBody Cache.
   *
   * @param bool $published
   *   TRUE to set this Personify MindBody Cache to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\personify_mindbody_sync\PersonifyMindbodyCacheInterface
   *   The called Personify MindBody Cache entity.
   */
  public function setPublished($published);

}
