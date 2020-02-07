<?php

namespace Drupal\openy_myy\PluginManager;

/**
 * Interface MyYDataOrdersInterface
 *
 * @package Drupal\openy_myy\PluginManager
 */
interface MyYDataOrdersInterface {

  /**
   * @param $ids
   * @param $date_start
   * @param $date_end
   *
   * @return mixed
   */
  public function getOrders($ids, $date_start, $date_end);

}