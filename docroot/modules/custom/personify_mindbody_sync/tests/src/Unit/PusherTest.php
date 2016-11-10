<?php

namespace Drupal\Tests\personify_mindbody_sync\Unit;

/**
 * Personify to MindBody services tests.
 *
 * @group ymca
 */
class PusherTest extends \PHPUnit_Framework_TestCase {

  /**
   * Backup globals.
   *
   * @var bool
   *
   * @see https://github.com/sebastianbergmann/phpunit/issues/451
   * @see https://github.com/silverstripe/silverstripe-behat-extension/commit/7ef575c961ef8a42646b9a30d5a37ad125290dce
   */
  protected $backupGlobals = FALSE;

  /**
   * Test sending notifications.
   */
  public function testSendNotification() {
    $pusher = \Drupal::service('personify_mindbody_sync.pusher_fast');
    $wrapper = \Drupal::service('personify_mindbody_sync.wrapper');

    $order = $wrapper->mockOrder();
    $pusher->sendNotification($order, 10);
  }

}
