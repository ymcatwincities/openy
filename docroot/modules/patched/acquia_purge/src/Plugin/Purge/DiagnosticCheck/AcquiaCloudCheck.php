<?php

/**
 * @file
 * Contains \Drupal\acquia_purge\Plugin\Purge\DiagnosticCheck\AcquiaCloudCheck.
 */

namespace Drupal\acquia_purge\Plugin\Purge\DiagnosticCheck;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;
use Drupal\acquia_purge\HostingInfoInterface;

/**
 * Acquia Cloud.
 *
 * @PurgeDiagnosticCheck(
 *   id = "acquia_purge_cloud",
 *   title = @Translation("Acquia Cloud"),
 *   description = @Translation("Checks if this site runs on Acquia Cloud."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {"acquia_purge"}
 * )
 */
class AcquiaCloudCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
   * @var \Drupal\acquia_purge\HostingInfoInterface
   */
  protected $acquiaPurgeHostinginfo;

  /**
   * Constructs a AcquiaCloudCheck object.
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

    // Block the entire system when this is a third-party platform.
    if (!$this->acquiaPurgeHostingInfo->isThisAcquiaCloud()) {
      $this->recommendation = $this->t("You are not running on Acquia Cloud, this is a mandatory requirement for the Acquia purger.");
      return SELF::SEVERITY_ERROR;
    }

    // Display information about the site.
    $this->value = $this->t("@group (@env)", [
      '@group' => $this->acquiaPurgeHostingInfo->getSiteGroup(),
      '@env' => $this->acquiaPurgeHostingInfo->getSiteEnvironment()]);
    $this->recommendation = ' ';
    return SELF::SEVERITY_OK;
  }

}
