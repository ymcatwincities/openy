<?php
/**
 * @file
 * Contains \Drupal\Tests\mailsystem\Unit\MailsystemManagerTest.
 */

namespace Drupal\Tests\mailsystem\Unit;

use Drupal\Core\Theme\Registry;
use Drupal\Core\Theme\ThemeInitialization;
use Drupal\Core\Theme\ThemeManager;
use Drupal\mailsystem\MailsystemManager;
use Drupal\Tests\UnitTestCase;

/**
 * Test the MailsystemManager to return valid plugin instances based on the configuration.
 *
 * @group mailsystem
 */
class MailsystemManagerTest extends UnitTestCase {
  /**
   * Stores the configuration factory to test with.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The mailsystem manager.
   *
   * @var \Drupal\mailsystem\MailsystemManager
   */
  protected $mailManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Create a configuration mock.
    $this->configFactory = $this->getConfigFactoryStub(array(
      'mailsystem.settings' => array(
        'defaults' => array(
          MailsystemManager::MAILSYSTEM_TYPE_FORMATTING => 'mailsystem_test',
          MailsystemManager::MAILSYSTEM_TYPE_SENDING => 'mailsystem_test',
        ),
        MailsystemManager::MAILSYSTEM_MODULES_CONFIG => array(
          'module1' => array(
            'none' => array(
              MailsystemManager::MAILSYSTEM_TYPE_FORMATTING => 'mailsystem_test',
              MailsystemManager::MAILSYSTEM_TYPE_SENDING => 'mailsystem_test',
            )
          ),
          'module2' => array(
            'mail_key' => array(
              MailsystemManager::MAILSYSTEM_TYPE_FORMATTING => 'mailsystem_test',
              MailsystemManager::MAILSYSTEM_TYPE_SENDING => 'mailsystem_test',
            )
          ),
        ),
      ),
    ));

    $namespaces = $this->getMock('\Traversable');
    $cache_backend = $this->getMock('\Drupal\Core\Cache\CacheBackendInterface');
    $module_handler = $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $logger_factory = $this->getMock('Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $string_translation = $this->getMock('Drupal\Core\StringTranslation\TranslationInterface');

    $theme_manager = $this->prophesize(ThemeManager::class);
    $theme_initialization = $this->prophesize(ThemeInitialization::class);
    $default_theme_registry = $this->prophesize(Registry::class);
    $mail_theme_registry = $this->prophesize(Registry::class);

    $this->mailManager = new MailsystemManager($namespaces, $cache_backend, $module_handler, $this->configFactory, $logger_factory, $string_translation, $theme_manager->reveal(), $theme_initialization->reveal(), $default_theme_registry->reveal(), $mail_theme_registry->reveal());
  }

  public function testGetInstances_Default() {

  }

}
