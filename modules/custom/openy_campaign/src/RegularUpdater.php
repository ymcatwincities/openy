<?php

namespace Drupal\openy_campaign;

use Drupal\Core\Config\ConfigFactoryInterface;
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
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, QueueFactory $queue_factory) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->queueFactory = $queue_factory;
  }

  /**
   * Create queue once a day.
   */
  public function createQueue() {
    // Get: Member ID, Campaign start, Campaign end to receive visits from CRM
    $connection = \Drupal::service('database');
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $connection->select('node_field_data', 'n');
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
      $startDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $item->start_date);
      $endDate = \DateTime::createFromFormat('Y-m-d\TH:i:s',$item->end_date);

      // Expand Member dates to fit all active campaigns
      if (in_array($item->member_id, array_keys($membersData))) {
        $startDate = $membersData[$item->member_id]['start_date'] > $startDate ? $startDate : $membersData[$item->member_id]['start_date'];
        $endDate = $membersData[$item->member_id]['end_date'] < $endDate ? $endDate : $membersData[$item->member_id]['end_date'];
      }

      $membersData[] = [
        'member_id' => $item->member_id,
        'master_customer_id' => $item->personify_id,
        'start_date' => $startDate,
        'end_date' => $endDate,
      ];
    }

    /**
     * Test data:
     *
     * MasterCustomerId - 2052250592, FacilityCardNumber - 4000301612, 2 visits 2017-08-15 and 2017-08-16
     * MasterCustomerId - 2030086900, FacilityCardNumber - 4000044307, 1 visit 2017-08-15
     */
    // Yesterday date - From.
    $dateFrom = new \DateTime();
//    $dateFrom->setDate(2017, 8, 15)->setTime(0, 0, 0);
    $dateFrom->sub(new \DateInterval('P1D'))->setTime(0, 0, 0);

    // Yesterday date - To.
    $dateTo = new \DateTime();
//    $dateTo->setDate(2017, 8, 16)->setTime(23, 59, 59);
    $dateTo->sub(new \DateInterval('P1D'))->setTime(23, 59, 59);

    // Collect all Master Customer Ids if yesterday date is in the range of Member dates
    $masterCustomerIds = [];
    $memberIds = [];
    foreach ($membersData as $item) {
      if ($item['start_date'] <= $dateFrom && $item['end_date'] >= $dateTo) {
        $masterCustomerIds[$item['member_id']] = $item['master_customer_id'];
        $memberIds[$item['master_customer_id']] = $item['member_id'];
      }
    }

    if (empty($masterCustomerIds)) {
      return;
    }

    $data = [
      'date_from' => $dateFrom,
      'date_to' => $dateTo,
    ];

    $queue = $this->queueFactory->get('openy_campaign_updates_member_visits');

    $chunks = array_chunk($masterCustomerIds, 100);

    foreach ($chunks as $chunk) {
      $data['items'] = [];
      foreach ($chunk as $masterCustomerID) {
        $memberID = $memberIds[$masterCustomerID];
        $data['items'][$memberID] = $masterCustomerID;
      }
      // Add data to queue
      $queue->createItem($data);
    }
  }

}
