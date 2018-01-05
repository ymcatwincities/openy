<?php

namespace Drupal\file_entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileAccessControlHandler;
use Drupal\file_entity\Entity\FileEntity;

/**
 * Defines the access control handler for the file entity type.
 */
class FileEntityAccessControlHandler extends FileAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    $result = AccessResult::allowedIfHasPermission($account, 'bypass file access')
      ->orIf(parent::access($entity, $operation, $account, TRUE));
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = array(), $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    $result = AccessResult::allowedIfHasPermission($account, 'bypass file access')
      ->orIf(parent::createAccess($entity_bundle, $account, $context, TRUE));
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create files');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var FileEntity $entity */
    $is_owner = $entity->getOwnerId() === $account->id();

    if ($operation == 'view') {
      $schemes = file_entity_get_public_and_private_stream_wrapper_names();
      if (isset($schemes['private'][file_uri_scheme($entity->getFileUri())])) {
        return AccessResult::allowedIfHasPermission($account, 'view private files')
          ->orIf(AccessResult::allowedIf($account->isAuthenticated() && $is_owner)->addCacheableDependency($entity)
            ->andIf(AccessResult::allowedIfHasPermission($account, 'view own private files')));
      }
      elseif ($entity->isPermanent()) {
        return AccessResult::allowedIfHasPermission($account, 'view files')
          ->orIf(AccessResult::allowedIf($is_owner)->addCacheableDependency($entity)
            ->andIf(AccessResult::allowedIfHasPermission($account, 'view own files')));
      }
    }

    // User can perform these operations if they have the "any" permission or if
    // they own it and have the "own" permission.
    if (in_array($operation, array('download', 'update', 'delete'))) {
      $permission_action = $operation == 'update' ? 'edit' : $operation;
      $type = $entity->get('type')->target_id;
      return AccessResult::allowedIfHasPermission($account, "$permission_action any $type files")
        ->orIf(AccessResult::allowedIf($is_owner)->addCacheableDependency($entity)
          ->andIf(AccessResult::allowedIfHasPermission($account, "$permission_action own $type files")));
    }

    // Fall back to the parent implementation so that file uploads work.
    // @todo Merge that in here somehow?
    return parent::checkAccess($entity, $operation, $account);
  }
}
