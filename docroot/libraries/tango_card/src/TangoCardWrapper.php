<?php

namespace Drupal\tango_card;

use Sourcefuse\TangoCardAppModeInvalidException;
use Sourcefuse\TangoCardBase;
use Sourcefuse\TangoCard;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\tango_card\Entity\Campaign;
use Drupal\tango_card\Entity\Account;

/**
 * Wraps TangoCard SDK object with local configuration.
 */
class TangoCardWrapper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Tango Card configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * A cache backend interface..
   *
   * @var Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Tango Card object.
   *
   * @var \Sourcefuse\TangoCard
   */
  protected $tangoCard;

  /**
   * Tango Card account.
   *
   * @var \Drupal\tango_card\Entity\Account
   */
  protected $account;

  /**
   * Tango Card campaign.
   *
   * @var \Drupal\tango_card\Entity\Campaign
   */
  protected $campaign;

  /**
   * Construct TangoCardWrapper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Tango Card configuration object.
   * @param Drupal\Core\Cache\CacheBackendInterface $cache
   *   A cache backend interface.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ImmutableConfig $config, CacheBackendInterface $cache) {
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config;
    $this->cache = $cache;

    $this->init();
  }

  /**
   * Initialize wrapper values from settings.
   */
  public function init() {
    libraries_load('tango_card');

    $tango_card = new TangoCard($this->config->get('platform_name'), $this->config->get('platform_key'));
    $this->setTangoCard($tango_card);

    if (!$app_mode = $this->config->get('app_mode')) {
      $app_mode = 'sandbox';
    }
    $this->tangoCard->setAppMode($app_mode);

    if (
      ($uid = $this->config->get('account')) &&
      ($account = $this->entityTypeManager->getStorage('tango_card_account')->load($uid))
    ) {
      $this->setAccount($account);
    }

    if (
      ($cid = $this->config->get('campaign')) &&
      ($campaign = $this->entityTypeManager->getStorage('tango_card_campaign')->load($cid))
    ) {
      $this->setCampaign($campaign);
    }
  }

  /**
   * Sets Tango Card object.
   *
   * @param \Sourcefuse\TangoCard $tango_card
   *   The Tango Card object to be wrapped.
   */
  public function setTangoCard(TangoCard $tango_card) {
    $this->tangoCard = $tango_card;
  }

  /**
   * Sets account.
   *
   * @param \Drupal\tango_card\Entity\Account $account
   *   Tango Card account entity.
   */
  public function setAccount(Account $account) {
    $this->account = $account;
  }

  /**
   * Get current account object, if set.
   *
   * @return \Drupal\tango_card\Entity\Account|bool
   *   Tango Card account object, if exists. False otherwise.
   */
  public function getAccount() {
    if (!$this->account) {
      return FALSE;
    }

    return $this->account;
  }

  /**
   * Sets campaign.
   *
   * @param \Drupal\tango_card\Entity\Campaign $campaign
   *   Tango Card campaign entity.
   */
  public function setCampaign(Campaign $campaign) {
    $this->campaign = $campaign;
  }

  /**
   * Get current campaign object, if set.
   *
   * @return \Drupal\tango_card\Entity\Campaign|bool
   *   Tango Card campaign object, if exists. False otherwise.
   */
  public function getCampaign() {
    if (!$this->campaign) {
      return FALSE;
    }

    return $this->campaign;
  }

  /**
   * Requests account creation to Tango Card.
   *
   * @param string $account_id
   *   The Tango Card account ID.
   * @param string $mail
   *   The Tango Card account email.
   *
   * @return object|bool
   *   Account object from Tango Card, if success. False otherwise.
   */
  public function setRemoteAccount($account_id, $mail) {
    $response = $this->tangoCard->createAccount($account_id, $account_id, $mail);
    return !empty($response->success);
  }

  /**
   * Requests account information from Tango Card.
   *
   * @param string $account_id
   *   The account ID.
   *
   * @return object|bool
   *   Account object from Tango Card, if success. False otherwise.
   */
  public function getRemoteAccount($account_id) {
    $response = $this->tangoCard->getAccountInfo($account_id, $account_id);

    if (empty($response->success)) {
      return FALSE;
    }

    return $response->account;
  }

  /**
   * Requests account balance to Tango Card.
   *
   * @return int|bool
   *   Acount balance, if success. False otherwise.
   */
  public function getAccountBalance() {
    if (!$account = $this->getAccount()) {
      return FALSE;
    }

    if (!$account = $this->getRemoteAccount($account->remote_id->value)) {
      return FALSE;
    }

    return $account->available_balance;
  }

  /**
   * Requests order info to Tango Card.
   *
   * @param string $recipient_name
   *   The recipient name.
   * @param string $recipient_email
   *   The recipient email.
   * @param string $sku
   *   The product sku.
   * @param int $amount
   *   Total in cents that defines order value. This parameter should not be
   *   included for products with fixed price.
   *
   * @return object|bool
   *   Detailed order object from Tango Card, if success. False otherwise.
   */
  public function placeOrder($recipient_name, $recipient_email, $sku, $amount = NULL) {
    if (!($account = $this->getAccount()) || !($campaign = $this->getCampaign())) {
      return FALSE;
    }

    foreach (array('from', 'subject', 'message') as $suffix) {
      $property = 'notification_' . $suffix;
      $notification[$suffix] = $campaign->$property->value ? $campaign->$property->value : '';
    }

    $response = $this->tangoCard->placeOrder(
      $account->remote_id->value,
      $account->remote_id->value,
      $campaign->name->value,
      $notification['from'],
      $notification['subject'],
      $notification['message'],
      $sku,
      $amount,
      $recipient_name,
      $recipient_email,
      $campaign->notification_enabled->value
    );

    if (empty($response->success)) {
      return FALSE;
    }

    return $response->order;
  }

  /**
   * Requests order info to Tango Card.
   *
   * @param int $order_id
   *   The order id.
   *
   * @return object|bool
   *   Detailed order object from Tango Card, if success. False otherwise.
   */
  public function getOrderInfo($order_id) {
    $response = $this->tangoCard->getOrderInfo($order_id);

    if (empty($response->success)) {
      return FALSE;
    }

    return $response->order;
  }

  /**
   * Requests orders history to Tango Card.
   *
   * @param int $offset
   *   (optional) Skip a number of initial results.
   * @param int $limit
   *   (optional) Limit number of results.
   * @param int $start_date
   *   (optional) Timestamp to limit results to a start date.
   * @param int $end_date
   *   (optional) Timestamp to limit results to an end date.
   *
   * @return object|bool
   *   Object containing orders objects from Tango Card (and other information),
   *   if success. False otherwise.
   */
  public function getOrderHistory($offset = NULL, $limit = NULL, $start_date = NULL, $end_date = NULL) {
    if (!$account = $this->getAccount()) {
      return FALSE;
    }

    $account_id = $account->remote_id->value;
    $response = $this->tangoCard->getOrderHistory($account_id, $account_id, $offset, $limit, $start_date, $end_date);

    if (empty($response->success)) {
      return FALSE;
    }

    return $response;
  }

  /**
   * Requests rewards list from Tango Card, grouped by brand.
   *
   * @param bool $reset
   *   (optional) Reset rewards cache. Defaults to false.
   *
   * @return array|bool
   *   Array containing rewards objects from Tango Card, grouped by brands, if
   *   success. False otherwise.
   */
  public function listRewards($reset = FALSE) {
    $brands = &drupal_static(__FUNCTION__);

    if (!isset($brands)) {
      $cid = 'tango_card:catalog:brands';

      if ($cache = $this->cache->get($cid)) {
        $brands = $cache->data;
      }
      else {
        if (empty($this->tangoCard)) {
          return FALSE;
        }

        $response = $this->tangoCard->listRewards();

        if (empty($response->success)) {
          return FALSE;
        }

        $brands = $response->brands;
        $this->cache->set($cid, $brands, CacheBackendInterface::CACHE_PERMANENT, array('tango_card'));
      }
    }

    return $brands;
  }

  /**
   * Requests rewards list from Tango Card, keyed by SKU.
   *
   * @param bool $reset
   *   (optional) Reset rewards cache. Defaults to false.
   *
   * @return array|bool
   *   Array containing rewards objects from Tango Card, if success. False
   *   otherwise.
   */
  public function listRewardsKeyed($reset = FALSE) {
    $rewards = &drupal_static(__FUNCTION__);

    if (!isset($rewards)) {
      $cid = 'tango_card:catalog:keyed';

      if (!$reset && ($cache = $this->cache->get($cid))) {
        $rewards = $cache->data;
      }
      else {
        if (!$brands = $this->listRewards()) {
          return FALSE;
        }

        $rewards = array();
        foreach ($brands as $brand) {
          foreach ($brand->rewards as $reward) {
            $rewards[$reward->sku] = $reward;
          }
        }

        $this->cache->set($cid, $rewards, CacheBackendInterface::CACHE_PERMANENT, array('tango_card'));
      }
    }

    return $rewards;
  }

  /**
   * Requests a reward info from Tango Card.
   *
   * @param string $sku
   *   The product SKU.
   *
   * @return object|bool
   *   Reward object from Tango Card, if success. False otherwise.
   */
  public function getRewardInfo($sku) {
    if (!$rewards = $this->listRewardsKeyed()) {
      return FALSE;
    }

    if (!isset($rewards[$sku])) {
      return FALSE;
    }

    return $rewards[$sku];
  }

  /**
   * Requests a credit card registry on Tango Card account.
   *
   * @param array $cc_info
   *   Credit card info. It should contain the following elements.
   *   - 'number': Credit card number.
   *   - 'cvv': CVV code.
   *   - 'date': Expiration date. Accepted format: Y-m (e.g. 2019-11).
   * @param array $billing_info
   *   Billing information. It should contain the following elements.
   *   - 'f_name': First name.
   *   - 'l_name': Last name
   *   - 'address': Address.
   *   - 'city': City.
   *   - 'state': State.
   *   - 'country': Country code.
   * @param bool $update_token
   *   (optional) Update local account CC token after request. Defaults to true.
   *
   * @return string|bool
   *   Credit card registration token, if success. false otherwise.
   */
  public function registerCreditCard($cc_info, $billing_info, $update_token = TRUE) {
    if (!$account = $this->getAccount()) {
      return FALSE;
    }

    $response = $this->tangoCard->registerCreditCard(
      $account->remote_id->value,
      $account->remote_id->value,
      $cc_info['number'],
      $cc_info['cvv'],
      $cc_info['date'],
      $billing_info['f_name'],
      $billing_info['l_name'],
      $billing_info['address'],
      $billing_info['city'],
      $billing_info['state'],
      $billing_info['zip'],
      $billing_info['country'],
      $account->mail->value
    );

    if (empty($response->success)) {
      return FALSE;
    }

    if ($update_token) {
      $account->set('cc_token', $response->cc_token);
    }

    return $response->cc_token;
  }

  /**
   * Requests an account fund to Tango Card.
   *
   * @param int $amount
   *   The total in cents to be credited.
   * @param int $cc_cvv
   *   Credit card CVV number.
   *
   * @return bool
   *   True if success. False otherwise.
   */
  public function fundAccount($amount, $cc_cvv) {
    if (!$account = $this->getAccount()) {
      return FALSE;
    }

    $account_id = $account->remote_id->value;
    $response = $this->tangoCard->fundAccount($account_id, $account_id, $amount, $account->cc_token->value, $cc_cvv);
    return !empty($response->success);
  }

  /**
   * Requests a credit card removal from Tango Card account.
   *
   * @param bool $update_token
   *   (optional) Update local account CC token after request. Defaults to true.
   *
   * @return bool
   *   True if success. False otherwise.
   */
  public function deleteCreditCard($update_token = TRUE) {
    if (!$account = $this->getAccount()) {
      return FALSE;
    }

    $account_id = $account->remote_id->value;
    $response = $this->tangoCard->deleteCreditCard($account_id, $account_id, $account->cc_token->value);

    if (empty($response->success)) {
      return FALSE;
    }

    if ($update_token) {
      $account->set('cc_token', NULL);
    }

    return TRUE;
  }

}
