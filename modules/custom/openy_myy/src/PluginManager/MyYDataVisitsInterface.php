<?php

namespace Drupal\openy_myy\PluginManager;

/**
 * Interface MyYDataVisitsInterface
 *
 * @package Drupal\open_myy\PluginManager
 */
interface MyYDataVisitsInterface {

  /**
   * Function that retrieve count of visits in time period.
   *
   * @param $personifyID
   * @param $start_date
   * @param $finish_date
   *
   * @return mixed
   */
  public function getVisitsCountByDate($personifyID, $start_date, $finish_date);

  /**
   * Function that return detailed visits data
   *
   * @param array $personifyID
   * @param $start_date
   * @param $finish_date
   *
   * @return mixed
   */
  public function getVisitsDetails($personifyID, $start_date, $finish_date);


  /**
   * Function that return visits stat for this month.
   *
   * @return mixed
   */
  public function getVisitsOverview();

}
