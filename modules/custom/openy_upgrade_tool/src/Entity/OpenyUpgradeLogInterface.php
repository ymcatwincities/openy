<?php

namespace Drupal\openy_upgrade_tool\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Openy upgrade log entities.
 *
 * @ingroup openy_upgrade_tool
 */
interface OpenyUpgradeLogInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Openy upgrade log name.
   *
   * @return string
   *   Name of the Openy upgrade log.
   */
  public function getName();

  /**
   * Sets the Openy upgrade log name.
   *
   * @param string $name
   *   The Openy upgrade log name.
   *
   * @return \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface
   *   The called Openy upgrade log entity.
   */
  public function setName($name);

  /**
   * Gets the Openy upgrade log creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Openy upgrade log.
   */
  public function getCreatedTime();

  /**
   * Sets the Openy upgrade log creation timestamp.
   *
   * @param int $timestamp
   *   The Openy upgrade log creation timestamp.
   *
   * @return \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface
   *   The called Openy upgrade log entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Openy upgrade log revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Openy upgrade log revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface
   *   The called Openy upgrade log entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Openy upgrade log revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Openy upgrade log revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface
   *   The called Openy upgrade log entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Gets the Openy upgrade log config data.
   *
   * @return array
   *   Config data that related to the Openy upgrade log.
   */
  public function getData();

  /**
   * Gets the Openy upgrade log config data in Yml format.
   *
   * @return string
   *   Config data that related to the Openy upgrade log in Yml format..
   */
  public function getYmlData();

  /**
   * Sets the Openy upgrade log data.
   *
   * @param array $data
   *   Config data.
   *
   * @return \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface
   *   The called Openy upgrade log entity.
   */
  public function setData(array $data);

  /**
   * Mark conflict as resolved, set status to TRUE.
   *
   * @return \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface
   *   The called Openy upgrade log entity.
   */
  public function applyCurrentActiveVersion();

  /**
   * Import to active storage config version from Open Y file storage.
   *
   * @return \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface
   *   The called Openy upgrade log entity.
   */
  public function applyOpenyVersion();

  /**
   * Import to active storage config version from data field.
   *
   * @return \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface
   *   The called Openy upgrade log entity.
   */
  public function applyConfigVersionFromData();

}
