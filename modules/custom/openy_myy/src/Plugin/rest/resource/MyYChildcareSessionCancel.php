<?php

namespace Drupal\openy_myy\Plugin\rest\resource;

use Drupal\openy_myy\PluginManager\MyYDataChildcare;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\rest\ResourceResponse;

/**
 * MyY cancel childcare scheduled events rest resource.
 *
 * @RestResource(
 *   id = "myy_childcare_session_cancel",
 *   label = @Translation("Cancel sessions for childcare product"),
 *   uri_paths = {
 *     "canonical" = "/myy-model/data/childcare/session-cancel/{date}/{type}"
 *   }
 * )
 */
class MyYChildcareSessionCancel extends ResourceBase {

  /**
   * @var \Drupal\openy_myy\PluginManager\MyYDataChildcare
   */
  protected $myYDataChildcare;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * MyYChildcareScheduled constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param array $serializer_formats
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\openy_myy\PluginManager\MyYDataChildcare $myYDataChildcare
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    MyYDataChildcare $myYDataChildcare,
    ConfigFactoryInterface $configFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->config = $configFactory->get('openy_myy.settings');
    $this->myYDataChildcare = $myYDataChildcare;
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
      $container->get('logger.factory')->get('openy_myy_data_childcare'),
      $container->get('plugin.manager.myy_data_childcare'),
      $container->get('config.factory')
    );
  }

  public function get($date, $type) {

    /**
     * @TODO Add check that order is connected to this user
     */

    $myy_config = $this->config->getRawData();
    $myy_authenticator_instances = $this->myYDataChildcare->getDefinitions();

    if (array_key_exists($myy_config['myy_data_childcare'], $myy_authenticator_instances)) {
      $response = $this
        ->myYDataChildcare
        ->createInstance($myy_config['myy_data_childcare'])
        ->cancelChildcareSessions($date, $type);
    } else {
      return new NotFoundHttpException();
    }

    return new ResourceResponse($response);


  }

}
