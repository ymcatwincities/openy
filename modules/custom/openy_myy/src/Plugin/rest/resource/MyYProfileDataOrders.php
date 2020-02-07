<?php

namespace Drupal\openy_myy\Plugin\rest\resource;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\openy_myy\PluginManager\MyYDataOrders;

/**
 * MyY get user orders
 *
 * @RestResource(
 *   id = "myy_orders",
 *   label = @Translation("My orders"),
 *   uri_paths = {
 *     "canonical" = "/myy-model/data/orders/{ids}/{date_start}/{date_end}"
 *   }
 * )
 */
class MyYProfileDataOrders extends ResourceBase {

  /**
   * @var \Drupal\openy_myy\PluginManager\MyYDataOrders
   */
  protected $myYDataOrders;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * MyYProfileDataOrders constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param array $serializer_formats
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\openy_myy\PluginManager\MyYDataOrders $myYDataOrders
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    MyYDataOrders $myYDataOrders,
    ConfigFactoryInterface $configFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->config = $configFactory->get('openy_myy.settings');
    $this->myYDataOrders = $myYDataOrders;
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
      $container->get('logger.factory')->get('openy_myy_data_orders'),
      $container->get('plugin.manager.myy_data_orders'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function get($ids, $date_start, $date_end) {

    $myy_config = $this->config->getRawData();
    $myy_orders_instances = $this->myYDataOrders->getDefinitions();

    if (array_key_exists($myy_config['myy_data_orders'], $myy_orders_instances)) {
      $response = $this
        ->myYDataOrders
        ->createInstance($myy_config['myy_data_orders'])
        ->getOrders($ids, $date_start, $date_end);
    } else {
      return new NotFoundHttpException();
    }

    return new ResourceResponse($response);

  }

}
