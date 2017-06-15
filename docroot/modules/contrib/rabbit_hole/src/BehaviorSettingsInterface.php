<?php

namespace Drupal\rabbit_hole;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Behavior settings entities.
 */
interface BehaviorSettingsInterface extends ConfigEntityInterface {

  /**
   * Set the configured action.
   *
   * @param string $action
   *   The action to save.
   */
  public function setAction($action);

  /**
   * Get the configured action.
   *
   * @return string
   *   The action id.
   */
  public function getAction();

  /**
   * Set whether overrides are allowed if this is for a bundle.
   *
   * @param int $allow_override
   *   0 (N/A), 1 (Allow), or 2 (Disallow).
   */
  public function setAllowOverride($allow_override);

  /**
   * Get whether overrides are allowed if this is for a bundle.
   *
   * @return int
   *   Whether overrides are allowed if this is for a bundle. 0 (N/A), 1
   *   (Allow), or 2 (Disallow).
   */
  public function getAllowOverride();

  /**
   * Set the redirect code if action is redirect.
   *
   * @param int $redirect_code
   *   The redirect code (0 for N/A).
   */
  public function setRedirectCode($redirect_code);

  /**
   * Get the redirect code if action is redirect.
   */
  public function getRedirectCode();

  /**
   * Set the redirect path if action is redirect.
   *
   * @param string $redirect
   *   The redirect path.
   */
  public function setRedirectPath($redirect);

  /**
   * Get the redirect path if action is redirect.
   *
   * @return string
   *   The redirect path.
   */
  public function getRedirectPath();

}
