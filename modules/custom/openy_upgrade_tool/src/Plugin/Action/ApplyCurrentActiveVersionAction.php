<?php

namespace Drupal\openy_upgrade_tool\Plugin\Action;

use Drupal\Core\Action\Plugin\Action\EntityActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Apply current active version for openy_upgrade_log entity.
 *
 * @Action(
 *   id = "apply_active_version",
 *   action_label = @Translation("Apply current active version"),
 *   type = "openy_upgrade_log"
 * )
 */
class ApplyCurrentActiveVersionAction extends EntityActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->applyCurrentActiveVersion();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $key = $object->getEntityType()->getKey('status');

    /** @var \Drupal\Core\Entity\EntityInterface $object */
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->$key->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
