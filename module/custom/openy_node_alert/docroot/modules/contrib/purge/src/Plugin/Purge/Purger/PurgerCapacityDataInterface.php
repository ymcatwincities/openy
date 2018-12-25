<?php

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\purge\Plugin\Purge\Purger\RuntimeMeasurementInterface;

/**
 * Describes what capacity tracking expects from purger implementations.
 */
interface PurgerCapacityDataInterface {

  /**
   * Get the time in seconds to wait after invalidation.
   *
   * The value is expressed as float between 0.0 and 3.0. After ::invalidate()
   * finished, the system will automatically wait this time to allow the caching
   * platform in front of Drupal, to catch up (before other purgers kick in).
   *
   * This value adds up to the total time hint of this purger and therefore the
   * higher this value is, the less processing can happen per request. Platforms
   * that clear instantly (e.g.: via a socket) are best off leaving this at 0.0.
   *
   * @see \Drupal\purge\Annotation\PurgePurger::$cooldown_time.
   * @see \Drupal\purge\Plugin\Purge\Purger\CapacityTrackerInterface::getCooldownTime()
   *
   * @return float
   *   The maximum number of seconds - as a float - to wait after invalidation.
   */
  public function getCooldownTime();

  /**
   * Get the maximum number of invalidations that this purger can process.
   *
   * When Drupal requests are served through a webserver, several resource
   * limitations - such as maximum execution time - affect how much objects are
   * given to your purger plugin. However, under certain conditions - such as
   * when ran through the command line - these limitations aren't in place. This
   * is the 'ideal conditions' scenario under which your purger can operate.
   *
   * However, we cannot feed the entire queue at once and therefore there will
   * always be a hard outer limit of how many invalidation objects are being
   * processed during Drupal's request lifetime.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\CapacityTrackerInterface::getRemainingInvalidationsLimit()
   *
   * @return int
   *   The number of invalidations you can process under ideal conditions.
   */
  public function getIdealConditionsLimit();

  /**
   * Get the runtime measurement counter.
   *
   * @return null|\Drupal\purge\Plugin\Purge\Purger\RuntimeMeasurementInterface
   *   The runtime measurement counter if set, or NULL otherwise.
   */
  public function getRuntimeMeasurement();

  /**
   * Get the maximum number of seconds, processing a single invalidation takes.
   *
   * Implementations need to return the maximum number of seconds it would take
   * them to process a single invalidation. It is important that this value is
   * not smaller than real code will take to execute, but also not many seconds
   * longer then necessary. If - for instance - your purger relies on external
   * HTTP requests, you can set a reasonable request timeout and also use that
   * as estimate here.
   *
   * General tips:
   *   - The float must stay between 0.1 and 10.00 seconds.
   *   - Always assume that your purger plugin is the only enabled purger.
   *   - Your returning time will be statically cached during request life-time,
   *     so don't return different values during a single request.
   *   - If your purger is able to bundle all invalidations in one action,
   *     try to estimate the smallest fraction of time per object, e.g.: 0.1.
   *   - If your purger executes invalidation in multiple steps (e.g. some CDNs)
   *     that can take many minutes, make sure to keep this value low. Put your
   *     objects into PROCESSING and they return back later to check status
   *     in which you can finalize their status into SUCCEEDED.
   *
   * @warning
   *   Please take implementing this method seriously, as it strongly influences
   *   real-world experiences for end users. Undercutting can result in requests
   *   that time out and too high values can lead to queue-processing not being
   *   able to keep up.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\CapacityTrackerInterface::getTimeHint()
   *
   * @return float
   *   The maximum number of seconds - as a float - it takes you to process.
   */
  public function getTimeHint();

  /**
   * Indicates whether your purger utilizes dynamic runtime measurement.
   *
   * Implementations of this method should simply return TRUE or FALSE but the
   * consequences of it have to be thouroughly understood. Either scenarios and
   * the resulting behavior explained:
   *
   * Dynamic runtime measurement enabled (TRUE):
   *  - A counter for your purger will be injected using ::setRuntimeMeasurement().
   *  - The injected counter will be returned by ::getRuntimeMeasurement().
   *  - RuntimeMeasurementInterface::start() is called before ::invalidate().
   *  - RuntimeMeasurementInterface::stopped() is called after ::invalidate().
   *  - Your ::getTimeHint() implementation is assumed to utilize the latest
   *    runtime measurement by calling RuntimeMeasurementInterface::get().
   *
   * In other words, returning TRUE will give you automatic adaptive performance
   * throthling based on data, gathered by automatically set performance
   * counters. When your purgers execute slow, less will be fed in the next
   * call to ::invalidate(). When it performs much better, it will incrementally
   * increase the amount it feeds, over the several next calls to ::invalidate.
   *
   * Dynamic runtime measurement disabled (FALSE):
   *  - No calls will be made to ::setRuntimeMeasurement().
   *  - No calls will be made to ::getRuntimeMeasurement().
   *  - Your ::getTimeHint() implementation will not use dynamic runtime
   *    tracking and carefully and responsibly define the right time hints.
   *
   * So returning FALSE, makes you responsible for the complex task of defining
   * a realistic time limit in which your code executes at all times. This is so
   * complex, because real-world circumstances (e.g. HTTP delays) can cause
   * sudden drops in productivity, which cannot come at the expense of Drupal's
   * overal stability. This is why Purge rather throttles its own work, than
   * letting things explode in front of the end-user. 
   *
   * @return bool
   *   Whether dynamic runtime measurement is used and should be injected.
   */
  public function hasRuntimeMeasurement();

  /**
   * Inject the runtime measurement counter.
   *
   * @param \Drupal\purge\Plugin\Purge\Purger\RuntimeMeasurementInterface $measurement
   *   The runtime measurement counter.
   *
   * @return void
   */
  public function setRuntimeMeasurement(RuntimeMeasurementInterface $measurement);

}
