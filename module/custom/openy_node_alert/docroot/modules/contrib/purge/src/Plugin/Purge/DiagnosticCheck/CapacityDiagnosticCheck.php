<?php

namespace Drupal\purge\Plugin\Purge\DiagnosticCheck;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;

/**
 * Checks if there is purging capacity available.
 *
 * @PurgeDiagnosticCheck(
 *   id = "capacity",
 *   title = @Translation("Capacity"),
 *   description = @Translation("Checks if there is invalidation capacity available."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class CapacityDiagnosticCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected $purgePurgers;

  /**
   * Constructs a CapacityCheck object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purge_purgers
   *   The purge executive service, which wipes content from external caches.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PurgersServiceInterface $purge_purgers) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('purge.purgers')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $tracker = $this->purgePurgers->capacityTracker();
    $this->value = $tracker->getRemainingInvalidationsLimit();
    $ideal_limit = $tracker->getIdealConditionsLimit();
    $placeholders = ['@limit' => $this->value, '@ideallimit' => $ideal_limit];

    if ($this->value === 0) {
      $this->recommendation = $this->t("There is no purging capacity available.");
      return SELF::SEVERITY_WARNING;
    }
    elseif ($this->value < 5) {
      $this->recommendation = $this->t("Your system invalidates just @limit items through webserver-initated processing. If you notice that purge cannot keep up with its queue, reconsider your configuration.", $placeholders);
      return SELF::SEVERITY_WARNING;
    }
    else {
      $this->recommendation = $this->t("Your system can invalidate @limit items when you're processing through webserver-initated requests. Under ideal conditions - for example via Drush - the capacity would be @ideallimit.", $placeholders);
      return SELF::SEVERITY_OK;
    }
  }

}
