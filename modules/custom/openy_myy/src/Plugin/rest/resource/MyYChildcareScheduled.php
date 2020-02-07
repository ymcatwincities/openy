<?php

namespace Drupal\openy_myy\Plugin\rest\resource;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\openy_myy\PluginManager\MyYDataChildcare;

/**
 * MyY scheduled childcare session resource
 *
 * @RestResource(
 *   id = "myy_childcare_scheduled",
 *   label = @Translation("List of scheduled childcare items"),
 *   uri_paths = {
 *     "canonical" = "/myy-model/data/childcare/scheduled"
 *   }
 * )
 */
class MyYChildcareScheduled extends ResourceBase {

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

  /**
   * {@inheritdoc}
   */
  public function get() {
    $myy_config = $this->config->getRawData();
    $myy_authenticator_instances = $this->myYDataChildcare->getDefinitions();
    if (array_key_exists($myy_config['myy_data_childcare'], $myy_authenticator_instances)) {
      $response = $this
        ->myYDataChildcare
        ->createInstance($myy_config['myy_data_childcare'])
        ->getChildcareScheduledEvents(date('Y-m-d H:i:s'), date('Y-m-d H:i:s'));
    } else {
      return new NotFoundHttpException();
    }

    return new ResourceResponse($response);

  }

}
