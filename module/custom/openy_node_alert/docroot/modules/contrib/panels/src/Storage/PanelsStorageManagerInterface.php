<?php

namespace Drupal\panels\Storage;

use Drupal\Core\Session\AccountInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * Interface for the Panels storage manager service.
 *
 * This service allows Panels displays to be loaded from and saved to their
 * underlying storage (ie. config entity, field, etc) without needing to know
 * the details of the storage plugin, only its storage type and id.
 *
 * It also provides a way to check if a user has permission to create, update,
 * read or delete items from the underlying storage.
 *
 * This is necessary to allow other modules (like Panels IPE) to work with
 * Panels displays that may be used by any number of other modules (like
 * Page Manager, Panelizer, Mini Panels, etc) without needing to explicitly
 * know how they are storing the Panels display.
 */
interface PanelsStorageManagerInterface {

  /**
   * Loads a Panels display.
   *
   * @param string $storage_type
   *   The storage type used by the storage plugin.
   * @param string $id
   *   The id within the storage plugin for the requested Panels display.
   *
   * @return \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant|NULL
   *   The Panels display if one exists with this id; NULL otherwise.
   */
  public function load($storage_type, $id);

  /**
   * Saves a Panels display.
   *
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   *   The Panels display to save. $panels_display->getStorageType() and
   *   $panels_display->getStorageId() must return the storage type and id as
   *   known to the storage plugin.
   *
   * @throws \Exception
   *   If $panels->getStorageType() or $panels->getStorageId() aren't set, the
   *   storage plugin can't be found, or there is no Panels display found in
   *   the storage plugin with the given id.
   */
  public function save(PanelsDisplayVariant $panels_display);

  /**
   * Checks if the user has access to underlying storage for a Panels display.
   *
   * @param string $storage_type
   *   The storage type used by the storage plugin.
   * @param string $id
   *   The id within the storage plugin for the requested Panels display.
   * @param string $op
   *   The operation to perform (ie. create, read, update, delete).
   * @param \Drupal\Core\Session\AccountInterface|NULL $account
   *   The user to check access for. If omitted, it'll check the curerntly
   *   logged in user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result. If there is no such Panels display then deny access.
   */
  public function access($storage_type, $id, $op, AccountInterface $account = NULL);

}
