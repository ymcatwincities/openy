<?php

namespace Drupal\rabbit_hole\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\rabbit_hole\Entity\BehaviorSettings;
use Drupal\rabbit_hole\Exception\InvalidBehaviorSettingException;

/**
 * Test the functionality of the rabbit hole form additions to the node form.
 *
 * @group rabbit_hole
 *
 * TODO: Test that creating an entity with an invalid redirect code fails.
 * TODO: Test that creating an entity with redirect settings when the action
 * type is not redirect fails.
 *
 * Note: Currently config entity constructors don't use setters - see
 * https://www.drupal.org/node/2399999.
 */
class RabbitHoleBehaviorSettingsEntityMethodsTest extends WebTestBase {
  public static $modules = array('rabbit_hole');

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->configFactory = $this->container->get('config.factory');
  }

  /**
   * Test creating a BehaviorSettings entity and loading it as config.
   */
  public function testCreateEntity() {
    $action = 'page_not_found';
    $redirect_code = BehaviorSettings::REDIRECT_NOT_APPLICABLE;
    $redirect = '/';
    $allow_override = BehaviorSettings::OVERRIDE_ALLOW;

    $entity = BehaviorSettings::create(
      array(
        'id' => 'test_behavior_settings',
        'action' => $action,
        'allow_override' => $allow_override,
        'redirect_code' => $redirect_code,
        'redirect' => $redirect,
      )
    );
    $entity->save();
    $config_entity = $this->configFactory
      ->get('rabbit_hole.behavior_settings.test_behavior_settings');

    $this->assertEqual($action, $config_entity->get('action'));
    $this->assertEqual($redirect_code, $config_entity->get('redirect_code'));
    $this->assertEqual($redirect, $config_entity->get('redirect'));
    $this->assertEqual($allow_override, $config_entity->get('allow_override'));
  }

  /**
   * Test that setAction() works as expected.
   */
  public function testSetAction() {
    $entity = $this->createGenericTestEntity();
    $action = 'page_not_found';
    $entity->setAction($action);
    $this->assertTrue($action, $entity->getAction());
  }

  /**
   * Test that setAllowOverride works as expected.
   *
   * Test that setAllowOverride works as expected (including throwing an
   * exception if an invalid value is passed).
   */
  public function testSetAllowOverride() {
    $entity = $this->createGenericTestEntity();

    $this->testBehaviorSettingExceptionThrown($entity,
      'setAllowOverride', array('some non-bool value'), __METHOD__);
    $entity->setAllowOverride(TRUE);
    $this->assertEqual(TRUE, $entity->getAllowOverride());
    $entity->setAllowOverride(FALSE);
    $this->assertEqual(FALSE, $entity->getAllowOverride());
  }

  /**
   * Test that setRedirectCode works as expected.
   *
   * Test that setRedirectCode works as expected (including throwing an
   * exception for invalid codes and settings codes when action type is wrong).
   */
  public function testSetRedirectCode() {
    $entity = $this->createGenericTestEntity();

    $entity->setAction('display_page');
    $this->testBehaviorSettingExceptionThrown($entity, 'setRedirectCode',
      array(BehaviorSettings::REDIRECT_FOUND), __METHOD__);

    $entity->setAction('redirect');

    $this->testBehaviorSettingExceptionThrown($entity, 'setRedirectCode',
      array(209458253), __METHOD__);

    $entity->setRedirectCode(BehaviorSettings::REDIRECT_FOUND);
    $this->assertEqual(BehaviorSettings::REDIRECT_FOUND,
      $entity->getRedirectCode());

    $entity->setRedirectCode(BehaviorSettings::REDIRECT_MOVED_PERMANENTLY);
    $this->assertEqual(BehaviorSettings::REDIRECT_MOVED_PERMANENTLY,
      $entity->getRedirectCode());
  }

  /**
   * Test that setRedirectPath works as expected.
   *
   * Test that setRedirectPath works as expected (including throwing an
   * exception for invalid codes and settings codes when action type is wrong).
   */
  public function testSetRedirectPath() {
    $entity = $this->createGenericTestEntity();

    $entity->setAction('display_page');
    $this->testBehaviorSettingExceptionThrown($entity, 'setRedirectPath',
      array('/'), __METHOD__);

    $entity->setAction('redirect');
    $path = '/somepage';
    $entity->setRedirectPath($path);
    $this->assertEqual($path, $entity->getRedirectPath());
  }

  /**
   * Create a generic test BehaviorSettings entity.
   */
  private function createGenericTestEntity() {
    return BehaviorSettings::create(
      array(
        'id' => 'test_behavior_settings',
        'action' => 'access_denied',
        'redirect_code' => BehaviorSettings::REDIRECT_NOT_APPLICABLE,
        'redirect' => NULL,
      )
    );
  }

  /**
   * Test that BehaviorSettingExceptions are thrown when we expect them to.
   *
   * Test that a BehaviorSettingException gets thrown when $entity executes
   * $method with $args. This uses call_user_func internally.
   *
   * @param BehaviorSettings $entity
   *   The BehaviorSettings entity.
   * @param string $method
   *   The method to call.
   * @param array $args
   *   The arguments to pass to the method.
   * @param string $parent
   *   The name of the method which calls this method.
   */
  private function testBehaviorSettingExceptionThrown(BehaviorSettings $entity, $method, array $args, $parent) {
    $exception_was_thrown = FALSE;
    try {
      call_user_func(array($entity, $method), $args);
    }
    catch (InvalidBehaviorSettingException $ex) {
      $exception_was_thrown = TRUE;
    }
    $this->assert($exception_was_thrown, 'Exception thrown executing '
      . $method . ', called from ' . $parent);
  }

}
