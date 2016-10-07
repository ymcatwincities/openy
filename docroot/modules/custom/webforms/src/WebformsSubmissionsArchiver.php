<?php

namespace Drupal\webforms;

use Drupal\contact\Entity\Message;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\csv_serialization\Encoder\CsvEncoder;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;
use Drupal\serialization\Normalizer\EntityNormalizer;

/**
 * Class WebformsSubmissionsArchiver
 * @package Drupal\webforms
 */
class WebformsSubmissionsArchiver {

  /**
   * Query Factory to work with.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  private $queryFactory;

  /**
   * Entity Type Manager to work with.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * Encoder for converting entities into CSV file.
   *
   * @var \Drupal\csv_serialization\Encoder\CsvEncoder
   */
  private $csvEncoder;

  public function __construct(QueryFactory $queryFactory, EntityTypeManager $entityTypeManager, CsvEncoder $csvEncoder) {
    $this->queryFactory = $queryFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->csvEncoder = $csvEncoder;
  }

  /**
   * Archiving loop, should be run from cron.
   */
  public function archive() {
    // Get first from list of contact_storage entities.
    $message_ids = $this->queryFactory->get('contact_message')
      ->condition('created', strtotime('last day of previous month'), '<=')
      ->sort('created', 'ASC')
      ->range(0,1)
      ->execute();
    // Loop through all of them to find the data, older than a month.
    $entities = $this->entityTypeManager->getStorage('contact_message')->loadMultiple($message_ids);
    /** @var Message $entity */
    $entity = array_shift($entities);
    $form_name = $entity->bundle();
    $created = $entity->created->get(0)->getValue()['value'];
    $end = strtotime('last day of this month', $created);
    $start = strtotime('first day of this month', $created);
    $month_ids = $this->queryFactory->get('contact_message')
      ->condition('created', $end, '<=')
      ->condition('created', $start, '>=')
      ->condition('contact_form', $form_name)
      ->sort('created', 'ASC')
      ->execute();

    // @todo Archive a single month data, store to local Archive entity.
    $month_entities = $this->entityTypeManager->getStorage('contact_message')->loadMultiple($month_ids);
    $normalizer = new EntityNormalizer(\Drupal::service('entity.manager'));
    $normalizer->setSerializer($this->c)
    $test = $normalizer->normalize($entity, 'csv');
    $csv = $this->csvEncoder->encode($month_entities, 'csv');
    // @todo Check if the file is greater than a zero, remove archived data.
    // @todo finish a loop.
    $i = 0;
  }
}