<?php

namespace Drupal\openy_pef_gxp_sync\Commands;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drush\Commands\DrushCommands;

/**
 * Groupex sync drush commands.
 */
class OpenyPefGxpSyncCommands extends DrushCommands {

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a new OpenyPefGxpSyncCommands.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger) {
    parent::__construct();
    $this->logger = $logger;
  }

  /**
   * Run syncer.
   *
   * @command openy:pef-gxp-sync
   * @aliases openy-pef-gxp-sync
   */
  public function pefGxpSync() {
    try {
      ymca_sync_run("openy_pef_gxp_sync.syncer", "proceed");
    }
    catch (\Exception $e) {
      $this->logger->get('openy_pef_gxp_sync')
        ->error('Failed to run syncer with message: %message', ['%message' => $e->getMessage()]);
    }
  }

}
