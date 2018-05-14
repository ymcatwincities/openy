<?php

namespace Drupal\panels\Storage;

use Drupal\Core\Session\AccountInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * Interface for storing Panels displays in various ways.
 */
interface PanelsStorageInterface {

  /**
   * Loads a Panels display.
   *
   * @param string $id
   *   The id for the Panels display within this storage plugin.
   *
   * @return \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant|NULL
   *   The Panels display if one exists with this id; NULL otherwise.
   */
  public function load($id);

  /**
   * Saves a Panels display.
   *
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   *   The Panels display to save. $panels_display->getStorageId() must return
   *   the display's id as known to this storage plugin.
   *
   * @throws \Exception
   *   If the storage information isn't set, or there is no such Panels display.
   */
  public function save(PanelsDisplayVariant $panels_display);

  /**
   * Checks if the user has access to a Panels display.
   *
   * @param string $id
   *   The id for the Panels display within this storage plugin.
   * @param string $op
   *   The operation to perform (create, read, update, delete, change layout).
   *   If the operation is 'change layout', implementing classes should
   *   implicitly check the 'update' permission as well.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result. If there is no such Panels display then deny access.
   */
  public function access($id, $op, AccountInterface $account);

}
