<?php

namespace Drupal\optimizely\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the account Id and the Default project.
 *
 * @group Optimizely
 */
class OptimizelyDefaultSettingsTest extends WebTestBase {

  protected $settingsPage = 'admin/config/system/optimizely/settings';
  protected $updateDefaultProjPage = 'admin/config/system/optimizely/add_update/1';

  protected $optimizelyPermission = 'administer optimizely';

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
      'name' => 'Optimizely Default Settings',
      'description' => 'Ensure that project settings work correctly',
      'group' => 'Optimizely',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    parent::setUp();

    $this->privilegedUser = $this->drupalCreateUser([$this->optimizelyPermission]);
  }

  /**
   * Test setting the Optimizely account id.
   *
   * Test enabling & disabling the Default project.
   */
  public function testDefaultSettings() {

    $this->drupalLogin($this->privilegedUser);

    // --- Add the Optimizely account ID.
    $edit = [
      'optimizely_id' => rand(0, 10000),
    ];
    $this->drupalPostForm($this->settingsPage, $edit, t('Submit'));

    // The Default project has project id of 1.
    $optimizely_id =
      \Drupal::database()->query('SELECT project_code FROM {optimizely} WHERE oid = 1')
        ->fetchField();
    $this->assertEqual($optimizely_id, $edit['optimizely_id'],
                        t('<strong>Optimizely ID number added to Default project.</strong>'),
                        'Optimizely');

    // --- Enable the default project.
    $edit = [
      'optimizely_enabled' => 1,
    ];
    $this->drupalPostForm($this->updateDefaultProjPage, $edit, t('Update'));

    $enabled = \Drupal::database()->query('SELECT enabled FROM {optimizely} WHERE oid = 1')->fetchField();
    $this->assertEqual($enabled, $edit['optimizely_enabled'],
                        t('<strong>The Default project was enabled.</strong>'),
                        'Optimizely');

    // --- Disable the default project.
    $edit = [
      'optimizely_enabled' => 0,
    ];
    $this->drupalPostForm($this->updateDefaultProjPage, $edit, t('Update'));

    $enabled = \Drupal::database()->query('SELECT enabled FROM {optimizely} WHERE oid = 1')->fetchField();
    $this->assertEqual($enabled, $edit['optimizely_enabled'],
                        t('<strong>The Default project was disabled.</strong>'),
                        'Optimizely');
  }

}
