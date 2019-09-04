<?php

namespace Drupal\openy_myy\PluginManager;

/**
 * Interface MyYDataChildcareInterface
 *
 * @package Drupal\openy_myy\PluginManager
 */
interface MyYDataChildcareInterface {

  /**
   * Retrieve all childcare events for current user's children.
   *
   * @return mixed
   */
  public function getChildcareEvents($start_date, $end_date);

  /**
   * Retrieve only scheduled events for current user's children.
   *
   * @return mixed
   */
  public function getChildcareScheduledEvents($start_date, $end_date);

}
