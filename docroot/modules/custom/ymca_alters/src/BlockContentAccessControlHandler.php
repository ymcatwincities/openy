<?php

namespace Drupal\ymca_alters;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\block_content\BlockContentAccessControlHandler as BlockContentAccessControlHandlerCore;

/**
 * Alters access control handler the custom block entity type.
 *
 * @see \Drupal\block_content\Entity\BlockContent
 */
class BlockContentAccessControlHandler extends BlockContentAccessControlHandlerCore {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'update' && $entity->bundle() == 'location_schedule') {
      $user = \Drupal::currentUser();
      if ($user->hasPermission('administer blocks')) {
        return AccessResult::allowed();
      }

      $storage = \Drupal::getContainer()->get('entity.manager')->getStorage('user');
      $account = $storage->load($user->getAccount()->id());
      $block_id = $entity->id();
      $location_nids = \Drupal::entityQuery('node')
        ->condition('type', 'location')
        ->condition('field_schedule_block.target_id', $block_id)
        ->execute();

      // If block isn't referenced by locations, use default access check.
      if (!$location_nids) {
        return parent::checkAccess($entity, $operation, $account);
      }

      $can_edit = FALSE;
      if ($account->hasField('field_locations')) {
        foreach ($account->field_locations->getValue() as $value) {
          if (in_array($value['target_id'], $location_nids)) {
            $can_edit = TRUE;
            break;
          }
        }
      }
      return AccessResult::allowedIf($can_edit);
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
