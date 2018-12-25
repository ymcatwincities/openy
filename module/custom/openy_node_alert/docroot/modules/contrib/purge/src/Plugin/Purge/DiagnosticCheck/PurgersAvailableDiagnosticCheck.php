<?php

namespace Drupal\purge\Plugin\Purge\DiagnosticCheck;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;

/**
 * Checks if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeDiagnosticCheck(
 *   id = "purgersavailable",
 *   title = @Translation("Purgers"),
 *   description = @Translation("Checks if there is a purger plugin available."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class PurgersAvailableDiagnosticCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected $purgePurgers;

  /**
   * Constructs a \Drupal\purge\Plugin\Purge\DiagnosticCheck\PurgerAvailableCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purge_purgers
   *   The purge executive service, which wipes content from external caches.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, PurgersServiceInterface $purge_purgers) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->purgePurgers = $purge_purgers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('purge.purgers')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $purgerlabels  = $this->purgePurgers->getLabels();

    // Put all enabled in a comma separated value.
    $this->value = '';
    if (!empty($purgerlabels)) {
      $this->value = [];
      foreach ($purgerlabels as $label) {
        $this->value[] = (string) $label;
      }
      $this->value = implode(', ', $this->value);
    }

    // Test for an empty set of labels, indicating no purgers are configured.
    if (empty($purgerlabels)) {
      $this->recommendation = $this->t("There is no purger loaded which means ".
        "that you need a module enabled to provide a purger plugin to clear ".
        "your external cache or CDN.");
      return SELF::SEVERITY_ERROR;
    }
    elseif (count($purgerlabels) == 1) {
      $this->recommendation = $this->t("Purger configured.");
      return SELF::SEVERITY_OK;
    }
    elseif (count($purgerlabels) > 3) {
      $this->recommendation = $this->t("You have more than 3 purgers active ".
        "on one system. This introduces the risk of congesting Drupal as ".
        "multiple purgers are clearing external caches. It is highly ".
        "recommended is to simplify your caching architecture if possible.");
      return SELF::SEVERITY_WARNING;
    }
    else {
      $this->recommendation = $this->t("Purgers configured.");
      return SELF::SEVERITY_OK;
    }
  }

}
