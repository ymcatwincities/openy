<?php

namespace Drupal\openy_digital_signage_groupex_schedule;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Defines a cron service to work with GroupEx Pro.
 *
 * @ingroup openy_digital_signage_groupex_schedule
 */
class OpenYSessionsGroupExCron implements OpenYSessionsGroupExCronInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Creates a new RegularUpdater.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed($allow_often = FALSE) {
    if ($allow_often) {
      return TRUE;
    }
    // @todo Make sense to think about using module Ultimate Cron and configure schedule via this module.
    $config = $this->configFactory->get('openy_digital_signage_groupex_schedule.cron_settings');
    $current_time = new \DateTime();
    $last_run = new \DateTime();
    $last_run->setTimestamp($config->get('last_run'));
    $last_run->add(new \DateInterval('PT' . $config->get('period') . 'S'));
    // Check if cron was run recently.
    if ($current_time > $last_run) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function importSessions() {
    if (!$this->isAllowed()) {
      return;
    }
    /* @var \Drupal\openy_digital_signage_groupex_schedule\OpenYSessionsGroupExFetcher $service */
    $service = \Drupal::service('openy_digital_signage_groupex_schedule.fetcher');
    $service->fetchAll();
    // Update run time.
    $config = $this->configFactory->getEditable('openy_digital_signage_groupex_schedule.cron_settings');
    $config->set('last_run', REQUEST_TIME);
  }

}
