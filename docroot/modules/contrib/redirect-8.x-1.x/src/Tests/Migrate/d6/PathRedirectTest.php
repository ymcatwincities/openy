<?php

/**
 * @file
 * Contains \Drupal\redirect\Tests\Migrate\d6\PathRedirectTest.
 */

namespace Drupal\redirect\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\redirect\Entity\Redirect;
use Drupal\migrate\Entity\Migration;


/**
 * Tests the d6_path_redirect source plugin.
 *
 * @group redirect
 */
class PathRedirectTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('redirect','link');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', array('router'));
    $this->installEntitySchema('redirect');
    $this->loadFixture( __DIR__ . '/../../../../tests/fixtures/drupal6.php');
    $template = \Drupal::service('migrate.template_storage')->getTemplateByName('d6_path_redirect');
    $migrations = \Drupal::service('migrate.migration_builder')->createMigrations([$template]);

    foreach ($migrations as $migration) {
      try {
        $migration->save();
      }
      catch (PluginNotFoundException $e) {
        // Migrations requiring modules not enabled will throw an exception.
        // Ignoring this exception is equivalent to placing config in the
        // optional subdirectory - the migrations we require for the test will
        // be successfully saved.
      }
    }

    $this->executeMigration('d6_path_redirect');
  }

  /**
   * Tests the Drupal 6 path redirect to Drupal 8 migration.
   */
  public function testPathRedirect() {

    /** @var Redirect $redirect */
    $redirect = Redirect::load(5);
    $this->assertIdentical(Migration::load('d6_path_redirect')
      ->getIdMap()
      ->lookupDestinationID(array(5)), array($redirect->id()));
    $this->assertIdentical("/test/source/url", $redirect->getSourceUrl());
    $this->assertIdentical("base:test/redirect/url", $redirect->getRedirectUrl()->toUriString());

    $redirect = Redirect::load(7);
    $this->assertIdentical("/test/source/url2", $redirect->getSourceUrl());
    $this->assertIdentical("http://test/external/redirect/url?foo=bar&biz=buz", $redirect->getRedirectUrl()->toUriString());
  }
}
