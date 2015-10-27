<?php

/**
 * @file
 * Contains \Drupal\migrate_plus\Tests\MigrationGroupTest.
 */

namespace Drupal\migrate_plus\Tests;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate_plus\Entity\MigrationGroupInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Test migration groups.
 *
 * @group migrate_plus
 */
class MigrationGroupTest extends WebTestBase {

  public static $modules = array('migrate_plus');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test that group configuration is properly merged into specific migrations.
   */
  public function testConfigurationMerge() {
    $group_id = 'test_group';

    /** @var MigrationGroupInterface $migration_group */
    $migration_group = entity_create('migration_group', array());
    $migration_group->set('id', $group_id);
    $migration_group->set('shared_configuration', array(
      'migration_tags' => array('Drupal 6'), // In migration, so will be overridden.
      'source' => array(
        'constants' => array(
          'type' => 'image',    // Not in migration, so will be added.
          'cardinality' => '1', // In migration, so will be overridden.
        ),
      ),
      'destination' => array('plugin' => 'field_storage_config'), // Not in migration, so will be added.
    ));
    $migration_group->save();

    /** @var MigrationInterface $migration */
    $migration = entity_create('migration', array(
      'id' => 'specific_migration',
      'load' => [],
      'migration_group' => $group_id,
      'label' => 'Unaffected by the group',
      'migration_tags' => array('Drupal 7'), // Overrides group.
      'destination' => array(),
      'source' => array(),
    ));
    $migration->set('source', array(
      'plugin' => 'empty',        // Not in group, persists.
      'constants' => array(
        'entity_type' => 'user',  // Not in group, persists.
        'cardinality' => '3',     // Overrides group.
      ),
    ));
    $migration->save();

    $expected_config = array(
      'migration_group' => $group_id,
      'label' => 'Unaffected by the group',
      'migration_tags' => array('Drupal 7'),
      'source' => array(
        'plugin' => 'empty',
        'constants' => array(
          'entity_type' => 'user',
          'type' => 'image',
          'cardinality' => '3',
        ),
      ),
      'destination' => array('plugin' => 'field_storage_config'),
    );
    /** @var MigrationInterface $loaded_migration */
    $loaded_migration = entity_load('migration', 'specific_migration', TRUE);
    foreach ($expected_config as $key => $expected_value) {
      $actual_value = $loaded_migration->get($key);
      $this->assertEqual($expected_value, $actual_value);
    }
  }

  /**
   * Test that deleting a group deletes its migrations.
   */
  public function testDelete() {
    /** @var MigrationGroupInterface $migration_group */
    $migration_group = entity_create('migration_group', array());
    $migration_group->set('id', 'test_group');
    $migration_group->save();

    /** @var MigrationInterface $migration */
    $migration = entity_create('migration', [
      'id' => 'specific_migration',
      'migration_group' => 'test_group',
      'migration_tags' => array(),
      'load' => [],
      'destination' => array(),
      'source' => array(),
    ]);
    $migration->save();

    /** @var MigrationGroupInterface $loaded_migration_group */
    $loaded_migration_group = entity_load('migration_group', 'test_group', TRUE);
    $loaded_migration_group->delete();

    /** @var MigrationGroupInterface $loaded_migration */
    $loaded_migration = entity_load('migration', 'specific_migration', TRUE);
    $this->assertNull($loaded_migration);
  }

}
