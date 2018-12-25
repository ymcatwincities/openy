<?php

/**
 * @file
 * Hooks related to module.
 *
 * This file contains no working PHP code; it exists to provide additional
 * documentation for doxygen as well as to document hooks in the standard
 * Drupal manner.
 */

/**
 * @defgroup acquia_spi Acquia Connector SPI module integrations.
 *
 * Module integrations with the Acquia Insight service.
 */

/**
 * Include data to be sent to Acquia Insight as part of the SPI process.
 *
 *  Include custom site information to be sent to the Acquia Insight service
 *  for detailed site analysis. Insight will process this data and alert
 *  appropriately.
 *
 * @return array
 *    An array of custom data keyed by unique identifier.
 *
 *    Required format 'string' => array().
 */
function hook_acquia_connector_spi_get() {
  $data['example'] = array(
    'result' => TRUE,
    'value' => '9000',
  );
  return $data;
}

/**
 * Include data to be sent to Acquia Insight as part of the SPI process.
 *
 * This data will be stored on Acquia's servers in an unencrypted database, so
 * be careful not to send sensitive information in any field. Multiple tests can
 * also be added per callback provided that each test has a unique identifier.
 *
 * @return array
 *   An array of user-contributed test data keyed by unique identifier.
 *   - (string)  description: Detailed information regarding test, its impact,
 *                and other relevant details. Cannot exceed 1024 characters.
 *   - (string)  solved_message: The message to display when the test has
 *                succeeded. Cannot exceed 1024 characters.
 *   - (string)  failed_message: The message to display when the test has
 *                failed. Cannot exceed 1024 characters.
 *   - (boolean) solved: A flag indicating whether or not the test was
 *                successful.
 *   - (string)  fix_details: Information on how to fix or resolve the test if
 *                failed. Cannot exceed 1024 characters.
 *   - (string)  category: The category to place the test within. Must be either
 *                'performance', 'security, or 'best_practices'.
 *   - (int)     severity: The priority level of the custom test. Must be either
 *                0, 1, 2, 4, 8, 16, 32, 64, or 128. Higher severities impact
 *                the Insight score proportionally.
 */
function hook_acquia_connector_spi_test() {
  return array(
    'unique_example' => array(
      'description'    => 'This example test is useful.',
      'solved_message' => 'The test was successful',
      'failed_message' => 'The test has failed',
      'solved'         => TRUE,
      'fix_details'    => 'Please resolve this issue using this fix information.',
      'category'       => 'best_practices',
      'severity'       => 0,
    ),
  );
}
