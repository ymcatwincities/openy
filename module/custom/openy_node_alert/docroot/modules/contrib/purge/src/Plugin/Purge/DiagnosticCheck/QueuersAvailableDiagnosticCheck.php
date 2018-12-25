<?php

namespace Drupal\purge\Plugin\Purge\DiagnosticCheck;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;
use Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface;

/**
 * Queuers.
 *
 * @PurgeDiagnosticCheck(
 *   id = "queuersavailable",
 *   title = @Translation("Queuers"),
 *   description = @Translation("Checks if there is a queuer that propagates the queue."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class QueuersAvailableDiagnosticCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
   * @var \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface
   */
  protected $purgeQueuers;

  /**
   * Constructs a \Drupal\purge\Plugin\Purge\DiagnosticCheck\PurgerAvailableCheck object.
   *
   * @param \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface $purge_queuers
   *   The purge queuers service.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(QueuersServiceInterface $purge_queuers, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->purgeQueuers = $purge_queuers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('purge.queuers'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    if (count($this->purgeQueuers) === 0) {
      $this->value = '';
      $this->recommendation = $this->t("You have no queuers populating the queue!");
      return SELF::SEVERITY_WARNING;
    }
    elseif (count($this->purgeQueuers) === 1) {
      $plugin_id = current($this->purgeQueuers->getPluginsEnabled());
      $queuer = $this->purgeQueuers->get($plugin_id);
      $this->value = $queuer->getLabel();
      $this->recommendation = $queuer->getDescription();
      return SELF::SEVERITY_OK;
    }
    else {
      $this->value = [];
      foreach ($this->purgeQueuers as $queuer) {
        $this->value[] = $queuer->getLabel();
      }
      $this->value = implode(', ', $this->value);
      $this->recommendation = $this->t("You have multiple queuers populating the queue.");
      return SELF::SEVERITY_OK;
    }
  }

}
