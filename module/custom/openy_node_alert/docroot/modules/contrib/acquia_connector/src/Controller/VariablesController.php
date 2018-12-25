<?php

namespace Drupal\acquia_connector\Controller;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\Component\Serialization\Json;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

/**
 * Class MappingController.
 */
class VariablesController extends ControllerBase {

  /**
   * Mapping array. Loaded from configuration.
   *
   * @var array
   */
  protected $mapping = [];

  /**
   * All config for the site.
   *
   * @var null|array
   */
  protected $configs = NULL;

  /**
   * Construction method.
   */
  public function __construct() {
    $this->mapping = \Drupal::config('acquia_connector.settings')->get('mapping');
  }

  /**
   * Load configs for all enabled modules.
   *
   * @return array
   *   Array of Drupal configs.
   */
  public function getAllConfigs() {
    if (!is_null($this->configs)) {
      return $this->configs;
    }

    $this->configs = [];
    $names = \Drupal::configFactory()->listAll();
    foreach ($names as $config_name) {
      $this->configs[$config_name] = \Drupal::config($config_name)->get();
    }

    return $this->configs;
  }

  /**
   * Get a variable value by the variable name.
   *
   * @param string $var
   *   Variable name.
   *
   * @return mixed
   *   Variable value.
   *
   * @throws \UnexpectedValueException
   */
  public function getVariableValue($var) {

    // We have no mapping for the variable.
    if (empty($this->mapping[$var])) {
      throw new \UnexpectedValueException($var);
    }

    // Variable type (for state, setting and container parameter only).
    // Holds Config name for the configuration object variables.
    $var_type = $this->mapping[$var][0];
    // Variable machine name (for state, settings and container parameter only).
    $var_name = !empty($this->mapping[$var][1]) ? $this->mapping[$var][1] : NULL;

    // Variable is Drupal state.
    if ($var_type == 'state') {
      return \Drupal::state()->get($var_name);
    }

    // Variable is Drupal setting.
    if ($var_type == 'settings') {
      return Settings::get($var_name);
    }

    // Variable is Container Parameter.
    if ($var_type == 'container_parameter') {
      if (\Drupal::hasContainer()) {
        try {
          return \Drupal::getContainer()->getParameter($var_name);
        }
        catch (ParameterNotFoundException $e) {
          // Parameter not found.
        }
      }
      throw new \UnexpectedValueException($var);
    }

    // Variable is data from Configuration object (D7 Variable).
    // We can not detect this variable type so we're processing it in last turn.
    $key_exists = NULL;
    $config = self::getAllConfigs();
    $value = NestedArray::getValue($config, $this->mapping[$var], $key_exists);
    if ($key_exists) {
      return $value;
    }

    throw new \UnexpectedValueException($var);

  }

  /**
   * Get all system variables.
   *
   * @return array
   *   Variables values keyed by the variable name.
   */
  public function getVariablesData() {
    // Send SPI definition timestamp to see if the site needs updates.
    $data = [
      'acquia_spi_def_timestamp' => \Drupal::config('acquia_connector.settings')->get('spi.def_timestamp'),
    ];
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
      'http_response_debug_cacheability_headers',
    ];

    $spi_def_vars = \Drupal::config('acquia_connector.settings')->get('spi.def_vars');
    $waived_spi_def_vars = \Drupal::config('acquia_connector.settings')->get('spi.def_waived_vars');
    // Merge hard coded $variables with vars from SPI definition.
    foreach ($spi_def_vars as $var_name => $var) {
      if (!in_array($var_name, $waived_spi_def_vars) && !in_array($var_name, $variables)) {
        $variables[] = $var_name;
      }
    }

    // @todo Add comment settings for node types.
    foreach ($variables as $name) {
      try {
        $data[$name] = $this->getVariableValue($name);
      }
      catch (\UnexpectedValueException $e) {
        // Variable does not exist.
      }
    }

    // Unset waived vars so they won't be sent to NSPI.
    foreach ($data as $var_name => $var) {
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
   * @param array $set_variables
   *   Variables to be set.
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
    $ignored = array_merge($ignored, array(
      'drupal_private_key',
      'site_mail',
      'site_name',
      'maintenance_mode',
      'user_register',
    ));
    // Variables that can be automatically set.
    $whitelist = \Drupal::config('acquia_connector.settings')->get('spi.set_variables_automatic');
    foreach ($set_variables as $key => $value) {
      // Approved variables get set immediately unless ignored.
      if (in_array($key, $whitelist) && !in_array($key, $ignored)) {
        if (!empty($this->mapping[$key])) {
          // State.
          if ($this->mapping[$key][0] == 'state' and !empty($this->mapping[$key][1])) {
            \Drupal::state()->set($this->mapping[$key][1], $value);
            $saved[] = $key;
          }
          elseif ($this->mapping[$key][0] == 'settings') {
            // @todo no setter for Settings
          }
          // Variable.
          else {
            $mapping_row_copy = $this->mapping[$key];
            $config_name = array_shift($mapping_row_copy);
            $variable_name = implode('.', $mapping_row_copy);
            \Drupal::configFactory()->getEditable($config_name)->set($variable_name, $value);
            \Drupal::configFactory()->getEditable($config_name)->save();
            $saved[] = $key;
          }
        }
        // @todo: for future D8 implementation. "config.name:variable.name".
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
