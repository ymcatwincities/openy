<?php

namespace Drupal\acquia_purge;

use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\acquia_purge\HostingInfoInterface;
use Drupal\acquia_purge\Hash;

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
  * Whether the current hosting environment is Acquia Cloud or not.
  *
  * @var bool
  */
  protected $isThisAcquiaCloud = FALSE;

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
   * Unique identifier for this site.
   *
   * @var string
   */
  protected $siteIdentifier = '';

  /**
   * The Acquia site name.
   *
   * @var string
   */
  protected $siteName = '';

  /**
   * The Drupal site path.
   *
   * @var string
   */
  protected $sitePath = '';

  /**
   * Constructs a HostingInfo object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Site\Settings $settings
   *   Drupal site settings object.
   */
  public function __construct(RequestStack $request_stack, Settings $settings) {

    // Generate the Drupal sitepath by querying the SitePath from this request.
    $this->sitePath = DrupalKernel::findSitePath($request_stack->getCurrentRequest());

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
      if (is_array($info = $function ())) {
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
    else if(!empty($GLOBALS['gardens_site_settings'])) {
      $this->siteEnvironment = $GLOBALS['gardens_site_settings']['env'];
      $this->siteGroup = $GLOBALS['gardens_site_settings']['site'];
      $this->siteName = $this->siteGroup . '.' . $this->siteEnvironment;
    }

    // Determine the authentication token is going to be, usually the site name.
    $this->balancerToken = $this->siteName;
    if (is_string($token = $settings->get('acquia_purge_token'))) {
      if ($token) {
        $this->balancerToken = $token;
      }
    }

    // Use the sitename and site path directory as site identifier.
    $this->siteIdentifier = Hash::siteIdentifier(
      $this->siteName,
      $this->sitePath
    );

    // Test the gathered information to determine if this is/isn't Acquia Cloud.
    $this->isThisAcquiaCloud =
      count($this->balancerAddresses)
      && $this->balancerToken
      && $this->siteEnvironment
      && $this->siteIdentifier
      && $this->siteName
      && $this->siteGroup;
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
  public function getSiteIdentifier() {
    return $this->siteIdentifier;
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
  public function getSitePath() {
    return $this->sitePath;
  }

  /**
   * {@inheritdoc}
   */
  public function isThisAcquiaCloud() {
    return $this->isThisAcquiaCloud;
  }

}
