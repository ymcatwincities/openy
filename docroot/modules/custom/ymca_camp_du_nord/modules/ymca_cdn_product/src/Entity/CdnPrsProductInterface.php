<?php

namespace Drupal\ymca_cdn_product\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Camp du Nord Personify Product entities.
 *
 * @ingroup ymca_cdn_product
 */
interface CdnPrsProductInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Camp du Nord Personify Product name.
   *
   * @return string
   *   Name of the Camp du Nord Personify Product.
   */
  public function getName();

  /**
   * Sets the Camp du Nord Personify Product name.
   *
   * @param string $name
   *   The Camp du Nord Personify Product name.
   *
   * @return \Drupal\ymca_cdn_product\Entity\CdnPrsProductInterface
   *   The called Camp du Nord Personify Product entity.
   */
  public function setName($name);

  /**
   * Gets the Camp du Nord Personify Product creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Camp du Nord Personify Product.
   */
  public function getCreatedTime();

  /**
   * Sets the Camp du Nord Personify Product creation timestamp.
   *
   * @param int $timestamp
   *   The Camp du Nord Personify Product creation timestamp.
   *
   * @return \Drupal\ymca_cdn_product\Entity\CdnPrsProductInterface
   *   The called Camp du Nord Personify Product entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Camp du Nord Personify Product published status indicator.
   *
   * Unpublished Camp du Nord Personify Product are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Camp du Nord Personify Product is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Camp du Nord Personify Product.
   *
   * @param bool $published
   *   TRUE to set this Camp du Nord Personify Product to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\ymca_cdn_product\Entity\CdnPrsProductInterface
   *   The called Camp du Nord Personify Product entity.
   */
  public function setPublished($published);

}
