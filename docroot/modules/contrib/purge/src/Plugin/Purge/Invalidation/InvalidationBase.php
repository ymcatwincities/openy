<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationBase;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\MissingExpressionException;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidStateException;

/**
 * Provides base implementations for the invalidation object.
 *
 * Invalidations are small value objects that describe and track invalidations
 * on one or more external caching systems within the Purge pipeline. These
 * objects can be directly instantiated from InvalidationsService and float
 * freely between the QueueService and the PurgersService.
 */
abstract class InvalidationBase extends ImmutableInvalidationBase implements InvalidationInterface {

  /**
   * Constructs \Drupal\purge\Plugin\Purge\Invalidation\InvalidationBase.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param int $id
   *   Unique integer ID for this object instance (during runtime).
   * @param mixed|null $expression
   *   Value - usually string - that describes the kind of invalidation, NULL
   *   when the type of invalidation doesn't require $expression. Types usually
   *   validate the given expression and throw exceptions for bad input.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $id, $expression) {
    parent::__construct([], $plugin_id, $plugin_definition);
    $this->id = $id;
    $this->expression = $expression;
    $this->validateExpression();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      [],
      $plugin_id,
      $plugin_definition,
      $configuration['id'],
      $configuration['expression']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function deleteProperty($key) {
    if (is_null($this->context)) {
      throw new \LogicException('Call ::setStateContext() before deleting properties!');
    }
    if (isset($this->properties[$this->context][$key])) {
      unset($this->properties[$this->context][$key]);
      if (empty($this->properties[$this->context])) {
        unset($this->properties[$this->context]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setProperty($key, $value) {
    if (is_null($this->context)) {
      throw new \LogicException('Call ::setStateContext() before deleting properties!');
    }
    if (!isset($this->properties[$this->context])) {
      $this->properties[$this->context] = [];
    }
    $this->properties[$this->context][$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setState($state) {
    if (!is_int($state)) {
      throw new InvalidStateException('$state not an integer!');
    }
    if (($state < 0) || ($state > 4)) {
      throw new InvalidStateException('$state is out of range!');
    }
    if (is_null($this->context)) {
      throw new \LogicException('State cannot be set in NULL context!');
    }
    $this->states[$this->context] = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function setStateContext($purger_instance_id) {
    $new_is_string = is_string($purger_instance_id);
    $new_is_null = is_null($purger_instance_id);
    if ($new_is_string && (!strlen($purger_instance_id))) {
      throw new \LogicException('Parameter $purger_instance_id is empty!');
    }
    elseif ((!$new_is_string) && (!$new_is_null)) {
      throw new \LogicException('Parameter $purger_instance_id is not NULL or a non-empty string!');
    }
    elseif ($purger_instance_id === $this->context) {
      return;
    }

    // Find out if states returning from purgers are actually valid.
    $old_is_string = is_string($this->context);
    $both_strings = $old_is_string && $new_is_string;
    $transferring = $both_strings && ($this->context != $purger_instance_id);
    if ($transferring || ($old_is_string && $new_is_null)) {
      if (!in_array($this->getState(), $this->states_after_processing)) {
        throw new BadPluginBehaviorException("Only NOT_SUPPORTED, PROCESSING, SUCCEEDED and FAILED are valid outbound states.");
      }
    }

    $this->context = $purger_instance_id;
  }

  /**
   * {@inheritdoc}
   */
  public function validateExpression() {
    $d = $this->getPluginDefinition();
    $topt = ['@type' => strtolower($d['label'])];
    if ($d['expression_required'] && is_null($this->expression)) {
      throw new MissingExpressionException($this->t("Argument required for @type invalidation.", $topt));
    }
    elseif ($d['expression_required'] && empty($this->expression) && !$d['expression_can_be_empty']) {
      throw new InvalidExpressionException($this->t("Argument required for @type invalidation.", $topt));
    }
    elseif (!$d['expression_required'] && !is_null($this->expression)) {
      throw new InvalidExpressionException($this->t("Argument given for @type invalidation.", $topt));
    }
    elseif (!is_null($this->expression) && !is_string($this->expression) && $d['expression_must_be_string']) {
      throw new InvalidExpressionException($this->t("String argument required for @type invalidation.", $topt));
    }
  }

}
