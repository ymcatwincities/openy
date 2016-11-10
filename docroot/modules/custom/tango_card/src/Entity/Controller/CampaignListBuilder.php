<?php

namespace Drupal\tango_card\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for tango_card_campaign entity.
 *
 * @ingroup tango_card_campaign
 */
class CampaignListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'id' => $this->t('Campaign ID'),
      'name' => $this->t('Name'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [
      'id' => $entity->id(),
      'name' => $entity->label(),
    ];
    return $row + parent::buildRow($entity);
  }

}
