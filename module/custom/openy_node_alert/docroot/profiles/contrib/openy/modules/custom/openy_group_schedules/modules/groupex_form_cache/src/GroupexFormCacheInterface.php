<?php

namespace Drupal\groupex_form_cache;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining GroupEx Pro Form Cache entities.
 *
 * @ingroup groupex_form_cache
 */
interface GroupexFormCacheInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the GroupEx Pro Form Cache name.
   *
   * @return string
   *   Name of the GroupEx Pro Form Cache.
   */
  public function getName();

  /**
   * Sets the GroupEx Pro Form Cache name.
   *
   * @param string $name
   *   The GroupEx Pro Form Cache name.
   *
   * @return \Drupal\groupex_form_cache\GroupexFormCacheInterface
   *   The called GroupEx Pro Form Cache entity.
   */
  public function setName($name);

  /**
   * Gets the GroupEx Pro Form Cache creation timestamp.
   *
   * @return int
   *   Creation timestamp of the GroupEx Pro Form Cache.
   */
  public function getCreatedTime();

  /**
   * Sets the GroupEx Pro Form Cache creation timestamp.
   *
   * @param int $timestamp
   *   The GroupEx Pro Form Cache creation timestamp.
   *
   * @return \Drupal\groupex_form_cache\GroupexFormCacheInterface
   *   The called GroupEx Pro Form Cache entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the GroupEx Pro Form Cache published status indicator.
   *
   * Unpublished GroupEx Pro Form Cache are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the GroupEx Pro Form Cache is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a GroupEx Pro Form Cache.
   *
   * @param bool $published
   *   TRUE to set this GroupEx Pro Form Cache to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\groupex_form_cache\GroupexFormCacheInterface
   *   The called GroupEx Pro Form Cache entity.
   */
  public function setPublished($published);

}
