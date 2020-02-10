<?php

namespace Drupal\openy_myy\Plugin\rest\resource;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\openy_myy\PluginManager\MyYDataVisits;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\openy_myy\PluginManager\MyYDataProfile;

/**
 * Provides a Demo Resource
 *
 * @RestResource(
 *   id = "myy_profile_visits_stat",
 *   label = @Translation("Stat of user visits"),
 *   uri_paths = {
 *     "canonical" = "/myy-model/data/profile/visits-stat/{personify_id}/{start}/{finish}"
 *   }
 * )
 */
class MyYProfileVisitsStat extends ResourceBase {

  /**
   * @var \Drupal\openy_myy\PluginManager\MyYDataVisits
   */
  protected $myYDataVisits;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * MyYProfileFamilyList constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param array $serializer_formats
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\openy_myy\PluginManager\MyYDataVisits $myYDataVisits
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    MyYDataVisits $myYDataVisits,
    ConfigFactoryInterface $configFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->config = $configFactory->get('openy_myy.settings');
    $this->myYDataVisits = $myYDataVisits;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('openy_node_alert'),
      $container->get('plugin.manager.myy_data_visits'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function get($personify_id, $start_date, $finish_date) {

    $myy_config = $this->config->getRawData();
    $myy_authenticator_instances = $this->myYDataVisits->getDefinitions();
    if (array_key_exists($myy_config['myy_data_visits'], $myy_authenticator_instances)) {
      $response = $this
        ->myYDataVisits
        ->createInstance($myy_config['myy_data_visits'])
        ->getVisitsCountByDate($personify_id, $start_date, $finish_date);
    } else {
      return new NotFoundHttpException();
    }

    return new ResourceResponse($response);

  }

}
