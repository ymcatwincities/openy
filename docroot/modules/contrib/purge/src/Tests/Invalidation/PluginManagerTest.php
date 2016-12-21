<?php

namespace Drupal\purge\Tests\Invalidation;

use Drupal\purge\Tests\KernelServiceTestBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\PluginManager.
 *
 * @group purge
 * @see \Drupal\Core\Plugin\DefaultPluginManager
 */
class PluginManagerTest extends KernelServiceTestBase {
  protected $serviceId = 'plugin.manager.purge.invalidation';

  /**
   * All metadata from \Drupal\purge\Annotation\PurgeInvalidation.
   *
   * @var string[]
   */
  protected $annotationFields = [
    'provider',
    'class',
    'id',
    'label',
    'description',
    'examples',
    'expression_required',
    'expression_can_be_empty',
    'expression_must_be_string',
  ];

  /**
   * All bundled plugins.
   *
   * @var string[]
   */
  protected $plugins = [
    'domain',
    'everything',
    'path',
    'regex',
    'tag',
    'url',
    'wildcardpath',
    'wildcardurl',
  ];

  /**
   * Test if the plugin manager is built as we'd like.
   */
  public function testCodeContract() {
    $this->assertTrue($this->service instanceof PluginManagerInterface);
    $this->assertTrue($this->service instanceof DefaultPluginManager);
    $this->assertTrue($this->service instanceof CachedDiscoveryInterface);
  }

  /**
   * Test the plugins we expect to be available.
   */
  public function testDefinitions() {
    $definitions = $this->service->getDefinitions();
    foreach ($this->plugins as $plugin_id) {
      $this->assertTrue(isset($definitions[$plugin_id]));
    }
    foreach ($definitions as $plugin_id => $md) {
      $this->assertTrue(in_array($plugin_id, $this->plugins));
    }
    foreach ($definitions as $plugin_id => $md) {
      foreach ($md as $field => $value) {
        $this->assertTrue(in_array($field, $this->annotationFields));
      }
      foreach ($this->annotationFields as $field) {
        $this->assertTrue(isset($md[$field]));
      }
    }
  }

}
