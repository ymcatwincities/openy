<?php

namespace Drupal\myy_personify\Plugin\MyYDataProfile;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\openy_myy\PluginManager\MyYDataProfileInterface;
use Drupal\personify\PersonifySSO;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Personify Profile data plugin.
 *
 * @MyYDataProfile(
 *   id = "myy_personify_data_profile",
 *   label = "MyY Data Profile: Personify",
 *   description = "Profile data communication using Personify",
 * )
 */
class PersonifyDataProfile implements MyYDataProfileInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\personify\PersonifySSO;
   */
  private $personifySSO;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * PersonifyAuthenticator constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\personify\PersonifySSO $personifySSO
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PersonifySSO $personifySSO,
    ConfigFactoryInterface $configFactory,
    LoggerChannelFactory $loggerChannelFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->personifySSO = $personifySSO;
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
    // TODO: Implement getFamilyInfo() method.
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