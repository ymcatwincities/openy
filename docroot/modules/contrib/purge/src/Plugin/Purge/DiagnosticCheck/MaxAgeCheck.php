<?php

namespace Drupal\purge\Plugin\Purge\DiagnosticCheck;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;

/**
 * Tests if the TTL of your site is in a good shape.
 *
 * @PurgeDiagnosticCheck(
 *   id = "maxage",
 *   title = @Translation("Page cache max age"),
 *   description = @Translation("Tests if the TTL of your site is in a good shape."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class MaxAgeCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
   * A config object for the system performance configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a \Drupal\purge\Plugin\Purge\DiagnosticCheck\MaxAgeCheck object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(ConfigFactoryInterface $config_factory, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config_factory->get('system.performance');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('config.factory'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $max_age = $this->config->get('cache.page.max_age');
    $this->value = $this->valueTranslatable($max_age);
    if ($max_age === 0) {
      $this->recommendation = $this->t("Your site instructs external caching systems not to cache anything. Not only does this make cache invalidation futile, it is also a great danger to your website as any form of traffic can bring it down quickly!");
      return SELF::SEVERITY_WARNING;
    }
    elseif ($max_age < 300) {
      $this->recommendation = $this->t("TTL settings below 5 minutes are very dangerous, as sudden traffic increases will quickly reach your webserver(s) and bring Drupal down.");
      return SELF::SEVERITY_WARNING;
    }
    elseif ($max_age < 86400) {
      $this->recommendation = $this->t("TTL settings under 24 hours are dangerous, as sudden traffic increases will quickly reach your webserver(s) and can make Drupal slow.");
      return SELF::SEVERITY_WARNING;
    }
    elseif ($max_age < 2764800) {
      $this->recommendation = $this->t("TTL settings under a month are not recommended, the longer you set it, the better your site will perform!");
      return SELF::SEVERITY_WARNING;
    }
    elseif ($max_age < 31536000) {
      $this->recommendation = $this->t("Consider increasing your TTL to over a year, the better your site will perform!");
      return SELF::SEVERITY_OK;
    }
    else {
      $this->recommendation = $this->t("Your TTL setting is great!");
      return SELF::SEVERITY_OK;
    }
  }

  /**
   * Return a user-facing string that represents the given max_age value.
   *
   * @param int $max_age
   *   The max_age setting to format.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected function valueTranslatable($max_age) {
    if ($max_age === 0) {
      return $this->t('no caching');
    }
    elseif ($max_age === 60) {
      return $this->t('1 minute');
    }
    elseif ($max_age < 3600) {
      return $this->t('@num minutes', ['@num' => round($max_age/60)]);
    }
    elseif ($max_age === 3600) {
      return $this->t('1 hour');
    }
    elseif ($max_age < 86400) {
      return $this->t('@num hours', ['@num' => round($max_age/3600, 1)]);
    }
    elseif ($max_age === 86400) {
      return $this->t('1 day');
    }
    elseif ($max_age < 604800) {
      return $this->t('@num days', ['@num' => round($max_age/86400, 1)]);
    }
    elseif ($max_age === 604800) {
      return $this->t('1 week');
    }
    elseif ($max_age < 2764800) {
      return $this->t('@num weeks', ['@num' => round($max_age/604800, 1)]);
    }
    elseif ($max_age === 2764800) {
      return $this->t('1 month');
    }
    elseif ($max_age < 31536000) {
      return $this->t('@num months', ['@num' => round($max_age/2764800, 1)]);
    }
    elseif ($max_age === 31536000) {
      return $this->t('1 year');
    }
    return $this->t('more than 1 year');
  }

}
