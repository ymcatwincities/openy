<?php
/**
 * @file
 * Contains \Drupal\mailsystem\Tests\AdminFormSettingsTest.
 */

namespace Drupal\mailsystem\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the Administrator Settings UI.
 *
 * @group mailsystem
 */
class AdminFormSettingsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'mailsystem'
  ];

  /**
   * Tests the Administrator Settings UI.
   */
  public function testAdminMailSystemForm() {
    // Unauthorized user should not have access.
    $this->drupalGet('admin/config/system/mailsystem');
    $this->assertResponse(403);

    // Check the overview.
    $user = $this->createUser(['administer_mailsystem']);
    $this->drupalLogin($user);
    $this->drupalGet(t('admin/config/system/mailsystem'));
    $this->assertText(t('Mail System'));

    // Configure the Mail System.
    $this->drupalPostForm(NULL, [
      'mailsystem[default_formatter]' => 'test_mail_collector',
      'mailsystem[default_sender]' => 'test_mail_collector',
      'mailsystem[default_theme]' => 'current',
      'custom[custom_module]' => 'system',
      'custom[custom_module_key]' => 'aaa',
      'custom[custom_formatter]' => 'test_mail_collector',
      'custom[custom_sender]' => 'test_mail_collector',
    ], t('Save configuration'));
    $this->drupalGet(t('admin/config/system/mailsystem'));
    $this->assertText('aaa');

    // Checking the configuration.
    $config = $this->config('mailsystem.settings');
    $this->assertEqual($config->get('theme'), 'current');
    $this->assertEqual($config->get('defaults.formatter'), 'test_mail_collector');
    $this->assertEqual($config->get('defaults.sender'), 'test_mail_collector');
    $this->assertEqual($config->get('modules.system.aaa.formatter'), 'test_mail_collector');
    $this->assertEqual($config->get('modules.system.aaa.sender'), 'test_mail_collector');

  }
}
