<?php

namespace Drupal\myy_personify\Plugin\MyYAuthenticator;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\myy_personify\PersonifyUserHelper;
use Drupal\openy_myy\PluginManager\MyYAuthenticatorInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\personify\PersonifySSO;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Personify instance of Authenticator plugin.
 *
 * @MyYAuthenticator(
 *   id = "myy_personify_authenticator",
 *   label = "MyY Authenticator: Personify",
 *   description = "Authentication logic for Personify integration",
 * )
 */
class PersonifyAuthenticator extends PluginBase implements MyYAuthenticatorInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\personify\PersonifySSO;
   */
  protected $personifySSO;

  /**
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $request;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * @var array
   */
  protected $config;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $personify_config;

  /**
   * @var \Drupal\myy_personify\PersonifyUserHelper
   */
  protected $personifyUserHelper;

  /**
   * PersonifyAuthenticator constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\personify\PersonifySSO $personifySSO
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PersonifySSO $personifySSO,
    ConfigFactoryInterface $configFactory,
    RequestStack $requestStack,
    LoggerChannelFactory $loggerChannelFactory,
    PersonifyUserHelper $personifyUserHelper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->personifySSO = $personifySSO;
    $this->config = $configFactory->get('myy_personify.settings')->getRawData();
    $this->personify_config = $configFactory->get('personify.settings')->getRawData();
    $this->request = $requestStack->getCurrentRequest();
    $this->logger = $loggerChannelFactory->get('personify_authenticator');
    $this->personifyUserHelper = $personifyUserHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('personify.sso_client'),
      $container->get('config.factory'),
      $container->get('request_stack'),
      $container->get('logger.factory'),
      $container->get('myy_personify_user_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getUserId() {
    return $this->personifyUserHelper->personifyGetId();
  }

  /**
   * {@inheritdoc}
   */
  public function loginPage() {

    $options = ['absolute' => TRUE];
    if ($destination = $this->request->query->get('dest')) {
      $options['query']['dest'] = urlencode($destination);
    }

    // Generate URL that would base of validation token.
    $url = Url::fromRoute('openy_myy.account', [], $options)->toString();

    $vendor_token = $this->personifySSO->getVendorToken($url);
    $options = [
      'query' => [
        'vi' => $this->personifySSO->getConfigVendorId(),
        'vt' => $vendor_token,
      ],
    ];

    $redirect_url = Url::fromUri($this->config[$this->personify_config['environment'] .'_url_login'], $options)->toString();
    $redirect = new TrustedRedirectResponse($redirect_url);
    $redirect->send();

    exit();
  }

  /**
   * {@inheritdoc}
   */
  public function authPage() {

    $request_time = \Drupal::time()->getRequestTime();
    $query = $this->request->query->all();
    if (isset($query['ct']) && !empty($query['ct'])) {
      $decrypted_token = $this->personifySSO->decryptCustomerToken($query['ct']);

      if ($token = $this->personifySSO->validateCustomerToken($decrypted_token)) {
        user_cookie_save([
          'personify_authorized' => $token,
          'personify_time' => $request_time,
        ]);

        $this->logger->info('A user logged in via Personify.');

      }
      else {
        $this->logger->warning('An attempt to login with wrong personify token was detected.');
      }
    }

    $redirect_url = Url::fromRoute('openy_myy.myy')->toString();
    if (isset($query['dest'])) {
      $redirect_url = urldecode($query['dest']);
    }

    $redirect = new TrustedRedirectResponse($redirect_url);
    $redirect->send();

  }

  /**
   * {@inheritdoc}
   */
  public function logoutPage() {

    user_cookie_delete('personify_authorized');
    user_cookie_delete('personify_time');

    return new TrustedRedirectResponse('/');

  }

  /**
   * {@inheritdoc}
   */
  public function checkLogin() {
    return new JsonResponse([
      'isLogined' => $this->personifyUserHelper->personifyGetId() ? 1 : 0,
    ]);
  }

}
