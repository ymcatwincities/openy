<?php

namespace Drupal\openy_upgrade_tool;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface;

/**
 * Defines the storage handler class for Openy upgrade log entities.
 *
 * This extends the base storage class, adding required special handling for
 * Openy upgrade log entities.
 *
 * @ingroup openy_upgrade_tool
 */
interface OpenyUpgradeLogStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Openy upgrade log revision IDs for a specific Openy upgrade log.
   *
   * @param \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface $entity
   *   The Openy upgrade log entity.
   *
   * @return int[]
   *   Openy upgrade log revision IDs (in ascending order).
   */
  public function revisionIds(OpenyUpgradeLogInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Openy upgrade log author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Openy upgrade log revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface $entity
   *   The Openy upgrade log entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(OpenyUpgradeLogInterface $entity);

  /**
   * Unsets the language for all Openy upgrade log with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
