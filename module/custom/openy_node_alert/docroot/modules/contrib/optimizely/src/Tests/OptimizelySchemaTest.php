<?php

namespace Drupal\optimizely\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test schema creation.
 *
 * @group Optimizely
 */
class OptimizelySchemaTest extends WebTestBase {

  protected $privilegedUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['optimizely'];

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => 'Optimizely Schema Creation',
      'description' => 'Ensure schema creation.',
      'group' => 'Optimizely',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    parent::setUp();

    $this->privilegedUser = $this->drupalCreateUser(['administer optimizely']);
  }

  /**
   * Test that module's database table is created.
   */
  public function testSchemaCreation() {
    $this->drupalLogin($this->privilegedUser);

    $schema = \Drupal::moduleHandler()->invoke('optimizely', 'schema');
    $this->assertNotNull($schema, t('<strong>Optimizely table was created.</strong>'), 'Optimizely');
  }

}
