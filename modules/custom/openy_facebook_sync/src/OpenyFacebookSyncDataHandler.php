<?php

namespace Drupal\openy_facebook_sync;

use Facebook\PersistentData\PersistentDataInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Variables are written to and read from session via this class.
 *
 * By default, Facebook SDK uses native PHP sessions for storing data. We
 * implement Facebook\PersistentData\PersistentDataInterface using Symfony
 * Sessions so that Facebook SDK will use that instead of native PHP sessions.
 * Also the module reads data from and writes data to session via this
 * class.
 *
 * @see https://developers.facebook.com/docs/php/PersistentDataInterface/5.0.0
 * @see http://cgit.drupalcode.org/simple_fb_connect/tree/src/SimpleFbConnectPersistentDataHandler.php
 */
class OpenyFacebookSyncDataHandler implements PersistentDataInterface {

  protected $session;
  protected $sessionPrefix = 'openy_facebook_sync_';

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   Used for reading data from and writing data to session.
   */
  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    return $this->session->get($this->sessionPrefix . $key);
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->session->set($this->sessionPrefix . $key, $value);
  }

}
