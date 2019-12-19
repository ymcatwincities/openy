<?php

namespace Drupal\myy_personify;

use Drupal\personify\PersonifySSO;

/**
 * Class PersonifyUserHelper
 *
 * @package Drupal\myy_personify
 */
class PersonifyUserHelper {

  /**
   * @var \Drupal\personify\PersonifySSO
   */
  protected $personifySSO;

  /**
   * PersonifyUserHelper constructor.
   *
   * @param \Drupal\personify\PersonifySSO $personifySSO
   */
  public function __construct(PersonifySSO $personifySSO) {
    $this->personifySSO = $personifySSO;
  }

  /**
   * @return int|mixed
   */
  public function personifyGetId() {

    //@TODO use token check when token verification issue would be resolved.
    /**if (!empty($_COOKIE['Drupal_visitor_personify_authorized'])) {
      return $this->personifySSO->getCustomerIdentifier($_COOKIE['Drupal_visitor_personify_authorized']);
    }*/
    return '2015228900';
    if (!empty($_SESSION['personify_id'])) {
      return $_SESSION['personify_id'];
    } else {
      return 0;
    }

  }

}
