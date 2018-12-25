<?php

namespace Drupal\file_entity\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;
use Drupal\file_entity\Entity\FileEntity;

/**
 * Sets the file status to permanent.
 *
 * @Action(
 *   id = "file_permanent_action",
 *   label = @Translation("Set file status to permanent"),
 *   type = "file"
 * )
 */
class FileSetPermanent extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var FileEntity $entity */
    $entity->setPermanent();
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIf($object instanceof FileInterface)->andIf(AccessResult::allowedIf($object->access('update')));
    return $return_as_object ? $result : $result->isAllowed();
  }


}
