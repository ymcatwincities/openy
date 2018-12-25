<?php

namespace Drupal\acquia_connector;

use Drupal\Core\Password\PhpassHashedPassword;

/**
 * Class CryptConnector.
 *
 * Extends secure password hashing functions based on the Portable PHP password
 * hashing framework.
 *
 * @package Drupal\acquia_connector
 */
class CryptConnector extends PhpassHashedPassword {

  public $cryptPass;

  /**
   * Construction method.
   */
  public function __construct($algo, $password, $setting, $extra_md5) {
    $this->algo = $algo;
    $this->password = $password;
    $this->setting = $setting;
    $this->extra_md5 = $extra_md5;
  }

  /**
   * Crypt pass.
   *
   * @return string
   *   Crypt password.
   */
  public function cryptPass() {
    // Server may state that password needs to be hashed with MD5 first.
    if ($this->extra_md5) {
      $this->password = md5($this->password);
    }
    $crypt_pass = $this->crypt($this->algo, $this->password, $this->setting);

    if ($this->extra_md5) {
      $crypt_pass = 'U' . $crypt_pass;
    }

    return $crypt_pass;
  }

  /**
   * Helper function. Calculate sha1 hash.
   *
   * @param string $key
   *   Acquia Subscription Key.
   * @param string $string
   *   String to calculate hash.
   *
   * @return string
   *   Sha1 sgtring.
   */
  public static function acquiaHash($key, $string) {
    return sha1((str_pad($key, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) . pack("H*", sha1((str_pad($key, 64, chr(0x00)) ^ (str_repeat(chr(0x36), 64))) . $string)));
  }

  /**
   * Derive a key for the solr hmac using a salt, id and key.
   *
   * @param string $salt
   *   Salt.
   * @param string $id
   *   Acquia Subscription ID.
   * @param string $key
   *   Acquia Subscription Key.
   *
   * @return string
   *   Derived Key.
   */
  public static function createDerivedKey($salt, $id, $key) {
    $derivation_string = $id . 'solr' . $salt;
    return hash_hmac('sha1', str_pad($derivation_string, 80, $derivation_string), $key);
  }

}
