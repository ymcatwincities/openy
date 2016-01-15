<?php

/**
 * @file
 * Contains \Drupal\acquia_connector\Controller\VariablesController.
 */

namespace Drupal\acquia_connector\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility;

/**
 * Class MappingController.
 */
class VariablesController extends ControllerBase {
  protected $mapping = [];

  public function __construct() {
    $this->mapping = \Drupal::config('acquia_connector.settings')->get('mapping');
  }

  /**
   * Load configs for all enabled modules.
   *
   * @return array
   */
  public function getAllConfigs() {
    $result = array();
    $names = \Drupal::configFactory()->listAll();
    foreach ($names as $key => $config_name) {
      $result[$config_name] = \Drupal::config($config_name)->get();
    }
    return $result;
  }

  /**
   * Get all system variables.
   *
   * @return array()
   */
  public function getVariablesData() {
    $data = [];
    $variables = [
      'acquia_spi_send_node_user',
      'acquia_spi_admin_priv',
      'acquia_spi_module_diff_data',
      'acquia_spi_send_watchdog',
      'acquia_spi_use_cron',
      'cache_backends',
      'cache_default_class',
      'cache_inc',
      'cron_safe_threshold',
      'googleanalytics_cache',
      'error_level',
      'preprocess_js',
      'page_cache_maximum_age',
      'block_cache',
      'preprocess_css',
      'page_compression',
      'cache',
      'cache_lifetime',
      'cron_last',
      'clean_url',
      'redirect_global_clean',
      'theme_zen_settings',
      'site_offline',
      'site_name',
      'user_register',
      'user_signatures',
      'user_admin_role',
      'user_email_verification',
      'user_cancel_method',
      'filter_fallback_format',
      'dblog_row_limit',
      'date_default_timezone',
      'file_default_scheme',
      'install_profile',
      'maintenance_mode',
      'update_last_check',
      'site_default_country',
      'acquia_spi_saved_variables',
      'acquia_spi_set_variables_automatic',
      'acquia_spi_ignored_set_variables',
      'acquia_spi_set_variables_override',
    ];

    $allConfigData = self::getAllConfigs();
    $spi_def_vars = \Drupal::config('acquia_connector.settings')->get('spi.def_vars');
    $waived_spi_def_vars = \Drupal::config('acquia_connector.settings')->get('spi.def_waived_vars');
    // Merge hard coded $variables with vars from SPI definition.
    foreach($spi_def_vars as $var_name => $var) {
      if (!in_array($var_name, $waived_spi_def_vars) && !in_array($var_name, $variables)) {
        $variables[] = $var_name;
      }
    }

    // @todo Add comment settings for node types.

    foreach ($variables as $name) {
      if (!empty($this->mapping[$name])) {
        // state
        if ($this->mapping[$name][0] == 'state' and !empty($this->mapping[$name][1])) {
          $data[$name] = \Drupal::state()->get($this->mapping[$name][1]);
        }
        elseif($this->mapping[$name][0] == 'settings' and !empty($this->mapping[$name][1])) {
          $data[$name] = Settings::get($this->mapping[$name][1]);
        }
        // variable
        else {
          $key_exists = NULL;
          $value = Utility\NestedArray::getValue($allConfigData, $this->mapping[$name], $key_exists);
          if ($key_exists) {
            $data[$name] = $value;
          }
          else {
            $data[$name] = 0;
          }
        }
      }
      else {
        // @todo: Implement D8 way to update variables mapping.
        $data[$name] = 'Variable not implemented.';
      }
    }

    // Unset waived vars so they won't be sent to NSPI.
    foreach($data as $var_name => $var) {
      if (in_array($var_name, $waived_spi_def_vars)) {
        unset($data[$var_name]);
      }
    }

    // Collapse to JSON string to simplify transport.
    return Json::encode($data);
  }

  /**
   * Set variables from NSPI response.
   *
   * @param  array $set_variables Variables to be set.
   * @return NULL
   */
  public function setVariables($set_variables) {
    \Drupal::logger('acquia spi')->notice('SPI set variables: @messages', array('@messages' => implode(', ', $set_variables)));
    if (empty($set_variables)) {
      return;
    }
    $saved = array();
    $ignored = \Drupal::config('acquia_connector.settings')->get('spi.ignored_set_variables');

    if (!\Drupal::config('acquia_connector.settings')->get('spi.set_variables_override')) {
      $ignored[] = 'acquia_spi_set_variables_automatic';
    }
    // Some variables can never be set.
    $ignored = array_merge($ignored, array('drupal_private_key', 'site_mail', 'site_name', 'maintenance_mode', 'user_register'));
    // Variables that can be automatically set.
    $whitelist = \Drupal::config('acquia_connector.settings')->get('spi.set_variables_automatic');
    foreach($set_variables as $key => $value) {
      // Approved variables get set immediately unless ignored.
      if (in_array($key, $whitelist) && !in_array($key, $ignored)) {
        if (!empty($this->mapping[$key])) {
          // state
          if ($this->mapping[$key][0] == 'state' and !empty($this->mapping[$key][1])) {
            \Drupal::state()->set($this->mapping[$key][1], $value);
            $saved[] = $key;
          }
          elseif($this->mapping[$key][0] == 'settings') {
            // @todo no setter for Settings
          }
          // variable
          else {
            $mapping_row_copy = $this->mapping[$key];
            $config_name = array_shift($mapping_row_copy);
            $variable_name = implode('.', $mapping_row_copy);
            \Drupal::configFactory()->getEditable($config_name)->set($variable_name, $value);
            \Drupal::configFactory()->getEditable($config_name)->save();
            $saved[] = $key;
          }
        }
        // todo: for future D8 implementation. "config.name:variable.name"
        elseif (preg_match('/^([^\s]+):([^\s]+)$/ui', $key, $regs)) {
          $config_name = $regs[1];
          $variable_name = $regs[2];
          \Drupal::configFactory()->getEditable($config_name)->set($variable_name, $value);
          \Drupal::configFactory()->getEditable($config_name)->save();
          $saved[] = $key;
        }
        else {
          \Drupal::logger('acquia spi')->notice('Variable is not implemented: ' . $key);
        }
      }
    }
    if (!empty($saved)) {
      \Drupal::configFactory()->getEditable('acquia_connector.settings')->set('spi.saved_variables', array('variables' => $saved, 'time' => time()));
      \Drupal::configFactory()->getEditable('acquia_connector.settings')->save();
      \Drupal::logger('acquia spi')->notice('Saved variables from the Acquia Network: @variables', array('@variables' => implode(', ', $saved)));
    }
    else {
      \Drupal::logger('acquia spi')->notice('Did not save any variables from the Acquia Network.');
    }
  }

}
