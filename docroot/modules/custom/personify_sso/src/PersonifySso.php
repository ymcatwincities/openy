<?php

namespace Drupal\personify_sso;

/**
 * Class PersonifySsO.
 *
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
  private $vendorId = NULL;

  /**
   * Vendor username.
   *
   * @var string
   */
  private $vendorUsername = NULL;

  /**
   * Vendor password.
   *
   * @var string
   */
  private $vendorPassword = NULL;

  /**
   * Vendor block.
   *
   * @var string
   */
  private $vendorBlock = NULL;

  /**
   * Soap client.
   *
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
    $this->vendorId = $vendor_id;
    $this->vendorUsername = $vendor_username;
    $this->vendorPassword = $vendor_password;
    $this->vendorBlock = $vendor_block;

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
   * @param string $url
   *   Absolute Url.
   *
   * @return string|bool
   *   Vendor token.
   */
  public function getVendorToken($url) {
    $params = [
      'vendorUsername' => $this->vendorUsername,
      'vendorPassword' => $this->vendorPassword,
      'vendorBlock' => $this->vendorBlock,
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
   * @param string $token
   *   Customer token.
   *
   * @return string|bool
   *   New customer token.
   */
  public function validateCustomerToken($token) {
    $params = [
      'vendorUsername' => $this->vendorUsername,
      'vendorPassword' => $this->vendorPassword,
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
   * @param string $token
   *   Encrypted customer token.
   *
   * @return string|bool
   *   Decrypted customer token.
   */
  public function decryptCustomerToken($token) {
    $params = [
      'vendorUsername' => $this->vendorUsername,
      'vendorPassword' => $this->vendorPassword,
      'vendorBlock' => $this->vendorBlock,
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

  /**
   * Get customer info.
   *
   * @param string $token
   *   Encrypted token.
   *
   * @return array|bool
   *   Customer info.
   */
  public function getCustomerInfo($token) {
    $params = [
      'vendorUsername' => $this->vendorUsername,
      'vendorPassword' => $this->vendorPassword,
      'customerToken' => $token,
    ];

    try {
      $response = $this->client->SSOCustomerGetByCustomerToken($params);
      $info = (array) $response->SSOCustomerGetByCustomerTokenResult;
      return $info;
    }
    catch (\Exception $e) {
      watchdog_exception('personify_sso', $e);
    }

    return FALSE;
  }

  /**
   * Get customer identifier.
   *
   * @param string $token
   *   Encrypted token.
   *
   * @return string|bool
   *   Customer identifier.
   */
  public function getCustomerIdentifier($token) {
    $params = [
      'vendorUsername' => $this->vendorUsername,
      'vendorPassword' => $this->vendorPassword,
      'customerToken' => $token,
    ];

    try {
      $response = $this->client->TIMSSCustomerIdentifierGet($params);
      return $response->TIMSSCustomerIdentifierGetResult->CustomerIdentifier;
    }
    catch (\Exception $e) {
      watchdog_exception('personify_sso', $e);
    }

    return FALSE;
  }

}
