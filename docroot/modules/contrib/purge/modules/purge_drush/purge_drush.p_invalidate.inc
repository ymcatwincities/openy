<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\MissingExpressionException;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;
use Drupal\purge\Plugin\Purge\Purger\Exception\CapacityException;
use Drupal\purge\Plugin\Purge\Purger\Exception\DiagnosticsException;
use Drupal\purge\Plugin\Purge\Purger\Exception\LockException;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;

/**
 * Directly invalidate an item without going through the queue.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface $purgeInvalidationFactory
 *   The purge invalidation factory service.
 * @param \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface $purgeProcessors
 *   The purge processors service.
 * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purgePurgers
 *   The purge purgers service.
 * @param string $type
 *   The type of invalidation to queue.
 * @param mixed|null $expression
 *   Value - usually string - that describes the kind of invalidation, NULL
 *   when the type of invalidation doesn't require $expression. Types usually
 *   validate the given expression and throw exceptions for bad input.
 *
 * @return array
 *   Drush render/format array.
 */
function purge_drush_p_invalidate($format, InvalidationsServiceInterface $purgeInvalidationFactory, ProcessorsServiceInterface $purgeProcessors, PurgersServiceInterface $purgePurgers, $type, $expression = NULL) {
  $return_exception = function($exception, $message = '') use($format) {
    if ($format === 'string') {
      return drush_set_error('ERROR', $message ? $message : $exception->getMessage());
    }
    return FALSE;
  };

  // Retrieve our queuer object and fail when it is not returned.
  if (!($processor = $purgeProcessors->get('drush_purge_invalidate'))) {
    if (in_array($format, ['yaml', 'json'])) {
      return FALSE;
    }
    return drush_set_error('NOT_AUTHORIZED', dt('Not authorized, processor missing!'));
  }

  // Instantiate the invalidation object based on user input.
  try {
    $invalidations = [$purgeInvalidationFactory->get($type, $expression)];
  }
  catch (PluginNotFoundException $e) {
    return $return_exception($e, dt("Type '@type' does not exist, see 'drush p-types' for available types.", ['@type' => $type]));
  }
  catch (InvalidExpressionException $e) {
    return $return_exception($e);
  }
  catch (MissingExpressionException $e) {
    return $return_exception($e);
  }

  // Attempt the cache invalidation and deal with errors.
  try {
    $purgePurgers->invalidate($processor, $invalidations);
  }
  catch (DiagnosticsException $e) {
    return $return_exception($e);
  }
  catch (CapacityException $e) {
    return $return_exception($e);
  }
  catch (LockException $e) {
    return $return_exception($e);
  }

  // Since this command is more meant for testing, we only regard SUCCEEDED as
  // a acceptable return state to call success on.
  if ($invalidations[0]->getState() === InvStatesInterface::SUCCEEDED) {
    if (in_array($format, ['yaml', 'json'])) {
      return TRUE;
    }
    return dt('Item invalidated successfully!');
  }
  if (in_array($format, ['yaml', 'json'])) {
    return FALSE;
  }
  return dt('Invalidation failed, its return state is: @state.', [
    '@state' => $invalidations[0]->getStateString()
  ]);
}
