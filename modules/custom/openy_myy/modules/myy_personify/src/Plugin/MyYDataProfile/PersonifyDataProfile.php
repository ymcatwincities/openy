<?php

namespace Drupal\myy_personify\Plugin\MyYDataProfile;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\myy_personify\PersonifyUserHelper;
use Drupal\openy_myy\PluginManager\MyYDataProfileInterface;
use Drupal\personify\PersonifyClient;
use Drupal\personify\PersonifySSO;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Personify Profile data plugin.
 *
 * @MyYDataProfile(
 *   id = "myy_personify_data_profile",
 *   label = "MyY Data Profile: Personify",
 *   description = "Profile data communication using Personify",
 * )
 */
class PersonifyDataProfile extends PluginBase implements MyYDataProfileInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\personify\PersonifySSO;
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
   * @var string
   */
  protected $personify_domain;

  /**
   * PersonifyDataProfile constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\personify\PersonifySSO $personifySSO
   * @param \Drupal\personify\PersonifyClient $personifyClient
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PersonifySSO $personifySSO,
    PersonifyClient $personifyClient,
    ConfigFactoryInterface $configFactory,
    LoggerChannelFactory $loggerChannelFactory,
    PersonifyUserHelper $personifyUserHelper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('personify.sso_client'),
      $container->get('personify.client'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('myy_personify_user_helper')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getProfileData() {
    return $this
      ->personifyClient
      ->doAPIcall(
        'GET',
        "CustomerInfos(MasterCustomerId='" . $this->personifyUserHelper->personifyGetId() . "',SubCustomerId=0)"
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getProfileAvatar() {
    // TODO: Implement getProfileAvatar() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getProfileHealthInformation() {
    // TODO: Implement getProfileHealthInformation() method.
  }

  public function updateHealthInformation(array $health_info) {
    // TODO: Implement updateHealthInformation() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getFamilyInfo() {

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
    $output['household'][] = [
      'name' => $my_data['d']['LabelName'],
      'age' => $now->diff($my_bdate)->format('%y'),
      'RelationshipCode' => 'ME',
      'ProfileLinks' => $this->getHouseholdProfileLinks(),
    ];

    $relationship_data = $this
      ->personifyClient
      ->doAPIcall(
        'GET',
        "CustomerInfos(MasterCustomerId='" . $personifyID . "',SubCustomerId=0)/Relationships"
      );


    foreach ($relationship_data['d'] as $relationship) {

      $family_member_profile_data = $this
        ->personifyClient
        ->doAPIcall(
          'GET',
          "CustomerInfos(MasterCustomerId='" . $relationship['RelatedMasterCustomerId'] . "',SubCustomerId=0)"
        );

      $family_member_birthdate = DrupalDateTime::createFromTimestamp(preg_replace('/[^0-9]/', '', $family_member_profile_data['d']['CL_BirthDate']) / 1000, 'UTC');

      $output['household'][] = [
        'name' => $relationship['RelatedName'],
        'RelationshipCode' => $relationship['RelationshipCode'],
        'age' => $now->diff($family_member_birthdate)->format('%y'),
        'RelatedMasterCustomerId' => $relationship['RelatedMasterCustomerId'],
        'ProfileLinks' => $this->getHouseholdProfileLinks(),
      ];
    }

    return $output;

  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipInfo() {

    $personifyID = $this->personifyUserHelper->personifyGetId();

    $memberships = $this
      ->personifyClient
      ->doAPIcall(
        'GET',
        'OrderMembershipInformationViews?$filter=ShipMasterCustomerId%20eq%20%27' . $personifyID . '%27&$format=json'
      );

    $last_membership = end($memberships['d']);
    $orderData = $this
      ->personifyClient
      ->doAPIcall(
        'GET',
        'OrderMasterInfos(%27' . $last_membership['OrderNumber'] . '%27)/OrderDetailInfo?$format=json'
      );

    $orderType = $productPrice = $order_time = '';

    foreach ($orderData['d'] as $orderLine) {
      if (strpos(strtolower($orderLine['ProductDescription']), 'membership') !== FALSE) {
        $orderType = 'Month-to-month';
        $productPrice = $orderLine['BaseTotalAmount'];
        $order_time = substr($orderLine['OrderDate'], 6, 10);
      }
    }

    return [
      'title' => $last_membership['Membership'],
      'status' => $last_membership['FulfillStatusDescr'],
      'orderType' => $orderType,
      'productPrice' => $productPrice,
      'orderDate' => date('Y-m-d', $order_time)
    ];

  }

  /**
   * Mapping of household links for Personify
   * @return array
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

  /**
   * {@inheritdoc}
   */
  public function addEmergencyContact(array $contact_data) {
    // TODO: Implement addEmergencyContact() method.
  }

  /**
   * {@inheritdoc}
   */
  public function updateEmergencyContact(array $contact_data) {
    // TODO: Implement updateEmergencyContact() method.
  }

  /**
   * {@inheritdoc}
   */
  public function updateProfileFields(array $profile) {
    // TODO: Implement updateProfileFields() method.
  }

  /**
   * {@inheritdoc}
   */
  public function updateProfilePassword($old_pwd, $new_pwd) {
    // TODO: Implement updateProfilePassword() method.
  }

  /**
   * {@inheritdoc}
   */
  public function updateProfilePhoneNumber(array $phone) {
    // TODO: Implement updateProfilePhoneNumber() method.
  }


}
