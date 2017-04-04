<?php

namespace Drupal\openy_facebook_sync;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\openy_mappings\EventMappingRepository;

/**
 * Class OpenyFacebookSyncSaver.
 *
 * @package Drupal\openy_facebook_sync
 */
class OpenyFacebookSyncSaver {

  use StringTranslationTrait;

  /**
   * Wrapper.
   *
   * @var \Drupal\openy_facebook_sync\OpenyFacebookSyncWrapperInterface
   */
  private $wrapper;

  /**
   * Logger Channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * EventMappingRepo.
   *
   * @var \Drupal\openy_mappings\EventMappingRepository
   */
  private $eventMappingRepo;

  /**
   * Constructor.
   *
   * @param \Drupal\openy_facebook_sync\OpenyFacebookSyncWrapperInterface $wrapper
   *   Wrapper.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger Channel.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   *   Entity Type Manager.
   * @param \Drupal\openy_mappings\EventMappingRepository
   *   EventMappingRepo.
   */
  public function __construct(OpenyFacebookSyncWrapperInterface $wrapper, LoggerChannelInterface $logger, EntityTypeManagerInterface $entityTypeManager, EventMappingRepository $event_mapping_repo) {
    $this->wrapper = $wrapper;
    $this->logger = $logger;
    $this->entityTypeManager = $entityTypeManager;
    $this->eventMappingRepo = $event_mapping_repo;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $this->eventMappingRepo->create();
  }

}
