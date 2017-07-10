<?php

namespace Drupal\groupex_form_cache;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Groupex Form Cache entities.
 *
 * @ingroup groupex_form_cache
 */
interface GroupexFormCacheInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Groupex Form Cache name.
   *
   * @return string
   *   Name of the Groupex Form Cache.
   */
  public function getName();

  /**
   * Sets the Groupex Form Cache name.
   *
   * @param string $name
   *   The Groupex Form Cache name.
   *
   * @return \Drupal\groupex_form_cache\GroupexFormCacheInterface
   *   The called Groupex Form Cache entity.
   */
  public function setName($name);

  /**
   * Gets the Groupex Form Cache creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Groupex Form Cache.
   */
  public function getCreatedTime();

  /**
   * Sets the Groupex Form Cache creation timestamp.
   *
   * @param int $timestamp
   *   The Groupex Form Cache creation timestamp.
   *
   * @return \Drupal\groupex_form_cache\GroupexFormCacheInterface
   *   The called Groupex Form Cache entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Groupex Form Cache published status indicator.
   *
   * Unpublished Groupex Form Cache are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Groupex Form Cache is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Groupex Form Cache.
   *
   * @param bool $published
   *   TRUE to set this Groupex Form Cache to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\groupex_form_cache\GroupexFormCacheInterface
   *   The called Groupex Form Cache entity.
   */
  public function setPublished($published);

}
