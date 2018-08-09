<?php

namespace Drupal\openy_upgrade_tool;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 */
class EntityTypeInfo {

  use StringTranslationTrait;

  /**
   * Adds devel operations on entity that supports it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   *
   * @see hook_entity_operation()
   */
  public function entityOperation(EntityInterface $entity) {
    $operations = [];

    if ($entity->getEntityTypeId() == 'logger_entity' && $entity->bundle() == 'openy_config_upgrade_logs') {
      $operations['diff'] = [
        'title' => $this->t('Diff'),
        'weight' => 100,
        'url' => Url::fromRoute('openy_upgrade_tool.logger.diff', ['logger_entity' => $entity->id()]),
        'attributes' => array(
          'class' => array('use-ajax'),
          'data-dialog-type' => 'modal',
          'data-dialog-options' => json_encode(array(
            'width' => 800,
          )),
        ),
      ];
    }

    return $operations;
  }

}
