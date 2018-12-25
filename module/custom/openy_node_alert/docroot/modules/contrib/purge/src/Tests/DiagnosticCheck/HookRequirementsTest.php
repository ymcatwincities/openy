<?php

namespace Drupal\purge\Tests\DiagnosticCheck;

use Drupal\purge\Tests\KernelTestBase;

/**
 * Tests that purge_requirements() passes on our diagnostic checks.
 *
 * @group purge
 */
class HookRequirementsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'purge_check_test',
    'purge_check_error_test',
    'purge_check_warning_test',
  ];

  /**
   * Tests that purge_requirements() passes on our diagnostic checks.
   */
  public function testHookRequirements() {
    $module_handler = \Drupal::service('module_handler');
    $module_handler->loadInclude('purge', 'install');
    $r = $module_handler->invoke('purge', 'requirements', ['runtime']);
    // Assert presence of all DiagnosticCheck plugins we're know off.
    $this->assertTrue(isset($r['capacity']));
    $this->assertTrue(isset($r['processorsavailable']));
    $this->assertTrue(isset($r['queuersavailable']));
    $this->assertTrue(isset($r['purgersavailable']));
    $this->assertTrue(isset($r['memoryqueuewarning']));
    $this->assertTrue(isset($r['alwaysinfo']));
    $this->assertTrue(isset($r['alwaysok']));
    $this->assertTrue(isset($r['alwayserror']));
    $this->assertTrue(isset($r['alwayswarning']));
    // Assert a couple of titles.
    $this->assertEqual('Purge - Always ok', (string) $r['alwaysok']['title']);
    $this->assertEqual('Purge - Always a warning', (string) $r['alwayswarning']['title']);
    $this->assertEqual('Purge - Always an error', (string) $r['alwayserror']['title']);
    // Assert that the descriptions come through.
    $this->assertEqual('This is an ok for testing.', (string) $r['alwaysok']['description']);
    $this->assertEqual('This is a warning for testing.', (string) $r['alwayswarning']['description']);
    $this->assertEqual('This is an error for testing.', (string) $r['alwayserror']['description']);
    // Assert that the severities come through properly.
    $this->assertEqual(0, $r['alwaysok']['severity']);
    $this->assertEqual(1, $r['alwayswarning']['severity']);
    $this->assertEqual(2, $r['alwayserror']['severity']);
    // Assert that the values come through properly.
    $this->assertTrue(is_string($r['capacity']['value']));
    $this->assertEqual("0", $r['capacity']['value']);
    $this->assertEqual("", $r['alwaysinfo']['value']);
    $this->assertEqual("", $r['alwaysok']['value']);
  }

}
