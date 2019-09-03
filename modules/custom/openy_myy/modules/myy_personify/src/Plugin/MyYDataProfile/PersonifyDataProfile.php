<?php

namespace Drupal\myy_personify\Plugin\MyYDataProfile;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
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
    LoggerChannelFactory $loggerChannelFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->personifySSO = $personifySSO;
    $this->personifyClient = $personifyClient;
    $this->config = $configFactory->get('myy_personify.settings');
    $this->logger = $loggerChannelFactory->get('personify_authenticator');
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
      $container->get('logger.factory')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getProfileData() {
    // TODO: Implement getProfileData() method.
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

    $personifyID = $this->personifySSO->getCustomerIdentifier($_COOKIE['Drupal_visitor_personify_authorized']);
    $relationship_data = $this
      ->personifyClient
      ->doAPIcall(
        'GET',
        "CustomerInfos(MasterCustomerId='" . $personifyID . "',SubCustomerId=0)/Relationships"
      );

    $output = [];

    foreach ($relationship_data->d as $relationship) {

      $family_member_profile_data = $this
        ->personifyClient
        ->doAPIcall(
          'GET',
          "CustomerInfos(MasterCustomerId='" . $relationship->RelatedMasterCustomerId . "',SubCustomerId=0)"
        );

      $family_member_birthdate = DrupalDateTime::createFromTimestamp(preg_replace('/[^0-9]/', '', $family_member_profile_data->d->CL_BirthDate) / 1000, 'UTC');
      $now = new DrupalDateTime();
      $output['household'][] = [
        'name' => $relationship->RelatedName,
        'RelationshipCode' => $relationship->RelationshipCode,
        'age' => $now->diff($family_member_birthdate)->format('%y'),
      ];
    }

    return $output;

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