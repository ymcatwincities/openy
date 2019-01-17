<?php

namespace Drupal\openy_upgrade_tool;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Openy upgrade log entities.
 *
 * @ingroup openy_upgrade_tool
 */
class OpenyUpgradeLogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Config Name');
    $header['updated'] = $this->t('Updated');
    $header['user'] = $this->t('By User');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.openy_upgrade_log.canonical',
      ['openy_upgrade_log' => $entity->id()]
    );
    $row['updated'] = \Drupal::service('date.formatter')->format($entity->getChangedTime());
    $row['user'] = $entity->getOwner()->getUsername();
    $row['status'] = $entity->getStatus() ? '✓' : '✖';
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations['diff'] = [
      'title' => $this->t('Diff'),
      'weight' => -100,
      'url' => Url::fromRoute('openy_upgrade_tool.log.diff', ['openy_upgrade_log' => $entity->id()]),
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => json_encode([
          'width' => OpenyUpgradeLogManager::MODAL_WIDTH,
        ]),
      ],
    ];

    return $operations;
  }

}
