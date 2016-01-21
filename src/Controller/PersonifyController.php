<?php
/**
 * @file
 * Contains \Drupal\ymca_personify\Controller\PersonifyController.
 */

namespace Drupal\ymca_personify\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\personify_sso\PersonifySso;
use Drupal\Core\Url;

/**
 * Class PersonifyController.
 */
class PersonifyController extends ControllerBase {

  /**
   * Show the page.
   */
  public function loginPage() {
    $config = \Drupal::config('ymca_personify.settings')->getRawData();
    $sso = new PersonifySso(
      $config['wsdl'],
      $config['vendor_id'],
      $config['vendor_username'],
      $config['vendor_password'],
      $config['vendor_block']
    );

    $options = [
      'absolute' => TRUE,
      'query' => [
        'personify' => 1,
      ],
    ];
    $url = Url::fromUserInput('/', $options)->toString();

    $vendor_token = $sso->getVendorToken($url);
    $options = [
      'query' => [
        'vi' => $config['vendor_id'],
        'vt' => $vendor_token,
      ],
    ];
    $redirect_url = Url::fromUri($config['url_login'], $options)->toString();
    $redirect = new TrustedRedirectResponse($redirect_url);
    $redirect->send();
  }

  /**
   * Account page.
   */
  public function accountPage() {
    $config = $this->config('ymca_personify.settings');
    $redirect_url = Url::fromUri($config->get('url_account'))->toString();
    $redirect = new TrustedRedirectResponse($redirect_url);
    $redirect->send();
  }

  /**
   * SignOut page.
   */
  public function signOutPage() {
    $config = $this->config('ymca_personify.settings');
    $redirect_url = Url::fromUri($config->get('url_sign_out'))->toString();
    $redirect = new TrustedRedirectResponse($redirect_url);
    $redirect->send();
  }

}
