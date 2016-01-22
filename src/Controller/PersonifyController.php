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
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class PersonifyController.
 */
class PersonifyController extends ControllerBase {

  /**
   * PersonifySso instance.
   *
   * @var PersonifySso
   */
  private $sso = NULL;

  /**
   * Config.
   *
   * @var array
   */
  private $config = [];

  /**
   * Initialize PersonifySso object.
   */
  private function initPersonifySso() {
    $this->config = \Drupal::config('ymca_personify.settings')->getRawData();
    if (is_null($this->sso)) {
      $this->sso = new PersonifySso(
        $this->config['wsdl'],
        $this->config['vendor_id'],
        $this->config['vendor_username'],
        $this->config['vendor_password'],
        $this->config['vendor_block']
      );
    }
  }

  /**
   * Show the page.
   */
  public function loginPage() {
    $this->initPersonifySso();

    $options = ['absolute' => TRUE];
    $url = Url::fromRoute('ymca_personify.personify_auth', [], $options)->toString();

    $vendor_token = $this->sso->getVendorToken($url);
    $options = [
      'query' => [
        'vi' => $this->config['vendor_id'],
        'vt' => $vendor_token,
      ],
    ];

    $redirect_url = Url::fromUri($this->config['url_login'], $options)->toString();
    $redirect = new TrustedRedirectResponse($redirect_url);
    $redirect->send();

    return [
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Account page.
   */
  public function accountPage() {
    $redirect_url = Url::fromUri($this->config['url_account'])->toString();
    $redirect = new TrustedRedirectResponse($redirect_url);
    $redirect->send();
    return [
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * SignOut page.
   */
  public function signOutPage() {
    unset($_SESSION['personify_token']);
    user_cookie_delete('personify_authorized');

    $redirect_url = Url::fromUri($this->config['url_sign_out'])->toString();
    $redirect = new TrustedRedirectResponse($redirect_url);
    $redirect->send();

    return [
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Auth page.
   */
  public function authPage() {
    $query = \Drupal::request()->query->all();
    if (isset($query['ct']) && !empty($query['ct'])) {
      $this->initPersonifySso();
      $decrypted_token = $this->sso->decryptCustomerToken($query['ct']);
      if ($token = $this->sso->validateCustomerToken($decrypted_token)) {
        user_cookie_save(['personify_authorized' => TRUE]);
        $session = \Drupal::service('session_manager');
        $_SESSION['personify_token'] = $token;
        $session->start();
        \Drupal::logger('ymca_personify')->info('A user logged in via Personify.');
      }
      else {
        \Drupal::logger('ymca_personify')->warning('An attempt to login with wrong personify token was detected.');
      }
    }
    $redirect = new RedirectResponse(Url::fromRoute('<front>')->toString());
    $redirect->send();
    return [
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
