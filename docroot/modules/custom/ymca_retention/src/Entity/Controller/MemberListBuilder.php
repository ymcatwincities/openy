<?php

namespace Drupal\ymca_retention\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for ymca_retention_member entity.
 *
 * @ingroup ymca_retention_member
 */
class MemberListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the Member list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Internal ID');
    $header['name'] = $this->t('Name');
    $header['mail'] = $this->t('Email');
    $header['membership_id'] = $this->t('Membership ID');
    $header['points'] = $this->t('Points');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\ymca_retention\Entity\Member */
    $row['id'] = $entity->id();
    $row['name'] = $entity->getFullName();
    $row['mail'] = $entity->getEmail();
    $row['membership_id'] = $entity->getMemberId();
    $row['points'] = $entity->getPoints();
    return $row + parent::buildRow($entity);
  }

}
