<?php

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\purge\Plugin\Purge\Purger\Exception\BadPluginBehaviorException;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException;
use Drupal\purge\Plugin\Purge\Purger\CapacityTrackerInterface;
use Drupal\purge\Counter\Counter;

/**
 * Provides the capacity tracker.
 */
class CapacityTracker implements CapacityTrackerInterface {

  /**
   * Associative array of cooldown times per purger, as int values.
   *
   * @var float[]
   */
  protected $cooldownTimes;

  /**
   * The total (theoretic) time all purgers wait after invalidation.
   *
   * @var float
   */
  protected $cooldownTimeTotal;

  /**
   * The number of invalidations that can be processed under ideal conditions.
   *
   * @var int
   */
  protected $idealConditionsLimit;

  /**
   * Keeps cached copies of all calculated lease time hints.
   *
   * @var int[]
   */
  protected $leaseTimeHints = [];

  /**
   * The maximum number of seconds available to cache invalidation. Zero means
   * that PHP has no fixed execution time limit, for instance on the CLI.
   *
   * @var int
   */
  protected $maxExecutionTime;

  /**
   * Holds all loaded purgers plugins.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgerInterface[]
   */
  protected $purgers = NULL;

  /**
   * Holds all calculated invalidations limits during runtime, this allows
   * ::getRemainingInvalidationsLimit() to calculate the least as possible.
   *
   * @var int[]
   */
  protected $remainingInvalidationsLimits = [];

  /**
   * The execution time spent on cache invalidation during this request.
   *
   * @var \Drupal\purge\Counter\CounterInterface
   */
  protected $spentExecutionTime;

  /**
   * Counter represting the number of invalidation objects touched this request.
   *
   * @var \Drupal\purge\Counter\CounterInterface
   */
  protected $spentInvalidations;

  /**
   * The maximum number of seconds - as a float - it takes each purger to
   * process a single cache invalidation.
   *
   * @var float[]
   */
  protected $timeHints;

  /**
   * The maximum number of seconds - as a float - it takes all purgers to
   * process a single cache invalidation (regardless of type).
   *
   * @var float
   */
  protected $timeHintTotal;

  /**
   * Gather ::getCooldownTime() data by iterating all loaded purgers.
   */
  protected function gatherCooldownTimes() {
    if (is_null($this->cooldownTimes)) {
      if (is_null($this->purgers)) {
        throw new \LogicException("::setPurgers() hasn't been called!");
      }
      $this->cooldownTimes = [];
      foreach ($this->purgers as $id => $purger) {
        $cooldown_time = $purger->getCooldownTime();
        if (!is_float($cooldown_time)) {
          $method = sprintf("%s::getCooldownTime()", get_class($purger));
          throw new BadPluginBehaviorException(
            "$method did not return a floating point value.");
        }
        if ($cooldown_time < 0.0) {
          $method = sprintf("%s::getCooldownTime()", get_class($purger));
          throw new BadPluginBehaviorException(
            "$method returned $cooldown_time, a value lower than 0.0.");
        }
        if ($cooldown_time > 3.0) {
          $method = sprintf("%s::getCooldownTime()", get_class($purger));
          throw new BadPluginBehaviorException(
            "$method returned $cooldown_time, a value higher than 3.0.");
        }
        $this->cooldownTimes[$id] = $cooldown_time;
      }
    }
  }

  /**
   * Gather ::getTimeHint() data by iterating all loaded purgers.
   */
  protected function gatherTimeHints() {
    if (is_null($this->timeHints)) {
      if (is_null($this->purgers)) {
        throw new \LogicException("::setPurgers() hasn't been called!");
      }
      $this->timeHints = [];
      if (count($this->purgers)) {
        foreach ($this->purgers as $id => $purger) {
          $hint = $purger->getTimeHint();

          // Be strict about what values are accepted, better throwing exceptions
          // than having a crashing website because it is trashing.
          if (!is_float($hint)) {
            $method = sprintf("%s::getTimeHint()", get_class($purger));
            throw new BadPluginBehaviorException(
              "$method did not return a floating point value.");
          }
          if ($hint < 0.1) {
            $method = sprintf("%s::getTimeHint()", get_class($purger));
            throw new BadPluginBehaviorException(
              "$method returned $hint, a value lower than 0.1.");
          }
          if ($hint > 10.0) {
            $method = sprintf("%s::getTimeHint()", get_class($purger));
            throw new BadPluginBehaviorException(
              "$method returned $hint, a value higher than 10.0.");
          }
          $this->timeHints[$id] = $hint;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCooldownTime($purger_instance_id) {
    $this->gatherCooldownTimes();
    if (!isset($this->cooldownTimes[$purger_instance_id])) {
      throw new BadBehaviorException("Instance id '$purger_instance_id' does not exist!");
    }
    return $this->cooldownTimes[$purger_instance_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getCooldownTimeTotal() {
    if (is_null($this->cooldownTimeTotal)) {
      $this->gatherCooldownTimes();
      $this->cooldownTimeTotal = array_sum($this->cooldownTimes);
    }
    return $this->cooldownTimeTotal;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdealConditionsLimit() {
    if (is_null($this->idealConditionsLimit)) {
      if (is_null($this->purgers)) {
        throw new \LogicException("::setPurgers() hasn't been called!");
      }

      // Fail early when no purgers are loaded.
      if (empty($this->purgers)) {
        $this->idealConditionsLimit = 0;
        return $this->idealConditionsLimit;
      }

      // Find the lowest emitted ideal conditions limit.
      $this->idealConditionsLimit = [];
      foreach ($this->purgers as $purger) {
        $limit = $purger->getIdealConditionsLimit();
        if ((!is_int($limit)) || ($limit < 1)) {
          $method = sprintf("%s::getIdealConditionsLimit()", get_class($purger));
          throw new BadPluginBehaviorException(
            "$method returned $limit, which has to be a integer higher than 0.");
        }
        $this->idealConditionsLimit[] = $limit;
      }
      $this->idealConditionsLimit = (int) min($this->idealConditionsLimit);
    }
    return $this->idealConditionsLimit;
  }

  /**
   * {@inheritdoc}
   */
  public function getLeaseTimeHint($items) {
    if (($items < 1) || (!is_int($items))) {
      throw new BadPluginBehaviorException('$items is below 1 or no integer.');
    }

    // Create a closure that calculates how much time it would take. It takes
    // cooldown time as well as potential code overhead into account.
    $calculate = function ($items) {
      $s = ($items * $this->getTimeHintTotal()) + $this->getCooldownTimeTotal();
      $s++;
      return (int) ceil($s);
    };

    // Use the items number as cache key and fetch/add calculations from/to it.
    if (!isset($this->leaseTimeHints[$items])) {
      $this->leaseTimeHints[$items] = $calculate($items);
    }
    return $this->leaseTimeHints[$items];
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxExecutionTime() {
    if (is_null($this->maxExecutionTime)) {
      $this->maxExecutionTime = (int) ini_get('max_execution_time');
      // When the limit isn't infinite, chop 20% off for the rest of Drupal.
      if ($this->maxExecutionTime !== 0) {
        $this->maxExecutionTime = intval(0.8 * $this->maxExecutionTime);
      }
    }
    return $this->maxExecutionTime;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemainingInvalidationsLimit() {
    if (is_null($this->purgers)) {
      throw new \LogicException("::setPurgers() hasn't been called!");
    }

    // Create a closure that calculates the current limit.
    $calculate = function ($spent_inv) {
      if (empty($this->purgers)) {
        return 0;
      }

      // Fetch PHP's maximum execution time. However, Purge can run longer when
      // the returned value is zero (=infinite). If so, we return outer limits.
      $time_max = $this->getMaxExecutionTime();
      if ($time_max === 0) {
        return (int) ($this->getIdealConditionsLimit() - $spent_inv);
      }

      // We do operate on a time-based limit. Calculate how much time there is
      // left, to base our estimate on by subtracting time spent and waiting time.
      $time_left = $time_max - $this->spentExecutionTime()->get() - $this->getCooldownTimeTotal();

      // Calculate how many invaldiations can still be processed with the time
      // that is left and subtract the number of already invalidated items.
      $limit = intval(floor($time_left / $this->getTimeHintTotal()) - $spent_inv);

      // In the rare case the limit exceeds ideal conditions, the limit is
      // lowered. Then return the limit or zero when it turned negative.
      if ($limit > $this->getIdealConditionsLimit()) {
        return (int) $this->getIdealConditionsLimit();
      }
      return (int) (($limit < 0) ? 0 : $limit);
    };

    // Fetch calculations from cache or generate new. We use the number of spent
    // invalidations as cache key, since this makes it change every time.
    $spent_inv = $this->spentInvalidations()->get();
    if (!isset($this->remainingInvalidationsLimits[$spent_inv])) {
      $this->remainingInvalidationsLimits[$spent_inv] = $calculate($spent_inv);
    }
    return $this->remainingInvalidationsLimits[$spent_inv];
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHint($purger_instance_id) {
    $this->gatherTimeHints();
    if (!isset($this->timeHints[$purger_instance_id])) {
      throw new BadBehaviorException("Instance id '$purger_instance_id' does not exist!");
    }
    return $this->timeHints[$purger_instance_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHintTotal() {
    if (is_null($this->timeHintTotal)) {
      $this->gatherTimeHints();
      $this->timeHintTotal = 1.0;
      if (count($this->timeHints)) {
        $hints_per_type = [];

        // Iterate all hints and group the values by invalidation type.
        foreach ($this->timeHints as $id => $hint) {
          foreach ($this->purgers[$id]->getTypes() as $type) {
            if (!isset($hints_per_type[$type])) {
              $hints_per_type[$type] = 0.0;
            }
            $hints_per_type[$type] = $hints_per_type[$type] + $hint;
          }
        }

        // Find the highest time, so that the system takes the least risk.
        $this->timeHintTotal = max($hints_per_type);
      }
    }
    return $this->timeHintTotal;
  }

  /**
   * {@inheritdoc}
   */
  public function setPurgers(array $purgers) {
    $this->purgers = $purgers;
  }

  /**
   * {@inheritdoc}
   */
  public function spentExecutionTime() {
    if (is_null($this->spentExecutionTime)) {
      $this->spentExecutionTime = new Counter(0);
      $this->spentExecutionTime->disableDecrement();
      $this->spentExecutionTime->disableSet();
    }
    return $this->spentExecutionTime;
  }

  /**
   * {@inheritdoc}
   */
  public function spentInvalidations() {
    if (is_null($this->spentInvalidations)) {
      $this->spentInvalidations = new Counter(0);
      $this->spentInvalidations->disableDecrement();
      $this->spentInvalidations->disableSet();
    }
    return $this->spentInvalidations;
  }

  /**
   * {@inheritdoc}
   */
  public function waitCooldownTime($purger_instance_id) {
    $seconds = $this->getCooldownTime($purger_instance_id);
    if (!($seconds == 0)) {
      $fractions = explode('.', (string) $seconds);
      if (isset($fractions[1])) {
        call_user_func_array('time_nanosleep', $fractions);
      }
      else {
        sleep($seconds);
      }
    }
  }

}
