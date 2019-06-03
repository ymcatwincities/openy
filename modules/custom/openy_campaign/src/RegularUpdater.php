<?php

namespace Drupal\openy_campaign;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * Defines a regular updater service to get Member visits.
 */
class RegularUpdater {

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
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Creates a new RegularUpdater.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\Core\Database\Connection $connection
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
    QueueFactory $queue_factory,
    Connection $connection
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->queueFactory = $queue_factory;
    $this->connection = $connection;
  }

  /**
   * Add specified or all Members to queue.
   *
   * @param \DateTime $dateFrom
   * @param \DateTime $dateTo
   * @param array $membersData
   *   Array of items with needed data: Member ID,
   *   Master Customer ID, Start date, End date to get visits.
   */
  public function createQueue(\DateTime $dateFrom, \DateTime $dateTo, $membersData = []) {
    // If Member doesn't specified - get all Member ID, Campaign start, Campaign end to receive visits from CRM.
    if (empty($membersData)) {
      $membersData = $this->getAllMembersDataForQueue();
    }

    // Collect all Master Customer Ids if yesterday date is in the range of Member dates.
    $masterCustomerIds = [];
    $memberIds = [];
    foreach ($membersData as $item) {
      if (!empty($item['master_customer_id']) && ($item['start_date'] <= $dateFrom) && ($item['end_date'] >= $dateTo)) {
        $masterCustomerIds[$item['member_id']] = $item['master_customer_id'];
        $memberIds[$item['master_customer_id']] = $item['member_id'];
      }
    }

    if (empty($masterCustomerIds)) {
      return;
    }

    $queue = $this->queueFactory->get('openy_campaign_updates_member_visits');

    $interval = \DateInterval::createFromDateString('1 day');
    $period = new \DatePeriod($dateFrom, $interval, $dateTo);

    /** @var \DateTime $from */
    foreach ($period as $from) {
      // Foreach by date - for every date in date interval create queue item.
      $to = clone $from;
      $to->setTime(23, 59, 59);
      $data = [
        'date_from' => $from,
        'date_to' => $to,
      ];

      $chunks = array_chunk($masterCustomerIds, 100);
      foreach ($chunks as $chunk) {
        $data['items'] = [];
        foreach ($chunk as $masterCustomerID) {
          $memberID = $memberIds[$masterCustomerID];
          $data['items'][$memberID] = $masterCustomerID;
        }
        // Add data to queue.
        $queue->createItem($data);
      }
    }
  }

  /**
   * Get all members data to create queue.
   *
   * @return array
   */
  private function getAllMembersDataForQueue() {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->connection->select('node_field_data', 'n');
    $query->condition('n.status', 1);
    $query->condition('n.type', 'campaign');

    $query->join('openy_campaign_member_campaign', 'mc', 'n.nid = mc.campaign');
    $query->addField('mc', 'member', 'member_id');

    $query->join('openy_campaign_member', 'm', 'mc.member = m.id');
    $query->addField('m', 'personify_id', 'personify_id');

    $query->join('node__field_campaign_start_date', 'ds', 'n.nid = ds.entity_id');
    $query->addField('ds', 'field_campaign_start_date_value', 'start_date');

    $query->join('node__field_campaign_end_date', 'de', 'n.nid = de.entity_id');
    $query->addField('de', 'field_campaign_end_date_value', 'end_date');

    $resArray = $query->execute()->fetchAll();

    $membersData = [];
    foreach ($resArray as $item) {
      // UTC dates from Drupal database.
      $startDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $item->start_date);
      $endDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $item->end_date);

      // Expand Member dates to fit all active campaigns.
      if (in_array($item->member_id, array_keys($membersData))) {
        $startDate = $membersData[$item->member_id]['start_date'] > $startDate ? $startDate : $membersData[$item->member_id]['start_date'];
        $endDate = $membersData[$item->member_id]['end_date'] < $endDate ? $endDate : $membersData[$item->member_id]['end_date'];
      }

      $startDate->setTime(0, 0, 0);

      $membersData[$item->member_id] = [
        'member_id' => $item->member_id,
        'master_customer_id' => $item->personify_id,
        'start_date' => $startDate,
        'end_date' => $endDate,
      ];
    }

    return $membersData;
  }

}
