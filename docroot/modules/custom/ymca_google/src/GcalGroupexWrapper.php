<?php

namespace Drupal\ymca_google;


/**
 * Class GcalGroupexWrapper.
 *
 * @package Drupal\ymca_google
 */
class GcalGroupexWrapper implements GcalGroupexWrapperInterface {

  /**
   * Raw source data from source system.
   *
   * @var array
   */
  protected $sourceData = [];

  /**
   * Prepared data for proxy system.
   *
   * @var array
   */
  protected $proxyData;

  /**
   * Prepared data for destination system.
   *
   * @var array
   */
  protected $destinationData;

  /**
   * Source data setter.
   *
   * @param $data
   *   Source data from Groupex.
   */
  public function setSourceData($data) {
    $this->sourceData = $data;
  }

  /**
   * Source data getter.
   */
  public function getSourceData() {
    return $this->sourceData;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalEntitiesFromSource() {
    // TODO: Implement getDrupalEntitiesFromSource() method.
    $this->sourceData = [
      0 => ['title' => 'Event1', 'id' => 123],
      1 => ['title' => 'Event2', 'id' => 124],
    ];
    foreach ($this->sourceData as $id => &$sourceItem) {
      $this->proxyData[$id] = new \stdClass();
      $this->proxyData[$id]->title = $sourceItem['title'];
      $this->proxyData[$id]->entity_id = $sourceItem['id'];
    }
    return $this->sourceData;

  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationEntitiesFromProxy() {
    // TODO: Implement getDestinationEntitiesFromProxy() method.

    foreach ($this->proxyData as $id => &$hostEntity) {
      $this->destinationData[] = array(
        'summary' => $hostEntity->title,
        'location' => 'Kyiv, Ukraine, 01042',
        'description' => 'Description for test event from code.',
        'start' => array(
          'dateTime' => '2016-05-24T09:00:00-07:00',
          'timeZone' => 'UTC',
        ),
        'end' => array(
          'dateTime' => '2016-05-24T17:00:00-07:00',
          'timeZone' => 'UTC',
        ),
      );
    }
    return $this->destinationData;
  }

  /**
   * {@inheritdoc}
   */
  public function getProxyData() {
    return $this->proxyData;
  }

}
