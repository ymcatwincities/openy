<?php

namespace Drupal\ymca_camp_du_nord\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\personify_sso\PersonifySso;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Class CDNPersonifyController.
 */
class CDNPersonifyController extends ControllerBase {

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
   * Login page.
   */
  public function login() {
    $this->config = \Drupal::config('ymca_camp_du_nord.settings')->getRawData();
    $this->initPersonifySso();
    $query = \Drupal::service('request_stack')->getCurrentRequest()->query->all();
    $options = ['absolute' => TRUE];
    if ($destination = \Drupal::request()->query->get('dest')) {
      $options['query']['dest'] = urlencode($destination);
    }
    if (!empty($query['ids'])) {
      $options['query']['cdn_personify_chosen_ids'] = $query['ids'];
    }
    if (!empty($query['total'])) {
      $options['query']['total'] = $query['total'];
    }
    if (!empty($query['nights'])) {
      $options['query']['nights'] = $query['nights'];
    }
    $url = Url::fromRoute('ymca_camp_du_nord.personify_auth', [], $options)->toString();

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
   * Auth page.
   */
  public function auth() {
    $query = \Drupal::request()->query->all();
    $this->config = \Drupal::config('ymca_camp_du_nord.settings')->getRawData();
    if (isset($query['ct']) && !empty($query['ct'])) {
      $this->initPersonifySso();
      $decrypted_token = $this->sso->decryptCustomerToken($query['ct']);
      $id = $this->sso->getCustomerIdentifier($decrypted_token);
      if ($token = $this->sso->validateCustomerToken($decrypted_token)) {
        user_cookie_save([
          'personify_authorized' => $token,
          'personify_time' => REQUEST_TIME,
          'personify_id' => $id
        ]);
        // Add components into a shopping cart.
        if (!empty($query['cdn_personify_chosen_ids'])) {
          $data = \Drupal::service('ymca_cdn_sync.add_to_cart')->addToCart($id, $query['cdn_personify_chosen_ids']);
        }
        \Drupal::logger('ymca_camp_du_nord')->info('A user logged in via Personify.');
      }
      else {
        \Drupal::logger('ymca_camp_du_nord')->warning('An attempt to login with wrong personify token was detected.');
      }
    }
    $form = \Drupal::formBuilder()->getForm('Drupal\ymca_camp_du_nord\Form\CdnContactForm', $data);
    return [
      'form' => $form,
    ];
  }

  /**
   * Confirmation page.
   */
  public function confirmationPage() {
    $query = \Drupal::request()->query->all();
    return [
      '#theme' => 'cdn_confirmation_page',
      '#data' => $query,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
