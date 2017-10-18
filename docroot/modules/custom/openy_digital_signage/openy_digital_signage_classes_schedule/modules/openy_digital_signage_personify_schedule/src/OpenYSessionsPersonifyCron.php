<?php

namespace Drupal\openy_digital_signage_personify_schedule;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\openy_digital_signage_classes_schedule\OpenYSessionsCronImporterAbstract;

/**
 * Defines a cron service to work with Personify.
 *
 * @ingroup openy_digital_signage_personify_schedule
 */
class OpenYSessionsPersonifyCron extends OpenYSessionsCronImporterAbstract {

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
    parent::__construct($config_factory, $entity_type_manager, $logger_factory);
    $this->config = $this->configFactory->get('openy_digital_signage_personify_schedule.cron_settings');
  }

  /**
   * {@inheritdoc}
   */
  public function importSessions() {
    if (!$this->isAllowed()) {
      return;
    }
    /* @var \Drupal\openy_digital_signage_personify_schedule\OpenYSessionsPersonifyFetcher $service */
    $service = \Drupal::service('openy_digital_signage_personify_schedule.fetcher');
    $service->fetchAll();

    // Update run time.
    $config = $this->configFactory->getEditable('openy_digital_signage_personify_schedule.cron_settings');
    $config->set('last_run', REQUEST_TIME);
  }

}
