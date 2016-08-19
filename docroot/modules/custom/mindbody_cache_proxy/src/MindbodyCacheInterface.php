<?php

namespace Drupal\mindbody_cache_proxy;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining MindBody Cache entities.
 *
 * @ingroup mindbody_cache_proxy
 */
interface MindbodyCacheInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the MindBody Cache name.
   *
   * @return string
   *   Name of the MindBody Cache.
   */
  public function getName();

  /**
   * Sets the MindBody Cache name.
   *
   * @param string $name
   *   The MindBody Cache name.
   *
   * @return \Drupal\mindbody_cache_proxy\MindbodyCacheInterface
   *   The called MindBody Cache entity.
   */
  public function setName($name);

  /**
   * Gets the MindBody Cache creation timestamp.
   *
   * @return int
   *   Creation timestamp of the MindBody Cache.
   */
  public function getCreatedTime();

  /**
   * Sets the MindBody Cache creation timestamp.
   *
   * @param int $timestamp
   *   The MindBody Cache creation timestamp.
   *
   * @return \Drupal\mindbody_cache_proxy\MindbodyCacheInterface
   *   The called MindBody Cache entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the MindBody Cache published status indicator.
   *
   * Unpublished MindBody Cache are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the MindBody Cache is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a MindBody Cache.
   *
   * @param bool $published
   *   TRUE to set this MindBody Cache to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\mindbody_cache_proxy\MindbodyCacheInterface
   *   The called MindBody Cache entity.
   */
  public function setPublished($published);

}
