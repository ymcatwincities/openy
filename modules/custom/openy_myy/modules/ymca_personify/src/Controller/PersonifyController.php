<?php

namespace Drupal\ymca_personify\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\personify_sso\PersonifySso;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Datetime\DrupalDateTime;

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
    if ($destination = \Drupal::request()->query->get('dest')) {
      $options['query']['dest'] = urlencode($destination);
    }
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
   * Account page.
   */
  public function childcareHistoryPage() {
    $redirect_url = Url::fromRoute('ymca_personify.childcare_payment_history_form')->toString();
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
    $request_time = \Drupal::time()->getRequestTime();
    $query = \Drupal::request()->query->all();
    if (isset($query['ct']) && !empty($query['ct'])) {
      $this->config = \Drupal::config('ymca_personify.settings')->getRawData();
      $this->initPersonifySso();

      $decrypted_token = $this->sso->decryptCustomerToken($query['ct']);
      $id = $this->sso->getCustomerIdentifier($decrypted_token);
      if ($token = $this->sso->validateCustomerToken($decrypted_token)) {
        user_cookie_save([
          'personify_authorized' => $token,
          'personify_time' => $request_time,
          'personify_id' => $id
        ]);
        \Drupal::logger('ymca_personify')->info('A user logged in via Personify.');
      }
      else {
        \Drupal::logger('ymca_personify')->warning('An attempt to login with wrong personify token was detected.');
      }
    }

    $redirect_url = Url::fromUri($this->config['url_account'])->toString();
    if (isset($query['dest'])) {
      $redirect_url = urldecode($query['dest']);
    }
    $redirect = new TrustedRedirectResponse($redirect_url);
    $redirect->send();

    exit();
  }

  /**
   * Checks access.
   */
  public static function isLoginedByPersonify() {
    if (
      isset($_COOKIE['Drupal_visitor_personify_authorized']) &&
      isset($_COOKIE['Drupal_visitor_personify_time']) &&
      isset($_COOKIE['Drupal_visitor_personify_id'])
    ) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks access.
   */
  public function access() {
    return AccessResult::allowedIf(static::isLoginedByPersonify());
  }

  /**
   * Returns PDF for specific personify user and parameters.
   */
  public function getChildcarePaymentPdf() {
    $content = $this->getChildcarePaymentPdfContent();
    $settings = [
      'body' => [
        '#theme' => 'ymca_childcare_payment_history__pdf__body',
        '#content' => $content,
      ],
      'footer' => [
        '#theme' => 'ymca_childcare_payment_history__pdf__footer',
      ],
    ];
    \Drupal::service('ymca_personify_pdf_generator')->generatePDF($settings);
  }

  /**
   * Returns content for Childcare Payment History PDF.
   */
  public function getChildcarePaymentPdfContent() {
    $request = \Drupal::service('request_stack')->getCurrentRequest();
    $parameters = $request->query->all();
    $data = \Drupal::service('ymca_personify_childcare_request')->personifyRequest($parameters);
    $content = [
      'total' => 0,
      'logo_url' => drupal_get_path('theme', 'ymca') . '/img/ymca-logo-social.png',
      'childcare_pdf_address_line1' => parent::config('ymca_personify.settings')->get('childcare_pdf_address_line1'),
      'childcare_pdf_address_line2' => parent::config('ymca_personify.settings')->get('childcare_pdf_address_line2'),
      'childcare_pdf_tax_id' => parent::config('ymca_personify.settings')->get('childcare_pdf_tax_id'),
    ];
    if (isset($data['ChildcarePaymentReceipts']['CL_ChildcarePaymentReceipts'])) {
      $receipts = $data['ChildcarePaymentReceipts']['CL_ChildcarePaymentReceipts'];
      if (array_key_exists('BillMasterCustomerId', $data['ChildcarePaymentReceipts']['CL_ChildcarePaymentReceipts'])) {
        $receipts = [$data['ChildcarePaymentReceipts']['CL_ChildcarePaymentReceipts']];
      }
      $content['start_date'] = $parameters['start_date'];
      $content['end_date'] = $parameters['end_date'];
      $content['child'] = $parameters['child'];
      foreach ($receipts as $receipt) {
        // Skip not chosen children, skip receipts with 0.00 Paid Amount.
        if ($receipt['ActualPostedPaidAmount'] == 0.00 || ($parameters['child'] !== 'all' && $parameters['child'] !== $receipt['ShipMasterCustomerId'])) {
          continue;
        }
        $name = str_replace(',', '', $receipt['ShipCustomerLastFirstName']);
        $key = $name . ', ' . $receipt['ShipMasterCustomerId'];
        $date = DrupalDateTime::createFromTimestamp(strtotime($receipt['ReceiptStatusDate']))->format('Y-m-d');
        $content['today_date'] = date('F d, Y');
        $content['customer_info'] = [
          'name' => $receipt['BillCustomerFirstName'] . ' ' . $receipt['BillCustomerLastName'],
          'address_line' => $receipt['AddressLine'],
          'city' => $receipt['City'] . ', ' . $receipt['State'] . ' ' . $receipt['PostalCode'],
          'bill_customer_id' => $receipt['BillMasterCustomerId'],
        ];
        $content['total'] += $receipt['ActualPostedPaidAmount'];
        $content['children'][$key]['name'] = $name;
        $content['children'][$key]['id'] = $receipt['ShipMasterCustomerId'];
        $content['children'][$key]['total'] += $receipt['ActualPostedPaidAmount'];
        $content['children'][$key]['total'] = number_format($content['children'][$key]['total'], 2, '.', '');
        $content['children'][$key]['receipts'][] = [
          'order' => $receipt['OrderAndLineNumber'],
          'description' => $receipt['Description'],
          'date' => $date,
          'amount' => number_format($receipt['ActualPostedPaidAmount'], 2, '.', ''),
        ];
      }
      $content['total'] = number_format($content['total'], 2, '.', '');
    }
    if (!empty($content['children'])) {
      // Sort by date.
      foreach ($content['children'] as $key => $child) {
        usort($child['receipts'], function ($a, $b) {
          return strtotime($a["date"]) - strtotime($b["date"]);
        });
        $content['children'][$key]['receipts'] = $child['receipts'];
      }
    }
    return $content;
  }

}
