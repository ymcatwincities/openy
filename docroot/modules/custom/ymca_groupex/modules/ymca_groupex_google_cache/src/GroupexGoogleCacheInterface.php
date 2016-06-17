<?php

namespace Drupal\ymca_groupex_google_cache;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Groupex Google Cache entities.
 *
 * @ingroup ymca_groupex_google_cache
 */
interface GroupexGoogleCacheInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Groupex Google Cache name.
   *
   * @return string
   *   Name of the Groupex Google Cache.
   */
  public function getName();

  /**
   * Sets the Groupex Google Cache name.
   *
   * @param string $name
   *   The Groupex Google Cache name.
   *
   * @return \Drupal\ymca_groupex_google_cache\GroupexGoogleCacheInterface
   *   The called Groupex Google Cache entity.
   */
  public function setName($name);

  /**
   * Gets the Groupex Google Cache creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Groupex Google Cache.
   */
  public function getCreatedTime();

  /**
   * Sets the Groupex Google Cache creation timestamp.
   *
   * @param int $timestamp
   *   The Groupex Google Cache creation timestamp.
   *
   * @return \Drupal\ymca_groupex_google_cache\GroupexGoogleCacheInterface
   *   The called Groupex Google Cache entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Groupex Google Cache published status indicator.
   *
   * Unpublished Groupex Google Cache are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Groupex Google Cache is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Groupex Google Cache.
   *
   * @param bool $published
   *   TRUE to set this Groupex Google Cache to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\ymca_groupex_google_cache\GroupexGoogleCacheInterface
   *   The called Groupex Google Cache entity.
   */
  public function setPublished($published);

}
