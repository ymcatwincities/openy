<?php
/**
 * @file
 * Personify Sso class.
 */

namespace Drupal\personify_sso;

/**
 * Class PersonifySsO
 * @package Drupal\personify_sso.
 */
class PersonifySso {

  /**
   * WSDL link.
   *
   * @var string
   */
  private $wsdl = NULL;

  /**
   * Vendor ID.
   *
   * @var int
   */
  private $vendor_id = NULL;

  /**
   * Vendor username.
   * @var string
   */
  private $vendor_username = NULL;

  /**
   * Vendor password.
   * @var string
   */
  private $vendor_password = NULL;

  /**
   * Vendor block.
   * @var string
   */
  private $vendor_block = NULL;

  /**
   * Soap client.
   * @var \SoapClient
   */
  private $client = NULL;

  /**
   * PersonifySSO constructor.
   *
   * @param string $wsdl
   *   WSDL link.
   * @param int $vendor_id
   *   Vendor ID.
   * @param string $vendor_username
   *   Vendor username.
   * @param string $vendor_password
   *   Vendor password.
   * @param string $vendor_block
   *   Vendor block.
   */
  public function __construct($wsdl, $vendor_id, $vendor_username, $vendor_password, $vendor_block) {
    $this->wsdl = $wsdl;
    $this->vendor_id = $vendor_id;
    $this->vendor_username = $vendor_username;
    $this->vendor_password = $vendor_password;
    $this->vendor_block = $vendor_block;

    $this->initClient();
  }

  /**
   * Initialise Soap client.
   */
  private function initClient() {
    $params = [
      'connection_timeout' => 10,
    ];

    try {
      $this->client = new \SoapClient($this->wsdl, $params);
    }
    catch (\Exception $e) {
      watchdog_exception('personify_sso', $e);
      $this->client = NULL;
    }
  }

  /**
   * Get vendor token.
   *
   * @param $url
   *   Absolute Url.
   *
   * @return string|bool
   *   Vendor token.
   */
  public function getVendorToken($url) {
    $params = [
      'vendorUsername' => $this->vendor_username,
      'vendorPassword' => $this->vendor_password,
      'vendorBlock' => $this->vendor_block,
      'url' => $url,
    ];

    try {
      $response = $this->client->VendorTokenEncrypt($params);
      return $response->VendorTokenEncryptResult->VendorToken;
    }
    catch (\Exception $e) {
      watchdog_exception('personify_sso', $e);
    }

    return FALSE;
  }

  /**
   * Validate custom token.
   *
   * @param $token
   *   Customer token.
   *
   * @return string|bool
   *   New customer token.
   */
  public function validateCustomerToken($token) {
    $params = [
      'vendorUsername' => $this->vendor_username,
      'vendorPassword' => $this->vendor_password,
      'customerToken' => $token,
    ];
    try {
      $response = $this->client->SSOCustomerTokenIsValid($params);
      $result = $response->SSOCustomerTokenIsValidResult;
      if (!$result->Valid) {
        return FALSE;
      }
      $new_token = $response->SSOCustomerTokenIsValidResult->NewCustomerToken;
      return $new_token;
    }
    catch (\Exception $e) {
      watchdog_exception('personify_sso', $e);
    }

    return FALSE;
  }

  /**
   * Decrypt customer Token.
   *
   * @param $token
   *   Encrypted customer token.
   *
   * @return string|bool
   *   Decrypted customer token.
   */
  public function decryptCustomerToken($token) {
    $params = [
      'vendorUsername' => $this->vendor_username,
      'vendorPassword' => $this->vendor_password,
      'vendorBlock' => $this->vendor_block,
      'customerToken' => $token,
    ];

    try {
      $response = $this->client->CustomerTokenDecrypt($params);
      $decrypted_token = $response->CustomerTokenDecryptResult->CustomerToken;
      return $decrypted_token;
    }
    catch (\Exception $e) {
      watchdog_exception('personify_sso', $e);
    }

    return FALSE;
  }

}
