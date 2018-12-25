<?php

namespace Drupal\plugin\Form;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;

/**
 * Provides helpers for building subforms.
 *
 * @internal
 */
trait SubformHelperTrait {

  protected function assertSubformState(FormStateInterface $form_state) {
    if (!($form_state instanceof SubformStateInterface)) {
      $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
      trigger_error(sprintf('%s::%s() SHOULD receive %s on line %d, but %s was given. More information is available at https://www.drupal.org/node/2774077.', $trace[1]['class'], $trace[1]['function'], SubformStateInterface::class, $trace[1]['line'], get_class($form_state)), E_USER_DEPRECATED);
    }
  }

}
