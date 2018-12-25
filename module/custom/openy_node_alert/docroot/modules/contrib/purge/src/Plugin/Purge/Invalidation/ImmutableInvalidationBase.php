<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Core\Plugin\PluginBase;
use Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface;

/**
 * Provides base implementations the immutable invalidation object.
 *
 * Immutable invalidations are not used in real-life cache invalidation, as
 * \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface doesn't accept
 * them. However, as they are read-only, they are used by user interfaces to
 * see what is in the queue without actually claiming or changing it.
 */
abstract class ImmutableInvalidationBase extends PluginBase implements ImmutableInvalidationInterface {

  /**
   * Unique runtime ID for this instance, this ID isn't the same as underlying
   * 'item_id' properties stored in the queue.
   *
   * @var int
   */
  protected $id;

  /**
   * The instance ID of the purger that is about to process this object, or
   * NULL when no longer any purgers are processing it. NULL is the default.
   *
   * @var string|null
   */
  protected $context = NULL;

  /**
   * Mixed expression (or NULL) that describes what needs to be invalidated.
   *
   * @var mixed|null
   */
  protected $expression = NULL;

  /**
   * Associative array in which the keys point to purger instances and where
   * each value represents a associative array with key-value stored metadata.
   *
   * @var array[]
   */
  protected $properties = [];

  /**
   * Associative list of which the keys refer to purger instances and the values
   * are \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface constants.
   *
   * @var int[]
   */
  protected $states = [];

  /**
   * Valid states invalidations can be set to by a purger instance. Here FRESH
   * is clearly missing, but it also protects us against bad behaving purgers.
   *
   * @var int[]
   */
  protected $states_after_processing = [
    SELF::NOT_SUPPORTED,
    SELF::PROCESSING,
    SELF::SUCCEEDED,
    SELF::FAILED,
  ];

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return is_null($this->expression) ? '' : $this->expression;
  }

  /**
   * {@inheritdoc}
   */
  public function getExpression() {
    return $this->expression;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties() {
    if (!is_null($this->context)) {
      throw new \LogicException('Cannot retrieve properties in purger context.');
    }
    return $this->properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperty($key) {
    if (is_null($this->context)) {
      throw new \LogicException('Call ::setStateContext() before retrieving properties!');
    }
    if (isset($this->properties[$this->context][$key])) {
      return $this->properties[$this->context][$key];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {

    // Regardless of the context, when there are no states stored we're FRESH.
    if (empty($this->states)) {
      return SELF::FRESH;
    }

    // In general context, we need to resolve what the invalidation state is.
    if ($this->context === NULL) {
      $totals = [SELF::SUCCEEDED => 0, SELF::NOT_SUPPORTED => 0];
      $total = count($this->states);
      foreach ($this->states as $state) {
        if (isset($totals[$state])) {
          $totals[$state]++;
        }
      }

      // If all purgers failed to support it, its unsupported.
      if ($totals[SELF::NOT_SUPPORTED] === $total) {
        return SELF::NOT_SUPPORTED;
      }
      // If all purgers succeeded, it succeeded.
      elseif ($totals[SELF::SUCCEEDED] === $total) {
        return SELF::SUCCEEDED;
      }
      // Failure and processing are the only states left we can be in, when any
      // of those are found, that's what the general state will reflect.
      elseif (in_array(SELF::FAILED, $this->states)) {
        return SELF::FAILED;
      }
      elseif (in_array(SELF::PROCESSING, $this->states)) {
        return SELF::PROCESSING;
      }
      // Catch combination states where one or more purgers added NOT_SUPPORTED
      // but other purgers added states as well.
      elseif (in_array(SELF::NOT_SUPPORTED, $this->states)) {
        if (in_array(SELF::FAILED, $this->states)) {
          return SELF::FAILED;
        }
        elseif (in_array(SELF::PROCESSING, $this->states)) {
          return SELF::PROCESSING;
        }
        elseif (in_array(SELF::SUCCEEDED, $this->states)) {
          return SELF::SUCCEEDED;
        }
      }
      throw new \LogicException("Invalidation state data integrity violation");
    }

    // When the purger instance ID is known, the state becomes more specific.
    else {
      if (isset($this->states[$this->context])) {
        return $this->states[$this->context];
      }
      return SELF::FRESH;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getStateString() {
    $mapping = [
      SELF::FRESH         => 'FRESH',
      SELF::PROCESSING    => 'PROCESSING',
      SELF::SUCCEEDED     => 'SUCCEEDED',
      SELF::FAILED        => 'FAILED',
      SELF::NOT_SUPPORTED => 'NOT_SUPPORTED',
    ];
    return $mapping[$this->getState()];
  }

  /**
   * {@inheritdoc}
   */
  public function getStateStringTranslated() {
    $mapping = [
      SELF::FRESH         => $this->t('New'),
      SELF::PROCESSING    => $this->t('Currently invalidating'),
      SELF::SUCCEEDED     => $this->t('Succeeded'),
      SELF::FAILED        => $this->t('Failed'),
      SELF::NOT_SUPPORTED => $this->t('Not supported'),
    ];
    return $mapping[$this->getState()];
  }

  /**
   * {@inheritdoc}
   */
  public function getStates() {
    return $this->states;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->getPluginId();
  }

}
