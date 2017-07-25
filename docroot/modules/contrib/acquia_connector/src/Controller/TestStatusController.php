<?php

namespace Drupal\acquia_connector\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class SpiController.
 */
class TestStatusController extends ControllerBase {

  /**
   * Determines status of user-contributed tests.
   *
   * Determines the status of all user-contributed tests and logs any failures
   * to a tracking table.
   *
   * @param bool $log
   *   (Optional) If TRUE, log all failures.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   An associative array containing any tests which failed validation.
   */
  public function testStatus($log = FALSE) {
    $custom_data = array();

    // Iterate through modules which contain hook_acquia_spi_test().
    foreach (\Drupal::moduleHandler()->getImplementations('acquia_connector_spi_test') as $module) {
      $function = $module . '_acquia_connector_spi_test';
      if (function_exists($function)) {

        $result = $this->testValidate($function());
        if (!$result['result']) {
          $custom_data[$module] = $result;

          foreach ($result['failure'] as $test_name => $test_failures) {
            foreach ($test_failures as $test_param => $test_value) {
              $variables = array(
                '@module'     => $module,
                '@message'    => $test_value['message'],
                '@param_name' => $test_param,
                '@test'       => $test_name,
                '@value'      => $test_value['value'],
              );
              // Only log if we're performing a full validation check.
              if ($log) {
                drupal_set_message($this->t("Custom test validation failed for @test in @module and has been logged: @message for parameter '@param_name'; current value '@value'.", $variables), 'error');
                \Drupal::logger('acquia spi test')->notice("<em>Custom test validation failed</em>: @message for parameter '@param_name'; current value '@value'. (<em>Test '@test_name' in module '@module_name'</em>)", $variables);
              }
            }
          }
        }
      }
    }

    // If a full validation check is being performed, go to the status page to
    // show the results.
    if ($log) {
      return $this->redirect('system.status');
    }

    return $custom_data;
  }

  /**
   * Validates data from custom test callbacks.
   *
   * @param array $collection
   *   An associative array containing a collection of user-contributed tests.
   *
   * @return array
   *   An associative array containing the validation result of the given tests,
   *   along with any failed parameters.
   */
  public function testValidate($collection) {
    $result = TRUE;
    $check_result_value = array();

    // Load valid categories and severities.
    $categories = array('performance', 'security', 'best_practices');
    $severities = array(0, 1, 2, 4, 8, 16, 32, 64, 128);

    foreach ($collection as $machine_name => $tests) {
      foreach ($tests as $check_name => $check_value) {
        $fail_value = '';
        $message    = '';

        $check_name  = strtolower($check_name);
        $check_value = (is_string($check_value)) ? strtolower($check_value) : $check_value;

        // Validate the data inputs for each check.
        switch ($check_name) {
          case 'category':
            if (!is_string($check_value) || !in_array($check_value, $categories)) {
              $type       = gettype($check_value);
              $fail_value = "$check_value ($type)";
              $message    = 'Value must be a string and one of ' . implode(', ', $categories);
            }
            break;

          case 'solved':
            if (!is_bool($check_value)) {
              $type       = gettype($check_value);
              $fail_value = "$check_value ($type)";
              $message    = 'Value must be a boolean';
            }
            break;

          case 'severity':
            if (!is_int($check_value) || !in_array($check_value, $severities)) {
              $type       = gettype($check_value);
              $fail_value = "$check_value ($type)";
              $message    = 'Value must be an integer and set to one of ' . implode(', ', $severities);
            }
            break;

          default:
            if (!is_string($check_value) || strlen($check_value) > 1024) {
              $type       = gettype($check_value);
              $fail_value = "$check_value ($type)";
              $message    = 'Value must be a string and no more than 1024 characters';
            }
            break;
        }

        if (!empty($fail_value) && !empty($message)) {
          $check_result_value['failed'][$machine_name][$check_name]['value']   = $fail_value;
          $check_result_value['failed'][$machine_name][$check_name]['message'] = $message;
        }
      }
    }

    // If there were any failures, the test has failed. Into exile it must go.
    if (!empty($check_result_value)) {
      $result = FALSE;
    }

    return array('result' => $result, 'failure' => (isset($check_result_value['failed'])) ? $check_result_value['failed'] : array());
  }

}
