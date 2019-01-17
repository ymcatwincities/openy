<?php

namespace Drupal\openy_upgrade_tool;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class OpenyUpgradeLogStorage extends SqlContentEntityStorage implements OpenyUpgradeLogStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(OpenyUpgradeLogInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {openy_upgrade_log_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {openy_upgrade_log_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(OpenyUpgradeLogInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {openy_upgrade_log_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('openy_upgrade_log_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
