<?php

/**
 * @file
 * Contains \Drupal\memcache\DrupalMemcacheConfig.
 */

namespace Drupal\memcache;

use Drupal\Core\Site\Settings;

/**
 * Class for holding Memcache related config
 */
class DrupalMemcacheConfig {

  /**
   * Array with the settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The site settings instance.
   */
  public function __construct(Settings $settings) {
    $this->settings = $settings->get('memcache', []);
  }

  /**
   * Returns a memcache setting.
   *
   * Settings can be set in settings.php in the $settings['memcache'] array and
   * requested by this function. Settings should be used over configuration for
   * read-only, possibly low bootstrap configuration that is environment
   * specific.
   *
   * @param string $name
   *   The name of the setting to return.
   * @param mixed $default
   *   (optional) The default value to use if this setting is not set.
   *
   * @return mixed
   *   The value of the setting, the provided default if not set.
   */
  public function get($name, $default = NULL) {
    return isset($this->settings[$name]) ? $this->settings[$name] : $default;
  }

  /**
   * Returns all Memcache settings.
   *
   * @return array
   *   All settings.
   */
  public function getAll() {
    return $this->settings;
  }
}
