<?php

namespace Drupal\fontyourface;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Font entities.
 *
 * @ingroup fontyourface
 */
class FontListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Font ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\fontyourface\Entity\Font */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      $entity->urlInfo()
    );
    return $row + parent::buildRow($entity);
  }

  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = [];
    if ($entity->access('update') && $entity->hasLinkTemplate('edit-form')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $entity->urlInfo('edit-form'),
      ];
    }
    if ($entity->isActivated()) {
      $operations['disable'] = [
        'title' => $this->t('Disable'),
        'weight' => 100,
        'url' => Url::fromRoute('entity.font.deactivate', ['js' => 'nojs', 'font' => $entity->id()], ['query' => \Drupal::destination()->getAsArray()]),
      ];
    }
    if ($entity->isDeactivated()) {
      $operations['enable'] = [
        'title' => $this->t('enable'),
        'weight' => 100,
        'url' => Url::fromRoute('entity.font.activate', ['js' => 'nojs', 'font' => $entity->id()], ['query' => \Drupal::destination()->getAsArray()]),
      ];
    }

    return $operations;
  }

}
