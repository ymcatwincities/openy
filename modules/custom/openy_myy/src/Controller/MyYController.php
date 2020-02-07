<?php

namespace Drupal\openy_myy\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\openy_myy\PluginManager\MyYDataProfile;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\openy_myy\PluginManager\MyYAuthenticator;

/**
 * {@inheritdoc}
 */
class MyYController extends ControllerBase {

  /**
   * @var \Drupal\openy_myy\PluginManager\MyYDataProfile
   */
  protected $myYDataProfile;

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
   * @param \Drupal\openy_myy\PluginManager\MyYDataProfile $myYDataProfile
   * @param \Drupal\openy_myy\PluginManager\MyYAuthenticator $myy_authenticator_manager
   */
  public function __construct(
    MyYAuthenticator $myy_authenticator_manager,
    MyYDataProfile $myYDataProfile,
    ConfigFactoryInterface $configFactory
  ) {
    $this->myy_authenticator_manager = $myy_authenticator_manager;
    $this->myYDataProfile = $myYDataProfile;
    $this->config = $configFactory->get('openy_myy.settings')->getRawData();

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.myy_authenticator'),
      $container->get('plugin.manager.myy_data_profile'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function myy(Request $request) {

    $myy_authenticator_instances = $this->myy_authenticator_manager->getDefinitions();

    $userID = 0;

    if (array_key_exists($this->config['myy_authenticator'], $myy_authenticator_instances)) {
      $userID = $this
        ->myy_authenticator_manager
        ->createInstance($this->config['myy_authenticator'])
        ->getUserId();
      $familyInfo = $this
        ->myYDataProfile
        ->createInstance($this->config['myy_data_profile'])
        ->getFamilyInfo();
    }

    // Redirect to login page if user is not authenticated.
    if (empty($userID)) {
      return new RedirectResponse(Url::fromRoute('openy_myy.login')->toString());
    }

    $colors = [];
    if (!empty($familyInfo['household'])) {
      foreach ($familyInfo['household'] as $item) {
        $colors[$item['name']] = $item['color'];
      }
    }

    return [
      '#theme' => 'openy_myy',
      '#attached' => [
        'drupalSettings' => [
          'myy' => [
            'uid' => $userID,
            'householdColors' => $colors,
            'childcare_purchase_link_title' => $this->config['childcare_purchase_link_title'],
            'childcare_purchase_link_url' => $this->config['childcare_purchase_link_url'],
          ]
        ]
      ]
    ];
  }

}
