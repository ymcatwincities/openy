<?php

namespace Drupal\myy_personify;

use Drupal\personify\PersonifySSO;
use Drupal\personify\PersonifyClient;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Class PersonifyUserData helper.
 *
 * @package Drupal\myy_personify
 */
class PersonifyUserData implements PersonifyUserDataInterface {

  /**
   * @var \Drupal\personify\PersonifySSO
   */
  protected $personifySSO;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * @var \Drupal\personify\PersonifyClient
   */
  protected $personifyClient;

  /**
   * @var \Drupal\myy_personify\PersonifyUserHelper
   */
  protected $personifyUserHelper;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * PersonifyUserHelper constructor.
   *
   * @param \Drupal\personify\PersonifySSO $personifySSO
   */
  public function __construct(
    PersonifySSO $personifySSO,
    PersonifyClient $personifyClient,
    PersonifyUserHelper $personifyUserHelper,
    ConfigFactoryInterface $configFactory,
    LoggerChannelFactory $loggerChannelFactory
  ) {
    $this->personifySSO = $personifySSO;
    $this->personifyClient = $personifyClient;
    $this->config = $configFactory->get('myy_personify.settings');
    $this->logger = $loggerChannelFactory->get('personify_authenticator');
    $this->personifyUserHelper = $personifyUserHelper;
    $personify_config = $configFactory->get('personify.settings')->getRawData();
    $this->personify_domain = $personify_config[$personify_config['environment'] . '_endpoint'];

  }

  /**
   * {@inheritdoc}
   */
  public function getFamilyData() {

    $personifyID = $this->personifyUserHelper->personifyGetId();
    $output = [];

    $my_data = $this
      ->personifyClient
      ->doAPIcall(
        'GET',
        "CustomerInfos(MasterCustomerId='" . $personifyID . "',SubCustomerId=0)"
      );

    $my_bdate = DrupalDateTime::createFromTimestamp(preg_replace('/[^0-9]/', '', $my_data['d']['CL_BirthDate']) / 1000, 'UTC');
    $now = new DrupalDateTime();
    $name = explode(' ', $my_data['d']['LabelName']);
    $short_name = $name[0][0] . ' ' . $name[1][0];
    $output['household'][] = [
      'name' =>  $name[1] . ', ' . $name[0],
      'short_name' => $short_name,
      'age' => $now->diff($my_bdate)->format('%y'),
      'RelationshipCode' => 'ME',
      'ProfileLinks' => $this->getHouseholdProfileLinks(),
      'color' => 'color-0',
    ];

    $relationship_data = $this
      ->personifyClient
      ->doAPIcall(
        'GET',
        "CustomerInfos(MasterCustomerId='" . $personifyID . "',SubCustomerId=0)/Relationships"
      );

    $color = 1;
    foreach ($relationship_data['d'] as $relationship) {

      $family_member_profile_data = $this
        ->personifyClient
        ->doAPIcall(
          'GET',
          "CustomerInfos(MasterCustomerId='" . $relationship['RelatedMasterCustomerId'] . "',SubCustomerId=0)"
        );

      $family_member_birthdate = DrupalDateTime::createFromTimestamp(preg_replace('/[^0-9]/', '', $family_member_profile_data['d']['CL_BirthDate']) / 1000, 'UTC');

      // Hide family members with duplicated status.
      if ($relationship['RelationshipCode'] == 'MERGED_TO') {
        continue;
      }

      $name = explode(', ', $relationship['RelatedName']);
      $short_name = $name[1][0] . ' ' . $name[0][0];
      $output['household'][] = [
        'name' => $relationship['RelatedName'],
        'short_name' => $short_name,
        'RelationshipCode' => $relationship['RelationshipCode'],
        'age' => $now->diff($family_member_birthdate)->format('%y'),
        'RelatedMasterCustomerId' => $relationship['RelatedMasterCustomerId'],
        'ProfileLinks' => $this->getHouseholdProfileLinks(),
        'color' => 'color-' . $color++,
      ];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getHouseholdProfileLinks() {

    $ddata = parse_url($this->personify_domain);
    $domain = $ddata['scheme'] . '://' . $ddata['host'];

    return [
      $domain . '/PersonifyEbusiness/Product-Search' => 'Product Search',
      $domain . '/PersonifyEbusiness/MyY/ChildCare-Attendance-Calendar' => 'My ChildCare Calendar',
      $domain . '/PersonifyEbusiness/MyY/Health-Form' => 'Health Information',
      $domain . '/PersonifyEbusiness/MyY/Emergency-Contact' => 'Emergency Contacts',
      $domain . '/PersonifyEbusiness/MyY/My-Programs' => 'View Programs',
      $domain . '/PersonifyEbusiness/MyY/Member-Visits' => 'View Facility Visits',
      $domain . '/PersonifyEbusiness/MyY/My-Family' => 'Relationships',
      $domain . '/PersonifyEbusiness/MyY/Personal-Information' => 'Profile'
    ];
  }

}
