<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

/**
 * Describes the states invalidations can be in during their lifetime.
 */
interface InvStatesInterface {

  /**
   * Invalidation is new and no processing has been attempted on it yet.
   *
   * @var int
   */
  const FRESH = 0;

  /**
   * Invalidation is actively processing remotely and hasn't yet reached
   * its final state. The invalidation flows back to the queue so that the
   * purger conducting the multi-step invalidation, can put it to FAILED or
   * SUCCEEDED at the next round of queue processing. There is no limit of how
   * many times the same object can be put into this state, but when this is
   * happening for too many times, this can lead to queue congestion.
   *
   * @var int
   */
  const PROCESSING = 1;

  /**
   * The invalidation succeeded.
   *
   * @var int
   */
  const SUCCEEDED = 2;

  /**
   * The invalidation failed and will be offered again later.
   *
   * @var int
   */
  const FAILED = 3;

  /**
   * The type of invalidation isn't supported and will be offered again later.
   *
   * @var int
   */
  const NOT_SUPPORTED = 4;

}
