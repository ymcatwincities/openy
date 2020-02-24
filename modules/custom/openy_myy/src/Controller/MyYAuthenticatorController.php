<?php

namespace Drupal\openy_myy\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\openy_myy\PluginManager\MyYAuthenticator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 */
class MyYAuthenticatorController extends ControllerBase {

  /**
   * @var \Drupal\openy_myy\PluginManager\MyYAuthenticator
   */
  protected $myy_authenticator_manager;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * MyYAuthenticatorController constructor.
   *
   * @param \Drupal\openy_myy\PluginManager\MyYAuthenticator $myy_authenticator_manager
   */
  public function __construct(
    MyYAuthenticator $myy_authenticator_manager,
    ConfigFactoryInterface $configFactory
  ) {
    $this->myy_authenticator_manager = $myy_authenticator_manager;
    $this->config = $configFactory->get('openy_myy.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.myy_authenticator'),
      $container->get('config.factory')
    );
  }

  /**
   * Helper method that create's instance of Authenticator plugin.
   *
   * @param $execution_method
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function createPluginInstance($execution_method) {

    $myy_config = $this->config->getRawData();
    $myy_authenticator_instances = $this->myy_authenticator_manager->getDefinitions();
    if (array_key_exists($myy_config['myy_authenticator'], $myy_authenticator_instances)) {
      return $this
        ->myy_authenticator_manager
        ->createInstance($myy_config['myy_authenticator'])
        ->{$execution_method}();
    } else {
      return [
        '#markup' => 'error'
      ];
    }
  }


  /**
   *
   * MyY login page
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   */
  public function login(Request $request) {
    return $this->createPluginInstance('loginPage');
  }

  /**
   * MyY logout page
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   */
  public function logout(Request $request) {
    return $this->createPluginInstance('logoutPage');
  }

  /**
   * MyY auth page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   */
  public function auth(Request $request) {
    return $this->createPluginInstance('authPage');
  }

  /**
   * MyY check login.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   */
  public function checkLogin(Request $request) {
    return $this->createPluginInstance('checkLogin');
  }

}
