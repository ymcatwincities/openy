<?php

/**
 * @file
 * Contains \Drupal\acquia_purge\HostingInfo.
 */

namespace Drupal\acquia_purge;

use Drupal\Core\Site\Settings;
use Drupal\acquia_purge\HostingInfoInterface;

/**
 * Provides technical information accessors for the Acquia Cloud environment.
 */
class HostingInfo implements HostingInfoInterface {

  /**
   * Name of the PHP function that's available when on Acquia Cloud.
   *
   * @var string
   */
  const AH_INFO_FUNCTION = 'ah_site_info_keyed';

  /**
   * The load balancer IP adresses installed in front of this site.
   *
   * @var string[]
   */
  protected $balancerAddresses = [];

  /**
   * The token used to authenticate cache invalidations with
   *
   * @var string
   */
  protected $balancerToken = '';

  /**
   * The Acquia site environment.
   *
   * @var string
   */
  protected $siteEnvironment = '';

  /**
   * The Acquia site group.
   *
   * @var string
   */
  protected $siteGroup = '';

  /**
   * The Acquia site name.
   *
   * @var string
   */
  protected $siteName = '';

  /**
   * Whether the current hosting environment is Acquia Cloud or not.
   *
   * @var bool
   */
  protected $isThisAcquiaCloud = FALSE;

  /**
   * Constructs a HostingInfo object.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   Drupal site settings object.
   */
  public function __construct(Settings $settings) {

    // Take the IP addresses from the 'reverse_proxies' setting.
    if (is_array($reverse_proxies = $settings->get('reverse_proxies'))) {
      foreach ($reverse_proxies as $reverse_proxy) {
        if ($reverse_proxy && strpos($reverse_proxy, '.')) {
          $this->balancerAddresses[] = $reverse_proxy;
        }
      }
    }

    // Call the AH_INFO_FUNCTION and take the keys 'sitename' and 'sitegroup'.
    $function = SELF::AH_INFO_FUNCTION;
    if (function_exists($function)) {
      if (is_array($info = $function())) {
        if (isset($info['environment'])) {
          if (is_string($info['environment']) && $info['environment']) {
            $this->siteEnvironment = $info['environment'];
          }
        }
        if (isset($info['sitename'])) {
          if (is_string($info['sitename']) && $info['sitename']) {
            $this->siteName = $info['sitename'];
          }
        }
        if (isset($info['sitegroup'])) {
          if (is_string($info['sitegroup']) && $info['sitegroup']) {
            $this->siteGroup = $info['sitegroup'];
          }
        }
      }
    }

    // Determine the authentication token is going to be, usually the site name.
    $this->balancerToken = $this->siteName;
    if (is_string($token = $settings->get('acquia_purge_token'))) {
      if ($token) {
        $this->balancerToken = $token;
      }
    }

    // Test the gathered information to determine if this is/isn't Acquia Cloud.
    $this->isThisAcquiaCloud =
      count($this->balancerAddresses)
      && $this->balancerToken
      && $this->siteEnvironment
      && $this->siteName
      && $this->siteGroup
      && function_exists('curl_init');
  }

  /**
   * {@inheritdoc}
   */
  public function getBalancerAddresses() {
    return $this->balancerAddresses;
  }

  /**
   * {@inheritdoc}
   */
  public function getBalancerToken() {
    return $this->balancerToken;
  }

  /**
  * {@inheritdoc}
  */
  public function getSiteEnvironment() {
    return $this->siteEnvironment;
  }

  /**
  * {@inheritdoc}
  */
  public function getSiteGroup() {
    return $this->siteGroup;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteName() {
    return $this->siteName;
  }

  /**
   * {@inheritdoc}
   */
  public function isThisAcquiaCloud() {
    return $this->isThisAcquiaCloud;
  }

}
