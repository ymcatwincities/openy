<?php

namespace Drupal\Tests\acquia_connector\Unit;

use Drupal\acquia_connector\AutoConnector;
use Drupal\acquia_connector\Helper\Storage;
use Drupal\acquia_connector\Subscription;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\acquia_connector\AutoConnector
 *
 * @group Acquia connector
 */
class AutoConnectorTest extends UnitTestCase {

  /**
   * Tests the happy path:
   *  - when there is not current connection (stored credentials)
   *  - attempt to connect succeeds
   */
  public function testAutoConnect() {

    $subscription_mock = $this->prophesize(Subscription::CLASS);
    $subscription_mock->hasCredentials()->willReturn(FALSE);
    $subscription_mock->update()->willReturn(TRUE);

    $storage_mock = $this->prophesize(Storage::CLASS);

    $config = [
      'ah_network_identifier' => 'WXYZ-12345',
      'ah_network_key' => '12345678901234567890',
    ];

    $auto_connector = new AutoConnector($subscription_mock->reveal(), $storage_mock->reveal(), $config);

    $auto_connected = $auto_connector->connectToAcquia();

    $this->assertTrue($auto_connected);

    $storage_mock->setKey('12345678901234567890')->shouldHaveBeenCalled();
    $storage_mock->setIdentifier('WXYZ-12345')->shouldHaveBeenCalled();
    $subscription_mock->update()->shouldHaveBeenCalled();

  }

  /**
   * Tests the scenario when the site is already connected to Acquia.
   */
  public function testAutoConnectWhenAlreadyConnected() {

    $subscription_mock = $this->prophesize(Subscription::CLASS);
    $subscription_mock->hasCredentials()->willReturn(TRUE);
    $subscription_mock->update()->shouldNotBeCalled();

    $storage_mock = $this->prophesize(Storage::CLASS);
    $storage_mock->setKey()->shouldNotBeCalled();
    $storage_mock->setIdentifier()->shouldNotBeCalled();

    $config = [
      'ah_network_identifier' => 'WXYZ-12345',
      'ah_network_key' => '12345678901234567890',
    ];

    $auto_connector = new AutoConnector($subscription_mock->reveal(), $storage_mock->reveal(), $config);

    $auto_connected = $auto_connector->connectToAcquia();

    $this->assertFalse($auto_connected);

  }

  /**
   * Tests the scenario when the site is not connected but there are no
   * credentials provided by the global config.
   */
  public function testAutoConnectWhenNoCredsInGlobalConfig() {

    $subscription_mock = $this->prophesize(Subscription::CLASS);
    $subscription_mock->hasCredentials()->willReturn(FALSE);
    $subscription_mock->update()->shouldNotBeCalled();

    $storage_mock = $this->prophesize(Storage::CLASS);
    $storage_mock->setKey()->shouldNotBeCalled();
    $storage_mock->setIdentifier()->shouldNotBeCalled();

    $config = [];

    $auto_connector = new AutoConnector($subscription_mock->reveal(), $storage_mock->reveal(), $config);

    $auto_connected = $auto_connector->connectToAcquia();

    $this->assertFalse($auto_connected);

  }

}
