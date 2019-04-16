<?php

namespace Drupal\openy_upgrade_tool\Plugin\Action;

use Drupal\Core\Action\Plugin\Action\EntityActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Apply current active version for openy_upgrade_log entity.
 *
 * @Action(
 *   id = "apply_openy_version",
 *   action_label = @Translation("Apply Open Y version"),
 *   type = "openy_upgrade_log"
 * )
 */
class ApplyOpenyVersionAction extends EntityActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->applyOpenyVersion();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $key = $object->getEntityType()->getKey('status');

    /** @var \Drupal\Core\Entity\EntityInterface $object */
    $result = $object->access('update', $account, TRUE);

    return $return_as_object ? $result : $result->isAllowed();
  }

}
