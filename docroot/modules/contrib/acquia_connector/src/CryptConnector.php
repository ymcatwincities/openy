<?php

namespace Drupal\acquia_connector;

use Drupal\Core\Password\PhpassHashedPassword;

class CryptConnector extends PhpassHashedPassword {

  public $crypt_pass;

  function __construct($algo, $password, $setting, $extra_md5) {
    $this->algo = $algo;
    $this->password = $password;
    $this->setting = $setting;
    $this->extra_md5 = $extra_md5;
  }

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
   * @param $key
   * @param $string
   * @return string
   */
  static function acquiaHash($key, $string) {
    return sha1((str_pad($key, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) . pack("H*", sha1((str_pad($key, 64, chr(0x00)) ^ (str_repeat(chr(0x36), 64))) . $string)));
  }

  /**
   * Derive a key for the solr hmac using a salt, id and key.
   * @param $salt
   * @param $id
   * @param $key
   * @return string
   */
  static function createDerivedKey($salt, $id, $key) {
    $derivation_string = $id . 'solr' . $salt;
    return hash_hmac('sha1', str_pad($derivation_string, 80, $derivation_string), $key);
  }
}
