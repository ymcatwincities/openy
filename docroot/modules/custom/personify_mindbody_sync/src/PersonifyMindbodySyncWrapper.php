<?php

namespace Drupal\personify_mindbody_sync;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\personify_mindbody_sync\Entity\PersonifyMindbodyCache;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class PersonifyMindbodySyncWrapper.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncWrapper implements PersonifyMindbodySyncWrapperInterface {

  /**
   * Logger channel name.
   */
  const CHANNEL = 'personify_mindbody_sync';

  /**
   * Cache entity name.
   */
  const CACHE_ENTITY = 'personify_mindbody_cache';

  /**
   * Overall timezone.
   */
  const TIMEZONE = 'America/Chicago';

  /**
   * Initial sync date.
   */
  const INITIAL_DATE = '2016-12-12T11:20:00';

  /**
   * Offset in seconds for getting data from Personify.
   */
  const DATE_OFFSET = 'PT1H';

  /**
   * Personify date format.
   */
  const PERSONIFY_DATE_FORMAT = 'Y-m-d\TH:i:s';

  /**
   * Timezone of the results coming from MindBody.
   */
  const PERSONIFY_TIMEZONE = 'America/Chicago';

  /**
   * Source data fetched from Personify.
   *
   * @var array
   */
  private $sourceData = [];

  /**
   * Data fetched and saved to Drupal database.
   *
   * @var array
   */
  private $proxyData = [];

  /**
   * Query Factory.
   *
   * @var QueryFactoryInterface
   */
  protected $query;

  /**
   * PersonifyMindbodySyncWrapper constructor.
   *
   * @param QueryFactory $query
   *   Query factory.
   */
  public function __construct(QueryFactory $query) {
    $this->query = $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceData() {
    return $this->sourceData;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceData(array $data) {
    $this->sourceData = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getProxyData() {
    return $this->proxyData;
  }

  /**
   * {@inheritdoc}
   */
  public function setProxyData(array $data) {
    $this->proxyData = $data;
  }

  /**
   * Find the first failed push.
   */
  public function findFirstFailTime() {
    $result = $this->query->get('personify_mindbody_cache')
      ->notExists('field_pmc_ord_data')
      ->sort('field_pmc_ord_date', 'ASC')
      ->execute();

    if (!$result) {
      return FALSE;
    }

    $entity = PersonifyMindbodyCache::load(reset($result));
    return $entity->field_pmc_ord_date->value;
  }

  /**
   * Convert timestamp to Personify date format.
   *
   * @param int $timestamp
   *   Timestamp.
   *
   * @return string
   *   Date string.
   */
  public function timestampToPersonifyDate($timestamp) {
    $timeZone = new \DateTimeZone(PersonifyMindbodySyncWrapper::TIMEZONE);
    $dateTime = \DateTime::createFromFormat('U', $timestamp, $timeZone);
    return $dateTime->format(self::PERSONIFY_DATE_FORMAT);
  }

  /**
   * Convert Personify date to timestamp.
   *
   * @param string $date
   *   Date string.
   *
   * @return string
   *   Timestamp.
   */
  public function personifyDateToTimestamp($date) {
    $timeZone = new \DateTimeZone(PersonifyMindbodySyncWrapper::TIMEZONE);
    $dateTime = \DateTime::createFromFormat(self::PERSONIFY_DATE_FORMAT, $date, $timeZone);
    return $dateTime->format('U');
  }

  /**
   * Find Order by order number.
   *
   * The unique id of an order in Personify is the order number + the line
   * number.
   *
   * @param string $order_num
   *   Order number.
   * @param string $order_line_num
   *   Order line number.
   *
   * @return bool|EntityInterface
   *   FALSE or order entity.
   */
  public function findOrder($order_num, $order_line_num) {
    $result = $this->query->get(PersonifyMindbodySyncWrapper::CACHE_ENTITY)
      ->condition('field_pmc_order_num', $order_num)
      ->condition('field_pmc_ord_l_num', $order_line_num)
      ->execute();

    if (!empty($result)) {
      $id = reset($result);
      return PersonifyMindbodyCache::load($id);
    }

    return FALSE;
  }

  /**
   * Returns current in appropriate timezone.
   */
  public function getCurrentTime() {
    $current_time = new \DateTime('now', $this->getPersonifyTimezone());
    $interval = new \DateInterval(self::DATE_OFFSET);
    $current_time->sub($interval);
    return $current_time->format(self::PERSONIFY_DATE_FORMAT);
  }

  /**
   * Get timezone for Personify results.
   *
   * @return \DateTimeZone
   *   Time zone.
   */
  protected function getPersonifyTimezone() {
    return new \DateTimeZone(self::PERSONIFY_TIMEZONE);
  }

  /**
   * Mocks Personify order.
   *
   * @param array $properties
   *   A list of properties to set dynamically.
   *   Example: ['TotalAmount' => 100].
   *
   * @return \stdClass
   *   Order.
   */
  public function mockOrder($properties = []) {
    $data = array(
      '$id' => '2',
      'InternalKey' => NULL,
      'NavigationKey' => NULL,
      'OrderNo' => '2009990288',
      'OrderLineNo' => 1,
      'OrderDate' => '2016-10-10T00:00:00',
      'ProductId' => 143000233,
      'ParentProduct' => '77_PT_1_SESS_30_MIN',
      'ProductCode' => '77_PT_1_SESS_30_MIN',
      'Subsystem' => 'MISC',
      'RateStructure' => 'Member',
      'RateCode' => 'STD',
      'LineStatusCode' => 'A',
      'LineStatusDate' => '2016-10-10T06:12:42.397',
      'OrderQuantity' => 1,
      'UnitPrice' => 45,
      'TotalAmount' => 45,
      'MasterCustomerId' => '2051165626',
      'SubCustomerId' => 0,
      'FirstName' => 'First',
      'LastName' => 'Last',
      'GenderCode' => 'MALE',
      'BirthDate' => '1977-04-22T00:00:00',
      'PrimaryPhone' => '612-386-6784',
      'PrimaryPhoneLocationCode' => 'HOME',
      'PrimaryPhoneDoNotCallFlag' => FALSE,
      'PrimaryMobile' => '',
      'PrimaryMobileLocationCode' => '',
      'PrimaryMobileDoNotCallFlag' => FALSE,
      'PrimaryEmail' => 'example@gmail.com',
      'PrimaryEmailLocationCode' => 'HOME',
      'PrimaryEmailDoNotContactFlag' => FALSE,
      'MindBodyCustomerOrderDetail' => new \stdClass(),
    );
    $order = (object) $data;

    foreach ($properties as $property => $value) {
      $order->$property = $value;
    }

    return $order;
  }

}
