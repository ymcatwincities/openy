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
    // Check default theme options.
    $this->assertOption('edit-mailsystem-default-theme', 'current');
    $this->assertOption('edit-mailsystem-default-theme', 'default');
    $this->assertOption('edit-mailsystem-default-theme', 'stable');
    $this->assertOption('edit-mailsystem-default-theme', 'classy');
    // Check custom module options labels.
    $xpath = '//*[@id="edit-custom-custom-module"]';
    $this->assertTrue(strpos($this->xpath($xpath)[0]->asXml(), '>- Select -</option>'));
    $this->assertTrue(strpos($this->xpath($xpath)[0]->asXml(), '>System</option>'));
    $this->assertTrue(strpos($this->xpath($xpath)[0]->asXml(), '>User</option>'));

    // Configure the default Mail System.
    $this->drupalPostForm(NULL, [
      'mailsystem[default_formatter]' => 'test_mail_collector',
      'mailsystem[default_sender]' => 'test_mail_collector',
      'mailsystem[default_theme]' => 'current',
    ], t('Save configuration'));

    // Configure a specific module configuration.
    $this->drupalPostForm(NULL, [
      'custom[custom_module]' => 'system',
      'custom[custom_module_key]' => 'aaa',
      'custom[custom_formatter]' => 'test_mail_collector',
      'custom[custom_sender]' => 'test_mail_collector',
    ], t('Add'));
    $this->drupalGet(t('admin/config/system/mailsystem'));
    $this->assertText('aaa');

    // Add additional custom module settings, one with test_mail_collector and
    // one with php_mail.
    $this->drupalPostForm(NULL, [
      'custom[custom_module]' => 'system',
      'custom[custom_module_key]' => 'bbb',
      'custom[custom_formatter]' => 'test_mail_collector',
      'custom[custom_sender]' => 'test_mail_collector',
    ], t('Add'));
    $this->drupalGet(t('admin/config/system/mailsystem'));
    $this->assertText('bbb');

    $this->drupalPostForm(NULL, [
      'custom[custom_module]' => 'system',
      'custom[custom_module_key]' => 'ccc',
      'custom[custom_formatter]' => 'php_mail',
      'custom[custom_sender]' => 'php_mail',
    ], t('Add'));
    $this->drupalGet(t('admin/config/system/mailsystem'));
    $this->assertText('ccc');

    // Add a custom module settings, without specifying any key.
    $this->drupalPostForm(NULL, [
      'custom[custom_module]' => 'system',
      'custom[custom_formatter]' => 'test_mail_collector',
      'custom[custom_sender]' => 'test_mail_collector',
    ], t('Add'));
    $this->assertText('All');
    $this->drupalGet(t('admin/config/system/mailsystem'));

    // Try to add a custom module, first without setting the module, then
    // without formatter nor sender, then just specifying a key.
    $this->drupalPostForm(NULL, [
      'custom[custom_module_key]' => 'ddd',
      'custom[custom_formatter]' => 'test_mail_collector',
      'custom[custom_sender]' => 'test_mail_collector',
    ], t('Add'));
    $this->assertNoText('ddd');
    $this->assertText('The module is required.');
    $this->drupalGet(t('admin/config/system/mailsystem'));

    $this->drupalPostForm(NULL, [
      'custom[custom_module]' => 'system',
      'custom[custom_module_key]' => 'ddd',
    ], t('Add'));
    $this->assertNoText('ddd');
    $this->assertText('At least a formatter or sender is required.');
    $this->drupalGet(t('admin/config/system/mailsystem'));

    $this->drupalPostForm(NULL, [
      'custom[custom_module_key]' => 'ddd',
    ], t('Add'));
    $this->assertNoText('ddd');
    $this->assertText('The module is required.');
    $this->assertText('At least a formatter or sender is required.');
    $this->drupalGet(t('admin/config/system/mailsystem'));

    // Checking the configuration.
    $config = $this->config('mailsystem.settings');
    $this->assertEqual($config->get('theme'), 'current');
    $this->assertEqual($config->get('defaults.formatter'), 'test_mail_collector');
    $this->assertEqual($config->get('defaults.sender'), 'test_mail_collector');
    $this->assertEqual($config->get('modules.system.aaa.formatter'), 'test_mail_collector');
    $this->assertEqual($config->get('modules.system.aaa.sender'), 'test_mail_collector');
    $this->assertEqual($config->get('modules.system.bbb.formatter'), 'test_mail_collector');
    $this->assertEqual($config->get('modules.system.bbb.sender'), 'test_mail_collector');
    $this->assertEqual($config->get('modules.system.ccc.formatter'), 'php_mail');
    $this->assertEqual($config->get('modules.system.ccc.sender'), 'php_mail');
    $this->assertEqual($config->get('modules.system.none.formatter'), 'test_mail_collector');
    $this->assertEqual($config->get('modules.system.none.sender'), 'test_mail_collector');
    $this->assertNull($config->get('modules.system.ddd'));

    // Try to update the formatter of the module keyed as 'ccc' from the form.
    $this->drupalPostForm(NULL, [
      'custom[custom_module_key]' => 'ccc',
      'custom[custom_formatter]' => 'test_mail_collector',
    ], t('Add'));
    $this->assertText('The module is required.');
    $this->drupalGet(t('admin/config/system/mailsystem'));

    // Try to update 'modules.system.ccc' formatter and sender from the form.
    $this->drupalPostForm(NULL, [
      'custom[custom_module]' => 'system',
      'custom[custom_module_key]' => 'ccc',
      'custom[custom_formatter]' => 'test_mail_collector',
      'custom[custom_sender]' => 'test_mail_collector',
    ], t('Add'));
    $this->assertText('An entry for this combination exists already. Use the form below to update or remove it.');
    $this->drupalGet(t('admin/config/system/mailsystem'));

    // Try to add a custom module with the same settings of an existing one,
    // without formatter and sender.
    $this->drupalPostForm(NULL, [
      'custom[custom_module]' => 'system',
      'custom[custom_module_key]' => 'ccc',
    ], t('Add'));
    $this->assertText('An entry for this combination exists already. Use the form below to update or remove it.');
    $this->assertNoText('At least a formatter or sender is required.');
    $this->drupalGet(t('admin/config/system/mailsystem'));

    // Edit the second and third custom module formatter from the table.
    $this->drupalPostForm(NULL, [
      'custom[modules][system.bbb][formatter]' => 'php_mail',
      'custom[modules][system.ccc][formatter]' => 'test_mail_collector',
    ], t('Save configuration'));
    $config->set('modules.system.bbb.formatter', 'php_mail')->save();
    $config->set('modules.system.ccc.formatter', 'test_mail_collector')->save();
    $this->drupalGet(t('admin/config/system/mailsystem'));
    $this->assertEqual($config->get('modules.system.aaa.formatter'), 'test_mail_collector');
    $this->assertEqual($config->get('modules.system.bbb.formatter'), 'php_mail');
    $this->assertEqual($config->get('modules.system.ccc.formatter'), 'test_mail_collector');
    $this->assertEqual($config->get('modules.system.none.formatter'), 'test_mail_collector');

    // Remove the first custom module.
    $this->drupalPostForm(NULL, [
      'custom[modules][system.aaa][remove]' => TRUE,
    ], t('Save configuration'));
    $config->clear('modules.system.aaa')->save();
    $this->drupalGet(t('admin/config/system/mailsystem'));
    $this->assertNull($config->get('modules.system.aaa'));
    $this->assertNotNull($config->get('modules.system.bbb'));
    $this->assertNotNull($config->get('modules.system.ccc'));
    $this->assertNotNull($config->get('modules.system.none'));

  }
}
