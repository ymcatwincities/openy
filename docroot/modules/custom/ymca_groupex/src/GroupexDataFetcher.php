<?php

namespace Drupal\ymca_groupex;
use Drupal\ymca_google\GcalGroupexWrapperInterface;

/**
 * Class GroupexDataFetcher.
 *
 * @package Drupal\ymca_groupex
 */
class GroupexDataFetcher implements GroupexDataFetcherInterface {

  use GroupexRequestTrait;

  /**
   * Data wrapper.
   *
   * @var GcalGroupexWrapperInterface
   */
  protected $dataWrapper;

  /**
   * GroupexDataFetcher constructor.
   *
   * @param GcalGroupexWrapperInterface $data_wrapper
   *   Data wrapper.
   */
  public function __construct(GcalGroupexWrapperInterface $data_wrapper) {
    $this->dataWrapper = $data_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(array $args) {
    // @todo Implement schedule to fetch data in cycle.
    $options = [
      'query' => [
        'schedule' => TRUE,
        'desc' => 'true',
        'start' => REQUEST_TIME,
        'end' => REQUEST_TIME + 60 * 60 * 24,
      ],
    ];

    $data = $this->request($options);
    if ($data) {
      $this->dataWrapper->setSourceData($data);
    }
  }

}
