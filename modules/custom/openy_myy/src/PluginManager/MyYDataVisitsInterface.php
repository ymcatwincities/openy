<?php

namespace Drupal\open_myy\PluginManager;

/**
 * Interface MyYDataVisitsInterface
 *
 * @package Drupal\open_myy\PluginManager
 */
interface MyYDataVisitsInterface {

  /**
   * Function that retrieve count of visits in time period.
   *
   * @param $start_date
   * @param $finish_date
   *
   * @return mixed
   */
  public function getVisitsCountByDate($start_date, $finish_date);

}
