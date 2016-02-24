<?php
/**
 * @file
 * Contains \Drupal\Tests\mailsystem\Unit\AdminFormTest.
 */

namespace Drupal\Tests\mailsystem\Unit {

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\mailsystem\Form\AdminForm;
use Drupal\mailsystem\MailsystemManager;
use Drupal\Tests\UnitTestCase;

/**
 * Test the Administration form from the mailsystem, especially the various collection functions.
 *
 * @group mailsystem
 */
class AdminFormTest extends UnitTestCase {

  /**
   * Stores the configuration factory to test with.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The mail system manager.
   *
   * @var \Drupal\mailsystem\MailsystemManager
   */
  protected $mailManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Create a config mock which does not mock the clear(), set() and get() methods.
    $methods = get_class_methods('Drupal\Core\Config\Config');
    unset($methods[array_search('set', $methods)]);
    unset($methods[array_search('get', $methods)]);
    unset($methods[array_search('clear', $methods)]);
    $config_mock = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->getMock();

    // Create the config factory we use in the submitForm() function.
    $this->configFactory = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
    $this->configFactory->expects($this->any())
      ->method('getEditable')
      ->will($this->returnValue($config_mock));

    // Create a MailsystemManager mock.
    $this->mailManager = $this->getMock('\Drupal\mailsystem\MailsystemManager', array(), array(), '', FALSE);
    $this->mailManager->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnValueMap(array(
        array('mailsystem_test', TRUE, array('label' => 'Test Mail-Plugin')),
        array('mailsystem_demo', TRUE, array('label' => 'Demo Mail-Plugin')),
      )));
    $this->mailManager->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue(array(
        array('id' => 'mailsystem_test', 'label' => 'Test Mail-Plugin'),
        array('id' => 'mailsystem_demo', 'label' => 'Demo Mail-Plugin'),
      )));

    // Create a module handler mock.
    $this->moduleHandler = $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $this->moduleHandler->expects($this->any())
      ->method('getImplementations')
      ->with('mail')
      ->will($this->returnValue(array('mailsystem_test', 'mailsystem_demo')));
    $this->moduleHandler->expects($this->any())
      ->method('moduleExists')
      ->withAnyParameters()
      ->will($this->returnValue(FALSE));

    // Create a theme handler mock.
    $this->themeHandler = $this->getMock('\Drupal\Core\Extension\ThemeHandlerInterface');
    $this->themeHandler->expects($this->any())
      ->method('listInfo')
      ->will($this->returnValue(array(
        'test_theme' => (object) array(
          'status' => 1,
          'info' => array('name' => 'test theme name'),
        ),
        'demo_theme' => (object) array(
          'status' => 1,
          'info' => array('name' => 'test theme name demo'),
        ),
        'inactive_theme' => (object) array(
          'status' => 0,
          'info' => array('name' => 'inactive test theme'),
        ),
      )));

    // Inject a language-manager into \Drupal.
    $this->languageManager = $this->getMock('\Drupal\Core\StringTranslation\TranslationInterface');
    $this->languageManager->expects($this->any())
      ->method('translate')
      ->withAnyParameters()
      ->will($this->returnArgument(0));

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->languageManager);
    \Drupal::setContainer($container);
  }

  /**
   * Calling a protected or private method on an object.
   *
   * @param object $obj
   *   Object to invoke a method on.
   * @param string $fnc
   *   Function name to invoke.
   * @param array $args
   *   Optional array with arguments passing to the method.
   *
   * @return mixed
   *   The method result.
   */
  protected function invokeMethod($obj, $fnc, array $args = array()) {
    $class = new \ReflectionClass($obj);
    $method = $class->getMethod($fnc);
    $method->setAccessible(TRUE);
    return $method->invokeArgs($obj, $args);
  }

  /**
   * Testing if MailPlugin modules are loaded correctly.
   */
  public function testCollectModules() {
    $admin_form = new AdminForm($this->configFactory, $this->mailManager, $this->moduleHandler, $this->themeHandler);
    $list = $this->invokeMethod($admin_form, 'getModulesList');

    $this->assertTrue(array_key_exists('none', $list), 'Option "none" exists');
    $this->assertEquals(3, count($list), 'List holds 3 mock modules');
    $this->assertEquals('Mailsystem_test', $list['mailsystem_test'], 'Uppercase first char as the module name');
  }

  /**
   * Testing if the label from a plugin is returned valid.
   */
  public function testCollectPluginLabels() {
    $admin_form = new AdminForm($this->configFactory, $this->mailManager, $this->moduleHandler, $this->themeHandler);

    $label = $this->invokeMethod($admin_form, 'getPluginLabel', array('mailsystem_test'));
    $this->assertEquals('Test Mail-Plugin', $label, 'Valid label for test plugin');

    $label = $this->invokeMethod($admin_form, 'getPluginLabel', array('mailsystem_demo'));
    $this->assertEquals('Demo Mail-Plugin', $label, 'Valid label for demo plugin');
  }

  /**
   * Testing the collection of all themes.
   */
  public function testCollectThemes() {
    $admin_form = new AdminForm($this->configFactory, $this->mailManager, $this->moduleHandler, $this->themeHandler);
    $list = $this->invokeMethod($admin_form, 'getThemesList');

    $this->assertEquals(4, count($list), 'Four entries in the theme list. Two themes, default and none');
    $this->assertTrue(array_key_exists('default', $list), '"Default" theme key exists');
    $this->assertTrue(array_key_exists('current', $list), '"Current" theme key exists');
    $this->assertEquals('test theme name', $list['test_theme'], 'Test theme name on the right key');
  }

  /**
   * Testing the collection of all sender plugins.
   */
  public function testCollectSenderPlugins() {
    $admin_form = new AdminForm($this->configFactory, $this->mailManager, $this->moduleHandler, $this->themeHandler);

    // Include the "-- Select --" entry.
    $list = $this->invokeMethod($admin_form, 'getSenderPlugins', array(TRUE));
    $this->assertEquals(3, count($list), 'Two plugins and one select entry');
    $this->assertEquals('Test Mail-Plugin', $list['mailsystem_test'], 'The label of the test plugin matches');
    $this->assertEquals('Demo Mail-Plugin', $list['mailsystem_demo'], 'The label of the demo plugin matches');

    // Without the "-- Select --" entry.
    $list = $this->invokeMethod($admin_form, 'getSenderPlugins');
    $this->assertEquals(2, count($list), 'Two plugins and one select entry');
  }

  /**
   * Testing the collection of all formatter plugins.
   */
  public function testCollectFormatterPlugins() {
    $admin_form = new AdminForm($this->configFactory, $this->mailManager, $this->moduleHandler, $this->themeHandler);

    // Include the "-- Select --" entry.
    $list = $this->invokeMethod($admin_form, 'getFormatterPlugins', array(TRUE));
    $this->assertEquals(3, count($list), 'Two plugins and one select entry');
    $this->assertEquals('Test Mail-Plugin', $list['mailsystem_test'], 'The label of the test plugin matches');
    $this->assertEquals('Demo Mail-Plugin', $list['mailsystem_demo'], 'The label of the demo plugin matches');

    // Without the "-- Select --" entry.
    $list = $this->invokeMethod($admin_form, 'getFormatterPlugins');
    $this->assertEquals(2, count($list), 'Two plugins and one select entry');
  }

  /**
   * Testing the form save function of the values.
   *
   * Checking that values are stored correctly in the configuration.
   */
  public function testSaveSettingsForm() {
    $admin_form = new AdminForm($this->configFactory, $this->mailManager, $this->moduleHandler, $this->themeHandler);
    $config = $this->configFactory->getEditable('mailsystem.settings');
    $form = array();

    // Global configuration.
    $form_state = new FormState();
    $form_state->setValues(array(
      'mailsystem' => array(
        'default_formatter' => 'mailsystem_test',
        'default_sender' => 'mailsystem_demo',
        'default_theme' => 'test_theme',
      ),
    ));
    $admin_form->submitForm($form, $form_state);
    $this->assertEquals('mailsystem_test', $config->get('defaults.formatter'), 'Default formatter changed');
    $this->assertEquals('mailsystem_demo', $config->get('defaults.sender'), 'Default sender changed');
    $this->assertEquals('test_theme', $config->get('theme'), 'Default theme changed');

    // Override a custom module setting with no mail key.
    $form_state = new FormState();
    $form_state->setValues(array(
        'custom' => array(
          'custom_module' => 'module_one',
          'custom_module_key' => 'mail_key',
          'custom_formatter' => 'mailsystem_test',
          'custom_sender' => 'mailsystem_demo',
        ),
      )
    );
    $admin_form->submitForm($form, $form_state);
    $base = MailsystemManager::MAILSYSTEM_MODULES_CONFIG . '.module_one.mail_key';
    $this->assertEquals('mailsystem_test', $config->get($base . '.' . MailsystemManager::MAILSYSTEM_TYPE_FORMATTING), 'Module one formatter changed');
    $this->assertEquals('mailsystem_demo', $config->get($base . '.' . MailsystemManager::MAILSYSTEM_TYPE_SENDING), 'Module one sender changed');

    // Override a custom module setting with a mail key and no sender.
    $form_state = new FormState();
    $form_state->setValues(array(
        'custom' => array(
          'custom_module' => 'module_two',
          'custom_module_key' => '',
          'custom_formatter' => 'mailsystem_test',
          'custom_sender' => 'none',
        ),
      )
    );
    $admin_form->submitForm($form, $form_state);
    $base = MailsystemManager::MAILSYSTEM_MODULES_CONFIG . '.module_two.none';
    $this->assertEquals('mailsystem_test', $config->get($base . '.' . MailsystemManager::MAILSYSTEM_TYPE_FORMATTING), 'Module two with no key formatter changed');
    $this->assertEquals(NULL, $config->get($base . '.' . MailsystemManager::MAILSYSTEM_TYPE_SENDING), 'Module two with no key sender changed to nothing');

    // Add a custom module setting with a mail key and no sender.
    $form_state = new FormState();
    $form_state->setValues(array(
        'custom' => array(
          'custom_module' => 'module_three',
          'custom_module_key' => 'mail_key',
          'custom_formatter' => 'none',
          'custom_sender' => 'mailsystem_test',
        ),
      )
    );
    $admin_form->submitForm($form, $form_state);
    $base = MailsystemManager::MAILSYSTEM_MODULES_CONFIG . '.module_three.mail_key';
    $this->assertEquals(NULL, $config->get($base . '.' . MailsystemManager::MAILSYSTEM_TYPE_FORMATTING), 'Module three no formatter added');
    $this->assertEquals('mailsystem_test', $config->get($base . '.' . MailsystemManager::MAILSYSTEM_TYPE_SENDING), 'Module three sender added');

    // Clear the configuration for some modules.
    $form_state = new FormState();
    $form_state->setValues(array(
        'custom' => array(
          'modules' => array(
            'module_two' => 'module_two',
            'module_one' => 'not_clean',
          ),
        ),
      )
    );
    $admin_form->submitForm($form, $form_state);
    $this->assertEquals('mailsystem_test', $config->get(MailsystemManager::MAILSYSTEM_MODULES_CONFIG . '.module_three.mail_key.' . MailsystemManager::MAILSYSTEM_TYPE_SENDING), 'After clean, module three exists');
    $this->assertEquals('mailsystem_demo', $config->get(MailsystemManager::MAILSYSTEM_MODULES_CONFIG . '.module_one.mail_key.' . MailsystemManager::MAILSYSTEM_TYPE_SENDING), 'After clean, module one exists');
    $this->assertEquals(NULL, $config->get(MailsystemManager::MAILSYSTEM_MODULES_CONFIG . '.module_two'), 'After clean, module two does not exists');
  }

}

}

namespace {
  if (!function_exists('drupal_set_message')) {
    function drupal_set_message() {}
  }
}
