<?php

namespace Drupal\purge\Plugin\Purge\DiagnosticCheck;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;
use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;

/**
 * Processors.
 *
 * @PurgeDiagnosticCheck(
 *   id = "processorsavailable",
 *   title = @Translation("Processors"),
 *   description = @Translation("Checks if there is a processor that works the queue once items are in it."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class ProcessorsAvailableDiagnosticCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
   * @var \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface
   */
  protected $purgeProcessors;

  /**
   * Constructs a ProcessorsAvailableCheck object.
   *
   * @param \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface $purge_processors
   *   The purge processors service.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(ProcessorsServiceInterface $purge_processors, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->purgeProcessors = $purge_processors;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('purge.processors'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    if (count($this->purgeProcessors) === 0) {
      $this->value = '';
      $this->recommendation = $this->t("You have no processors, the queue can now build up because of this.");
      return SELF::SEVERITY_WARNING;
    }
    elseif (count($this->purgeProcessors) === 1) {
      $plugin_id = current($this->purgeProcessors->getPluginsEnabled());
      $processor = $this->purgeProcessors->get($plugin_id);
      $this->value = $processor->getLabel();
      $this->recommendation = $processor->getDescription();
      return SELF::SEVERITY_OK;
    }
    else {
      $this->value = [];
      foreach ($this->purgeProcessors as $processor) {
        $this->value[] = $processor->getLabel();
      }
      $this->value = implode(', ', $this->value);
      $this->recommendation = $this->t("You have multiple processors working the queue.");
      return SELF::SEVERITY_OK;
    }
  }

}
