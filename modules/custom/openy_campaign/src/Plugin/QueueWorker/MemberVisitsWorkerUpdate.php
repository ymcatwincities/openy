<?php

namespace Drupal\openy_campaign\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\openy_campaign\CRMClientFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Updates member checkins for retention campaign.
 *
 * @QueueWorker(
 *   id = "openy_campaign_updates_member_visits",
 *   title = @Translation("Updates member visits for retention campaign"),
 *   cron = {"time" = 60}
 * )
 */
class MemberVisitsWorkerUpdate extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Campaign start date.
   *
   * @var \DateTime
   */
  protected $dateOpen;

  /**
   * Campaign end date.
   *
   * @var \DateTime
   */
  protected $dateClose;

  /**
   * @var \Drupal\openy_campaign\CRMClientFactory
   */
  protected $clientFactory;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;


  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\openy_campaign\CRMClientFactory $clientFactory
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    CRMClientFactory $clientFactory,
    LoggerChannelFactoryInterface $logger
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->clientFactory = $clientFactory;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('openy_campaign.client_factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Get info from CRM.
    /** @var $client \Drupal\openy_campaign\CRMClientInterface */
    $client = $this->clientFactory->getClient();

    $results = $client->getVisitsBatch($data['items'], $data['date_from'], $data['date_to']);
    if (!empty($results->ErrorMessage)) {
      $logger = $this->logger->get('openy_campaign_queue');
      $logger->alert('Could not retrieve visits information for members for batch operation');
      return;
    }

    $memberCheckinStorage = $this->entityTypeManager->getStorage('openy_campaign_member_checkin');
    foreach ($results->FacilityVisitCustomerRecord as $item) {
      if (!isset($item->TotalVisits) || $item->TotalVisits == 0) {
        continue;
      }
      $member_id = array_search($item->MasterCustomerId, $data['items']);
      /** @var \DateTime $dateFrom */
      $dateFrom = $data['date_from'];
      $timestampFrom = $dateFrom->getTimestamp();

      $checkin_ids = $memberCheckinStorage->getQuery()
        ->condition('member', $member_id)
        ->condition('date', $timestampFrom)
        ->execute();

      // Verify checkins for the day.
      if (!empty($checkin_ids)) {
        continue;
      }

      // Create check-in record.
      $checkin = $memberCheckinStorage->create([
        'date' => $timestampFrom,
        'checkin' => TRUE,
        'member' => $member_id,
      ]);
      $checkin->save();
    }
  }

}
