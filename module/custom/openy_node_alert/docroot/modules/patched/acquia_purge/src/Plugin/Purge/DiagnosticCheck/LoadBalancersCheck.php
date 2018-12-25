<?php

namespace Drupal\acquia_purge\Plugin\Purge\DiagnosticCheck;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;
use Drupal\acquia_purge\HostingInfoInterface;

/**
 * Load Balancers.
 *
 * @PurgeDiagnosticCheck(
 *   id = "acquia_purge_balancers",
 *   title = @Translation("Load Balancers"),
 *   description = @Translation("Checks the load balancers for this site."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {"acquia_purge"}
 * )
 */
class LoadBalancersCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
   * @var \Drupal\acquia_purge\HostingInfoInterface
   */
  protected $acquiaPurgeHostinginfo;

  /**
   * Constructs a LoadBalancersCheck object.
   *
   * @param \Drupal\acquia_purge\HostingInfoInterface $acquia_purge_hostinginfo
   *   Technical information accessors for the Acquia Cloud environment.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(HostingInfoInterface $acquia_purge_hostinginfo, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->acquiaPurgeHostingInfo = $acquia_purge_hostinginfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('acquia_purge.hostinginfo'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $balancers = $this->acquiaPurgeHostingInfo->getBalancerAddresses();
    $count = count($balancers);
    $this->value = $count ? implode(', ', $balancers) : '';

    if (!$count) {
      $this->value = '';
      $this->recommendation = $this->t("No balancers were discovered, therefore cache invalidation has been disabled. Please contact Acquia Support!");
      return SELF::SEVERITY_ERROR;
    }
    elseif ($count < 2) {
      $this->recommendation = $this->t("You have only one load balancer, this means your site cannot be failed over in case of emergency. Please contact Acquia Support!");
      return SELF::SEVERITY_WARNING;
    }
    elseif ($count >= 5) {
      $this->recommendation = $this->t("Your site has @n load balancers, which will put severe stress on your system. Please pay attention to your queue, contact Acquia Support and request less but bigger load balancers!", ['@n' => $count]);
      return SELF::SEVERITY_WARNING;
    }
    else {
      $this->recommendation = ' ';
      return SELF::SEVERITY_OK;
    }
  }

}
