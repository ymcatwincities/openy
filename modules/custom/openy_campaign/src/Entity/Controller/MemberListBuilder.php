<?php

namespace Drupal\openy_campaign\Entity\Controller;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for ymca_campaign_member entity.
 *
 * @ingroup openy_campaign_member
 */
class MemberListBuilder extends EntityListBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * MappingListBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Database\Connection $connection
   */
  public function __construct(
    EntityTypeInterface $entityType,
    EntityStorageInterface $storage,
    EntityTypeManagerInterface $entityTypeManager,
    Connection $connection
  ) {
    parent::__construct($entityType, $storage);
    $this->entityTypeManager = $entityTypeManager;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entityType) {
    return new static(
      $entityType,
      $container->get('entity_type.manager')->getStorage($entityType->id()),
      $container->get('entity_type.manager'),
      $container->get('database')
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
    $header['id'] = $this->t('Internal ID');
    $header['name'] = $this->t('Name');
    $header['mail'] = $this->t('Email');
    $header['region'] = $this->t('Branch (Region)');
    $header['membership_id'] = $this->t('Membership ID');
    $header['is_employee'] = $this->t('Employee');
    $header['checkins'] = $this->t('Checkins');
    $header['campaigns'] = $this->t('Campaigns');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\openy_campaign\Entity\Member $entity */
    $row['id'] = $entity->id();
    $row['name'] = $entity->getFullName();
    $row['mail'] = $entity->getEmail();

    /** @var \Drupal\node\Entity\Node $branch */
    $branch = $entity->branch->entity;
    $row['region'] = '';
    if (!empty($branch)) {
      /** @var \Drupal\taxonomy\Entity\Term $locationName */
      $locationName = $this->entityTypeManager->getStorage('taxonomy_term')->load($branch->field_location_area->target_id);
      $region = !empty($locationName) ? ' (' . $locationName->getName() . ')' : '';
      $row['region'] = $branch->getTitle() . $region;
    }

    $row['membership_id'] = $entity->getMemberId();
    $row['is_employee'] = $entity->isMemberEmployee() ? $this->t('Yes') : $this->t('No');

    // Get Checkins from MemberCheckin entity.
    $memberCheckins = $this->entityTypeManager->getStorage('openy_campaign_member_checkin')->getQuery()
      ->condition('member', $entity->id())
      ->execute();
    $row['checkins'] = count($memberCheckins);

    // Get Campaign titles list for this Member.
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->connection->select('openy_campaign_member', 'm');
    $query->condition('m.id', $entity->id());
    $query->join('openy_campaign_member_campaign', 'mc', 'm.id = mc.member');
    $query->join('node_field_data', 'n', 'n.nid = mc.campaign');
    $query->condition('n.type', 'campaign');
    $query->fields('n', ['title']);
    $campaignTitlesArray = $query->execute()->fetchCol();

    $row['campaigns'] = !empty($campaignTitlesArray) ? implode('; ', $campaignTitlesArray) : '';

    return $row + parent::buildRow($entity);
  }

}
