<?php

namespace Drupal\scheduler\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LightweightCronController.
 *
 * @package Drupal\scheduler\Controller
 */
class LightweightCronController extends ControllerBase {

  /**
   * Index.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   RedirectResponse.
   */
  public function index() {
    // @TODO: \Drupal calls should be avoided in classes.
    // Replace \Drupal::service with dependency injection?
    \Drupal::service('scheduler.manager')->runLightweightCron();

    return new Response('', 204);
  }

  /**
   * Checks access.
   *
   * @param string $cron_key
   *   The cron key.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access($cron_key) {
    // @TODO: \Drupal calls should be avoided in classes.
    // Replace \Drupal::config with dependency injection?
    $valid_cron_key = \Drupal::config('scheduler.settings')
      ->get('lightweight_cron_access_key');
    return AccessResult::allowedIf($valid_cron_key == $cron_key);
  }

}
