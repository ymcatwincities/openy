<?php

namespace Drupal\ymca_groupex;

/**
 * Class GroupexDataFetcher.
 *
 * @package Drupal\ymca_groupex
 */
class GroupexDataFetcher implements GroupexDataFetcherInterface {

  use GroupexRequestTrait;

  /**
   * GroupexDataFetcher constructor.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public function fetch(/*$start = 0, $end = 0*/ array $a) {
    $i = 0;
//    $options = [
//      'query' => [
//        'schedule' => TRUE,
//        'desc' => 'true',
//        'start' => $start,
//        'end' => $end,
//      ],
//    ];
//
//    $data = $this->request($options);
//    // @todo $wrapper->setGroupexData($data);
//    return $data;
  }

}
