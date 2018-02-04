<?php

namespace Drupal\openy_campaign\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a list controller for openy_campaign_mapping_branch entity.
 *
 * @ingroup openy_campaign_mapping_branch
 */
class MappingListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the Member list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Record ID');
    $header['personify_branch'] = $this->t('Personify Branch ID');
    $header['branch'] = $this->t('Branch');
    $header['region'] = $this->t('Region');
    $return = $header + parent::buildHeader();
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\openy_campaign\Entity\Mapping $entity */
    $row['id'] = $entity->id();
    $row['personify_branch'] = $entity->getPersonifyBranch();
    $row['branch'] = $entity->branch->entity->getTitle();
    if (!empty($entity->branch->entity->field_location_area->target_id)) {
      $regionTid = $entity->branch->entity->field_location_area->target_id;
      $row['region'] = Term::load($regionTid)->getName();
    }
    else {
      $row['region'] = '';
    }

    return $row + parent::buildRow($entity);
  }

}
