<?php

namespace Drupal\acquia_purge;

/**
 * Describes technical information accessors for the Acquia Cloud environment.
 */
interface HostingInfoInterface {

  /**
   * Get the load balancer IP adresses installed in front of this site.
   *
   * @return string[]
   *   Unassociative list of adresses in the form of 'I.P.V.4', or empty array.
   */
  public function getBalancerAddresses();

  /**
   * Get the token used to authenticate cache invalidations with.
   *
   * @return string[]
   *   Token string, e.g. 'oursecret' or 'sitedev'.
   */
  public function getBalancerToken();

  /**
   * Get the Acquia site environment.
   *
   * @return string
   *   The site environment, e.g. 'dev'.
   */
  public function getSiteEnvironment();

  /**
   * Get the Acquia site group.
   *
   * @return string
   *   The site group, e.g. 'site' or '' when unavailable.
   */
  public function getSiteGroup();

  /**
   * Get a unique identifier for this Acquia site.
   *
   * @return string
   *   Unique string for this Drupal instance, even within multisites!
   */
  public function getSiteIdentifier();

  /**
   * Get the Acquia site name.
   *
   * @return string
   *   The site group, e.g. 'sitedev' or '' when unavailable.
   */
  public function getSiteName();

  /**
   * Get the Drupal site path.
   *
   * @return string
   *   The site path, e.g. 'site/default' or 'site/mysecondsite'.
   */
  public function getSitePath();

  /**
   * Determine whether the current hosting environment is Acquia Cloud or not.
   *
   * @return true|false
   *   Boolean expression where 'true' indicates Acquia Cloud or 'false'.
   */
  public function isThisAcquiaCloud();

}
