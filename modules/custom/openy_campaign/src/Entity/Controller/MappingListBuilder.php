<?php

namespace Drupal\openy_campaign\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a list controller for openy_campaign_mapping_branch entity.
 *
 * @ingroup openy_campaign_mapping_branch
 */
class MappingListBuilder extends EntityListBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MappingListBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeInterface $entityType, EntityStorageInterface $storage, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($entityType, $storage);
    $this->entityTypeManager = $entityTypeManager;

  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entityType) {
    return new static(
      $entityType,
      $container->get('entity_type.manager')->getStorage($entityType->id()),
      $container->get('entity_type.manager')
    );
  }

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
    $row['region'] = '';
    if (!empty($entity->branch->entity->field_location_area->target_id)) {
      $regionTid = $entity->branch->entity->field_location_area->target_id;
      /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
      $row['region'] = $this->entityTypeManager->getStorage('taxonomy_term')->load($regionTid)->getName();
    }

    return $row + parent::buildRow($entity);
  }

}
