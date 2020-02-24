<?php

namespace Drupal\myy_personify;

use Drupal\personify\PersonifySSO;

/**
 * Class PersonifyUserHelper
 *
 * @package Drupal\myy_personify
 */
class PersonifyUserHelper implements PersonifyUserHelperfInterface {

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
   * {@inheritdoc}
   */
  public function personifyGetId() {

    if (!empty($_COOKIE['Drupal_visitor_personify_authorized'])) {
      return $this->personifySSO->getCustomerIdentifier($_COOKIE['Drupal_visitor_personify_authorized']);
    }
     else {
      return NULL;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function locationMapping($branch_id) {

    $mapping = [
      '32' => 'Andover',
      '14' => 'Blaisdell',
      '30' => 'Burnsville',
      '82' => 'Eagan',
      '34' => 'Elk River',
      '38' => 'Forest Lake',
      '85' => 'Hastings',
      '18' => 'Cora McCorvey YMCA',
      '84' => 'Hudson',
      '81' => 'Lino Lakes',
      '79' => 'Mounds View Community Center',
      '24' => 'New Hope',
      '22' => 'Ridgedale',
      '36' => 'River Valley - Prior Lake',
      '75' => 'St. Paul Downtown',
      '76' => 'St. Paul Eastside',
      '77' => 'St. Paul Midway',
      '89' => 'Shoreview',
      '20' => 'Southdale',
      '70' => 'West St. Paul',
      '88' => 'White Bear Area',
      '83' => 'Woodbury',
      '16' => 'North Community',
      '27' => 'Emma B. Howe - Coon Rapids',
      '62' => 'Camp du Nord',
      '42' => 'Camp Icaghowan',
      '40' => 'Camp Ihduhapi',
      '46' => 'Camp Menogyn',
      '63' => 'Camp St. Croix',
      '44' => 'Camp Warren',
      '64' => 'Camp Widjiwagan',
      '87' => 'Maplewood Community Center',
      '50' => 'Rochester',
      '17' => 'Dayton at Gaviidae - DT Minneapolis',
      '61' => 'Camp Northern Lights',
    ];

    if (empty($mapping[$branch_id])) {
      return 'wrong branch';
    }

    return $mapping[$branch_id];
  }

}
