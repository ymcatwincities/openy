<?php
/**
 * @file
 * Contains \Drupal\mailsystem\Tests\MailsystemTestThemeTest.
 */

namespace Drupal\mailsystem\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests mail theme for formatting emails using a theme template.
 *
 * @group mailsystem
 */
class MailsystemTestThemeTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'mailsystem',
    'mailsystem_test',
  ];

  /**
   * The Mailsystem settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  public function setUp() {
    parent::setUp();
    $this->config = $this->config('mailsystem.settings');


  }

  /**
   * Tests the mail theme.
   */
  public function testMailTheme() {

    // Mail System uses its own configuration for the used mail plugins.
    // Use the mail collector just like WebTestBase::initConfig().
    $this->config
      ->set('defaults.sender', 'test_mail_collector')
      ->set('defaults.formatter', 'test_mail_collector')
      ->save();

    // Send an email with the default setting (should NOT use the test theme).
    $this->drupalGet('/mailsystem-test/theme');
    $mails = $this->drupalGetMails();

    // Check the configuration and if the correct theme was used in mails.
    $this->assertEqual($this->config->get('theme'), 'current');
    $this->assertTrue(strpos($mails[0]['body'], 'Anonymous (not verified)') !== FALSE);

    // Install the test theme and set it as the mail theme.
    \Drupal::service('theme_handler')->install(array('mailsystem_test_theme'));
    $this->config->set('theme', 'mailsystem_test_theme')->save();

    // Send another email (now it should use the test theme).
    $this->drupalGet('/mailsystem-test/theme');
    $mails = $this->drupalGetMails();

    // Check the new configuration and ensure that our test theme and its
    // implementation of the username template are used in mails.
    $this->assertEqual($this->config->get('theme'), 'mailsystem_test_theme');
    $this->assertTrue(strpos($mails[1]['body'], 'Mailsystem test theme') !== FALSE);
  }

}
