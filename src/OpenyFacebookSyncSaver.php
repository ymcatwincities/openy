<?php

namespace Drupal\openy_facebook_sync;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

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
   * Constructor.
   *
   * @param \Drupal\openy_facebook_sync\OpenyFacebookSyncWrapperInterface $wrapper
   *   Wrapper.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger Channel.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   *   Entity Type Manager.
   */
  public function __construct(OpenyFacebookSyncWrapperInterface $wrapper, LoggerChannelInterface $logger, EntityTypeManagerInterface $entityTypeManager) {
    $this->wrapper = $wrapper;
    $this->logger = $logger;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    // @todo Save events here.
  }

}
