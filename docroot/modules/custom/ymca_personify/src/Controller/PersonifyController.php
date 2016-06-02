<?php

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
    $this->config = \Drupal::config('ymca_personify.settings')->getRawData();
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

    exit();
  }

  /**
   * Account page.
   */
  public function accountPage() {
    $this->config = \Drupal::config('ymca_personify.settings')->getRawData();

    $redirect_url = Url::fromUri($this->config['url_account'])->toString();
    $redirect = new TrustedRedirectResponse($redirect_url);
    $redirect->send();

    exit();
  }

  /**
   * SignOut page.
   */
  public function signOutPage() {
    $this->config = \Drupal::config('ymca_personify.settings')->getRawData();

    user_cookie_delete('personify_authorized');
    user_cookie_delete('personify_time');

    $redirect_url = Url::fromUri($this->config['url_sign_out'])->toString();
    $redirect = new TrustedRedirectResponse($redirect_url);
    $redirect->send();

    exit();
  }

  /**
   * Auth page.
   */
  public function authPage() {
    $query = \Drupal::request()->query->all();
    if (isset($query['ct']) && !empty($query['ct'])) {
      $this->config = \Drupal::config('ymca_personify.settings')->getRawData();
      $this->initPersonifySso();

      $decrypted_token = $this->sso->decryptCustomerToken($query['ct']);
      if ($token = $this->sso->validateCustomerToken($decrypted_token)) {
        user_cookie_save([
          'personify_authorized' => $token,
          'personify_time' => REQUEST_TIME,
        ]);
        \Drupal::logger('ymca_personify')->info('A user logged in via Personify.');
      }
      else {
        \Drupal::logger('ymca_personify')->warning('An attempt to login with wrong personify token was detected.');
      }
    }

    $redirect_url = Url::fromUri($this->config['url_account'])->toString();
    $redirect = new TrustedRedirectResponse($redirect_url);
    $redirect->send();

    exit();
  }

  /**
   * POC of customer orders.
   */
  public function customerOrders() {
    $this->config = \Drupal::config('ymca_personify.settings')->getRawData();

    $output = '<div class="container">';
    $client = \Drupal::httpClient();
    $options = [
      'json' => [
        'CL_MindBodyCustomerOrderInput' => [
          'LastDataAccessDate' => '2000-01-01T11:20:00',
        ],
      ],
      'headers' => [
        'Content-Type' => 'application/json;charset=utf-8',
      ],
      'auth' => [
        $this->config['customer_orders_username'],
        $this->config['customer_orders_password'],
      ],
    ];

    try {
      $response = $client->request('POST', $this->config['customer_orders_endpoint'], $options);
      if ($response->getStatusCode() == '200') {
        $body = $response->getBody();
        $data = json_decode($body->getContents());

        foreach ($data->MindBodyCustomerOrderDetail as $order) {
          $output .= '<div><pre>' . print_r($order, TRUE) . '</pre></div>';
        }
      }
    }
    catch (\Exception $e) {
      watchdog_exception('ymca_personify', $e);
    }
    $output .= '</div>';

    return [
      '#markup' => $output,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
