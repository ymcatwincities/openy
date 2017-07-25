<?php

/**
 * @file
 * Drush integration for the acquia connector module.
 */

use Drupal\acquia_connector\Controller\SpiController;
use Drupal\acquia_connector\Controller\TestStatusController;

/**
 * Implements hook_drush_command().
 */
function acquia_connector_drush_command() {
  $items = array();
  $items['spi-get'] = array(
    'description' => dt('Output raw Acquia SPI data.'),
    'options' => array(
      'outfile' => dt('Optional. A file to write data to in the current directory. If omitted Drush will output to stdout.'),
      'format' => dt('Optional. Format may be json, print_r, or var_dump. Defaults to print_r.'),
    ),
    'examples' => array(
      'spi-get --format=json --outfile=spi.json' => 'Write JSON encoded SPI data to spi.json in current directory.',
    ),
    'drupal dependencies' => array('acquia_connector'),
  );
  $items['spi-test-validate'] = array(
    'callback'    => 'drush_acquia_connector_custom_test_validate',
    'description' => dt('Perform a validation check on any modules with Acquia SPI custom tests.'),
    'aliases'     => array('spi-tv'),
  );
  return $items;
}

/**
 * Implements hook_drush_help().
 */
function acquia_connector_drush_help($section) {
  switch ($section) {
    case 'drush:spi-get':
      return dt("Output raw Acquia SPI data. NOTE: If the Acquia Connector module is not enabled or installed, execute with: drush -i @spidir", array('@spidir' => dirname(__FILE__)));
  }
}

/**
 * Output raw Acquia SPI data.
 */
function drush_acquia_connector_spi_get() {
  $raw_spi = _acquia_connector_drush_get();
  switch (drush_get_option('format', 'print_r')) {
    case 'json':
      $spi = drush_json_encode($raw_spi);
      break;

    case 'var_dump':
    case 'var_export':
      $spi = var_export($raw_spi, 1);
      break;

    case 'print_r':
    default:
      $spi = print_r($raw_spi, 1);
      break;
  }
  if ($file = drush_get_option('outfile')) {
    // Reset our working directory so that relative paths work as expected.
    $wd = getcwd();
    chdir($_SERVER['PWD']);
    if (file_put_contents($file, $spi)) {
      drush_log(dt('SPI Data written to @outfile.', array('@outfile' => realpath($file))), 'success');
    }
    chdir($wd);
  }
  else {
    echo $spi;
  }
}

/**
 * Helper function to include acquia_connector.module file.
 *
 * If the Acquia Connector module is not enabled, include the
 * acquia_connector.module file.
 * HINT: invoke drush with the --include option pointing module folder,
 * otherwise drush will not execute spi-get.
 */
function _acquia_connector_drush_get() {
  global $conf;
  $conf['acquia_connector.settings']['spi']['ssl_verify'] = FALSE;
  $conf['acquia_connector.settings']['spi']['ssl_override'] = TRUE;

  $client = \Drupal::service('acquia_connector.client');
  $spi = new SpiController($client);

  if (!method_exists($spi, 'get')) {
    drush_print(t('Method get not implement'));
  }
  if (!function_exists('acquia_connector_load_versions')) {
    drush_include(dirname(dirname(__FILE__)) . '/acquia_connector', 'acquia_connector', drush_drupal_major_version(), 'module');
  }
  return $spi->get();
}

/**
 * A command callback and drush wrapper for custom test validation.
 */
function drush_acquia_connector_custom_test_validate() {
  $pass = array();
  $failure = array();

  $modules = \Drupal::moduleHandler()->getImplementations('acquia_connector_spi_test');
  if (!$modules) {
    drush_print_r((string) dt("No Acquia SPI custom tests were detected."));
    return;
  }
  drush_print_r((string) dt("Acquia SPI custom tests were detected in: @modules \n", array('@modules' => implode(', ', $modules))));

  foreach ($modules as $module) {
    $function = $module . '_acquia_connector_spi_test';
    if (function_exists($function)) {
      $testStatus = new TestStatusController();
      $result = $testStatus->testValidate($function());

      if (!$result['result']) {
        $failure[] = $module;
        drush_print_r((string) dt("[FAILED]  Validation failed for '@module' and has been logged.", array('@module' => $module)));

        foreach ($result['failure'] as $test_name => $test_failures) {
          foreach ($test_failures as $test_param => $test_value) {
            $variables = array(
              '@module_name' => $module,
              '@message'     => $test_value['message'],
              '@param_name'  => $test_param,
              '@test_name'   => $test_name,
              '@value'       => $test_value['value'],
            );
            drush_print_r((string) dt("[DETAILS] @message for parameter '@param_name'; current value '@value'. (Test @test_name in module @module_name)", $variables));
            watchdog('acquia connector spi test', "<em>Custom test validation failed</em>: @message for parameter '@param_name'; current value '@value'. (<em>Test '@test_name' in module '@module_name'</em>)", $variables);
          }
        }
      }
      else {
        $pass[] = $module;
        drush_print_r((string) dt("[PASSED]  Validation passed for '@module.'", array('@module' => $module)));
      }
    }
    drush_print_r("");
  }

  $message = (string) dt('Validation checks completed.');
  $variables = array();
  if (count($pass) > 0) {
    $message .= "\n";
    $variables['@passes'] = implode(', ', $pass);
    $variables['@pass_count'] = count($pass);
    $message .= (string) dt("@pass_count module(s) passed validation: @passes.");
  }
  if (count($failure) > 0) {
    $message .= "\n";
    $variables['@failures'] = implode(', ', $failure);
    $variables['@fail_count'] = count($failure);
    $message .= (string) dt("@fail_count module(s) failed validation: @failures.");
  }

  drush_print_r((string) dt($message, $variables));

}
