<?php

namespace Drupal\rabbit_hole\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\rabbit_hole\BehaviorSettingsInterface;
use Drupal\rabbit_hole\Exception\InvalidBehaviorSettingException;

/**
 * Defines the Behavior settings entity.
 *
 * @ConfigEntityType(
 *   id = "behavior_settings",
 *   label = @Translation("Behavior settings"),
 *   handlers = {},
 *   config_prefix = "behavior_settings",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "action" = "action",
 *     "allow_override" = "allow_override",
 *     "redirect" = "redirect",
 *     "redirect_code" = "redirect_code"
 *   },
 *   links = {}
 * )
 */
class BehaviorSettings extends ConfigEntityBase implements BehaviorSettingsInterface {
  const OVERRIDE_ALLOW = TRUE;
  const OVERRIDE_DISALLOW = FALSE;

  const REDIRECT_NOT_APPLICABLE = 0;
  const REDIRECT_MOVED_PERMANENTLY = 301;
  const REDIRECT_FOUND = 302;
  const REDIRECT_SEE_OTHER = 303;
  const REDIRECT_NOT_MODIFIED = 304;
  const REDIRECT_USE_PROXY = 305;
  const REDIRECT_TEMPORARY_REDIRECT = 307;

  /**
   * The Behavior settings ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The configured action (e.g. display_page).
   *
   * @var string
   */
  protected $action;

  /**
   * Whether inherited behaviors can be edited (if this is a bundle).
   */
  protected $allow_override;

  /**
   * The path to use for redirects (if the action is redirect).
   *
   * @todo It may be possible to make this reliant on a plugin instead (i.e.
   *  the redirect plugin) - if so, we should probably do this
   */
  protected $redirect;

  /**
   * The code to use for redirects (if the action is redirect).
   *
   * @todo It may be possible to make this reliant on a plugin instead (i.e.
   * the redirect plugin) - if so, we should probably do this
   */
  protected $redirect_code;

  /**
   * {@inheritdoc}
   */
  public function setAction($action) {
    $this->action = $action;
  }

  /**
   * {@inheritdoc}
   */
  public function getAction() {
    return $this->action;
  }

  /**
   * {@inheritdoc}
   */
  public function setAllowOverride($allow_override) {
    if (!is_bool($allow_override)) {
      throw new InvalidBehaviorSettingException('allow_override');
    }
    $this->allow_override = $allow_override;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowOverride() {
    return $this->allow_override;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Possibly this should instead rely on the redirect plugin?
   */
  public function setRedirectCode($redirect_code) {
    if (!in_array($redirect_code, array(
      self::REDIRECT_NOT_APPLICABLE,
      self::REDIRECT_MOVED_PERMANENTLY,
      self::REDIRECT_FOUND,
      self::REDIRECT_SEE_OTHER,
      self::REDIRECT_NOT_MODIFIED,
      self::REDIRECT_USE_PROXY,
      self::REDIRECT_TEMPORARY_REDIRECT,
    )
      )) {
      throw new InvalidBehaviorSettingException('redirect_code');
    }

    if ($this->action !== 'redirect'
      && $redirect_code !== self::REDIRECT_NOT_APPLICABLE) {
      throw new InvalidBehaviorSettingException('redirect_code');
    }
    $this->redirect_code = $redirect_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectCode() {
    return $this->redirect_code;
  }

  /**
   * {@inheritdoc}
   */
  public function setRedirectPath($redirect) {
    if ($this->action !== 'redirect' && $redirect != "") {
      throw new InvalidBehaviorSettingException('redirect');
    }
    $this->redirect = $redirect;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectPath() {
    return $this->redirect;
  }

}
