<?php

namespace Drupal\Tests\plugin\Unit\Event;

use Drupal\plugin\Event\PluginEvents;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\plugin\Event\PluginEvents
 *
 * @group Plugin
 */
class PluginEventsTest extends UnitTestCase {

  /**
   * Tests constants with event names.
   */
  public function testEventNames() {
    $class = new \ReflectionClass(PluginEvents::class);
    foreach ($class->getConstants() as $event_name) {
      // Make sure that every event name is properly namespaced.
      $this->assertSame(0, strpos($event_name, 'drupal.plugin.'));
    }
  }

}
