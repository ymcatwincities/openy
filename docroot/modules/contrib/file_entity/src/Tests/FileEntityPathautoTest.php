<?php

namespace Drupal\file_entity\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\pathauto\Entity\PathautoPattern;

/**
 * Tests Pathauto support.
 *
 * @dependencies pathauto
 *
 * @group file_entity
 */
class FileEntityPathautoTest extends FileEntityTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('pathauto');

  /**
   * Tests Pathauto support.
   */
  public function testPathauto() {
    $pattern = PathautoPattern::create([
      'id' => Unicode::strtolower($this->randomMachineName()),
      'type' => 'canonical_entities:file',
      'pattern' => '/files/[file:name]',
      'weight' => 0,
    ]);
    $pattern->save();

    $file = $this->createFileEntity(['filename' => 'example.png']);

    $path = \Drupal::service('path.alias_storage')->load(array('source' => '/' . $file->urlInfo()->getInternalPath()));
    $this->assertEqual($path['alias'], '/files/examplepng', t('Alias for file found.'));
  }

}
