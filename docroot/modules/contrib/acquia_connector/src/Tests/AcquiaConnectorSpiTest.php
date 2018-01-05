<?php

/**
 * @file
 * Definition of Drupal\acquia_connector\Tests\AcquiaConnectorSpiTest.
 */

namespace Drupal\acquia_connector\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\acquia_connector\Controller\SpiController;
use Drupal\acquia_connector\Controller\VariablesController;
use Drupal\Component\Serialization\Json;

/**
 * Tests the functionality of the Acquia SPI module.
 *
 * @group Acquia connector
 */
class AcquiaConnectorSpiTest extends WebTestBase {
  protected $strictConfigSchema = FALSE;
  protected $privilegedUser;
  protected $setupPath;
  protected $credentialsPath;
  protected $settingsPath;
  protected $statusReportUrl;
  protected $baseUrl;
  protected $environmentChangePath;
  protected $acqtestEmail       = 'TEST_networkuser@example.com';
  protected $acqtestPass        = 'TEST_password';
  protected $acqtestId          = 'TEST_AcquiaConnectorTestID';
  protected $acqtestKey         = 'TEST_AcquiaConnectorTestKey';
  protected $acqtestExpiredId   = 'TEST_AcquiaConnectorTestIDExp';
  protected $acqtestExpiredKey  = 'TEST_AcquiaConnectorTestKeyExp';
  protected $acqtest503Id       = 'TEST_AcquiaConnectorTestID503';
  protected $acqtest503Key      = 'TEST_AcquiaConnectorTestKey503';
  protected $acqtestErrorId     = 'TEST_AcquiaConnectorTestIDErr';
  protected $acqtestErrorKey    = 'TEST_AcquiaConnectorTestKeyErr';
  protected $acqtestName        = 'test name';
  protected $acqtestMachineName = 'test_name';

  protected $platformKeys = [
    'php',
    'webserver_type',
    'webserver_version',
    'php_extensions',
    'php_quantum',
    'database_type',
    'database_version',
    'system_type',
    'system_version',
  ];
  protected $spiDataKeys = [
    'spi_data_version',
    'site_key',
    'modules',
    'platform',
    'quantum',
    'system_status',
    'failed_logins',
    '404s',
    'watchdog_size',
    'watchdog_data',
    'last_nodes',
    'last_users',
    'extra_files',
    'ssl_login',
    'distribution',
    'base_version',
    'build_data',
    'roles',
    'uid_0_present',
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'acquia_connector',
    'toolbar',
    'acquia_connector_test',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    if (empty($_SERVER['SERVER_SOFTWARE'])) {
      $_SERVER['SERVER_SOFTWARE'] = $this->randomString();
    }
    parent::setUp();

    global $base_url;
    // Enable any modules required for the test
    // Create and log in our privileged user.
    $this->privilegedUser = $this->drupalCreateUser(array(
      'administer site configuration',
      'access administration pages',
    ));
    $this->drupalLogin($this->privilegedUser);

    // Setup variables.
    $this->environmentChangePath = '/admin/config/system/acquia-connector/environment-change';
    $this->credentialsPath = 'admin/config/system/acquia-connector/credentials';
    $this->settingsPath = 'admin/config/system/acquia-connector';
    $this->statusReportUrl = 'admin/reports/status';
    $this->baseUrl = $base_url;

    // Local env.
    $config = \Drupal::configFactory()->getEditable('acquia_connector.settings');
    $config->set('spi.server', $this->baseUrl);
    $config->set('spi.ssl_verify', FALSE);
    $config->set('spi.ssl_override', TRUE);
    // Set mapping for the test variables.
    $mapping = $config->get('mapping');
    $mapping['test_variable_1'] = ['state', 'test_variable_1'];
    $mapping['test_variable_2'] = ['state', 'test_variable_2'];
    $mapping['test_variable_3'] = ['state', 'test_variable_3'];
    $config->set('mapping', $mapping);
    $config->save(TRUE);

    // Set values for test variables.
    \Drupal::state()->set('test_variable_1', 1);
    \Drupal::state()->set('test_variable_2', 2);
    \Drupal::state()->set('test_variable_3', 3);

  }

  /**
   * Helper function for storing UI strings.
   */
  private function acquiaSpiStrings($id) {
    switch ($id) {
      case 'spi-status-text':
        return 'SPI data will be sent once every 30 minutes once cron is called';

      case 'spi-not-sent';
        return 'SPI data has not been sent';

      case 'spi-send-text';
        return 'manually send SPI data';

      case 'spi-data-sent':
        return 'SPI data sent';

      case 'spi-data-sent-error':
        return 'Error sending SPI data. Consult the logs for more information.';

      case 'spi-new-def':
        return 'There are new checks that will be performed on your site by the Acquia Connector';

      case 'provide-site-name':
        return 'provide a site name';

      case 'change-env-detected':
        return 'A change in your site\'s environment has been detected. SPI data cannot be submitted until this is resolved.';

      case 'confirm-action':
        return 'confirm the action you wish to take';

      case 'block-site-message':
        return 'This site has been disabled from sending profile data to Acquia Cloud.';

      case 'unblock-site':
        return 'Enable this site';

      case 'acquia-hosted':
        return 'Your site is now Acquia hosted.';

      case 'no-acquia-hosted':
        return 'Your site is no longer Acquia hosted.';
    }
  }

  public function testAll() {
    $this->_testAcquiaSpiUi();
    $this->_testAcquiaSpiGet();
    $this->_testNoObjectInSpiData();
    $this->_testAcquiaSpiSend();
    $this->_testAcquiaSpiUpdateResponse();
    $this->_testAcquiaSpiSetVariables();
  }

  /**
   * Test Acquia SPI UI.
   */
  public function _testAcquiaSpiUi() {
    $this->drupalGet($this->statusReportUrl);
    $this->assertNoText($this->acquiaSPIStrings('spi-status-text'), 'SPI send option does not exist when site is not connected');
    // Connect site on key and id that will error.
    $edit_fields = [
      'acquia_identifier' => $this->acqtestErrorId,
      'acquia_key' => $this->acqtestErrorKey,
    ];
    $submit_button = 'Connect';
    $this->drupalPostForm($this->credentialsPath, $edit_fields, $submit_button);

    // If name and machine name are empty.
    $this->drupalGet($this->statusReportUrl);
    $this->assertText($this->acquiaSPIStrings('spi-not-sent'), 'SPI data was not sent');
    $this->assertText($this->acquiaSPIStrings('provide-site-name'), 'Provide a site name');

    $edit_fields = [
      'name' => $this->acqtestName,
      'machine_name' => $this->acqtestMachineName,
    ];
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settingsPath, $edit_fields, $submit_button);

    // Send SPI data.
    $this->drupalGet($this->statusReportUrl);
    $this->assertText($this->acquiaSPIStrings('spi-status-text'), 'SPI explanation text exists');
    $this->clickLink($this->acquiaSPIStrings('spi-send-text'));
    $this->assertNoText($this->acquiaSPIStrings('spi-data-sent'), 'SPI data was not sent');
    $this->assertText($this->acquiaSPIStrings('spi-data-sent-error'), 'Page says there was an error sending data');

    // Connect site on non-error key and id.
    $this->connectSite();
    $this->drupalGet($this->statusReportUrl);
    $this->clickLink($this->acquiaSPIStrings('spi-send-text'));
    $this->assertText($this->acquiaSPIStrings('spi-data-sent'), 'SPI data was sent');
    $this->assertNoText($this->acquiaSPIStrings('spi-not-sent'), 'SPI does not say "data has not been sent"');
    $this->assertText('This is the first connection from this site, it may take awhile for it to appear on the Acquia Network', 'First connection');

    // Machine name change.
    $edit_fields = [
      'name' => $this->acqtestName,
      'machine_name' => $this->acqtestMachineName . '_change',
    ];
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settingsPath, $edit_fields, $submit_button);
    $this->assertText('A change has been detected in your site environment. Please check the Acquia SPI status on your Status Report page for more information.', 'Change environment detected');
    $this->drupalGet($this->statusReportUrl);
    $this->clickLink($this->acquiaSPIStrings('confirm-action'));
    $this->assertText('Your site machine name changed from ' . $this->acqtestMachineName . ' to ' . $this->acqtestMachineName . '_change' . '.');

    // Block site.
    $edit_fields = [
      'env_change_action' => 'block',
    ];

    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->environmentChangePath, $edit_fields, $submit_button);
    $this->assertText($this->acquiaSPIStrings('block-site-message'), 'Block site');
    $this->clickLink($this->acquiaSPIStrings('unblock-site'));

    // Unblock site.
    $edit_fields = [
      'env_change_action[unblock]' => TRUE,
    ];

    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->environmentChangePath, $edit_fields, $submit_button);
    $this->assertText('Your site has been enabled and is sending data to Acquia Cloud', 'Enable site');
    $this->assertText($this->acquiaSPIStrings('spi-data-sent'), 'SPI data was sent');
    $this->assertNoText($this->acquiaSPIStrings('spi-not-sent'), 'SPI does not say "data has not been sent"');

    // Update machine name on existing site.
    $this->clickLink($this->acquiaSPIStrings('spi-send-text'));
    $this->assertText($this->acquiaSPIStrings('change-env-detected'), 'Change environment detected');
    $this->clickLink($this->acquiaSPIStrings('confirm-action'));

    $edit_fields = [
      'env_change_action' => 'update',
    ];

    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->environmentChangePath, $edit_fields, $submit_button);

    // Name change.
    $edit_fields = [
      'name' => $this->acqtestName . ' change',
      'machine_name' => $this->acqtestMachineName . '_change',
    ];
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settingsPath, $edit_fields, $submit_button);
    $this->drupalGet($this->statusReportUrl);
    $this->assertNoText($this->acquiaSPIStrings('spi-not-sent'), 'SPI does not say "data has not been sent"');
    $this->clickLink($this->acquiaSPIStrings('spi-send-text'));
    $this->assertText('Site name updated (from ' . $this->acqtestName . ' to ' . $this->acqtestName . ' change).', 'Change name');
  }

  /**
   * Test Acquia SPI get.
   */
  public function _testAcquiaSpiGet() {
    // Connect site on non-error key and id.
    $this->connectSite();

    $edit_fields = [
      'name' => $this->acqtestName,
      'machine_name' => $this->acqtestMachineName,
    ];
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settingsPath, $edit_fields, $submit_button);

    // Test spiControllerTest::get.
    $spi = new SpiControllerTest();
    $spi_data = $spi->get();
    $valid = is_array($spi_data);
    $this->assertTrue($valid, 'spiController::get returns an array');
    if ($valid) {
      foreach ($this->spiDataKeys as $key) {
        if (!array_key_exists($key, $spi_data)) {
          $valid = FALSE;
          break;
        }
      }
      $this->assertTrue($valid, 'Array has expected keys');
      $private_key = \Drupal::service('private_key')->get();
      $this->assertEqual(sha1($private_key), $spi_data['site_key'], 'Site key is sha1 of Drupal private key');
      $this->assertTrue(!empty($spi_data['spi_data_version']), 'SPI data version is set');
      $vars = Json::decode($spi_data['system_vars']);
      $this->assertTrue(is_array($vars), 'SPI data system_vars is a JSON-encoded array');
      $this->assertTrue(isset($vars['test_variable_3']), 'test_variable_3 included in SPI data');
      $this->assertTrue(!empty($spi_data['modules']), 'Modules is not empty');
      $modules = [
        'status',
        'name',
        'version',
        'package',
        'core',
        'project',
        'filename',
        'module_data',
      ];
      $diff = array_diff(array_keys($spi_data['modules'][0]), $modules);
      $this->assertTrue(empty($diff), 'Module elements have expected keys');
      $diff = array_diff(array_keys($spi_data['platform']), $this->platformKeys);
      $this->assertTrue(empty($diff), 'Platform contains expected keys');
      $roles = Json::decode($spi_data['roles']);
      $this->assertTrue(is_array($roles), 'Roles is an array');
      $this->assertTrue(isset($roles) && array_key_exists('anonymous', $roles), 'Roles array contains anonymous user');
    }
  }

  /**
   * Validate Acquia SPI data.
   */
  public function _testNoObjectInSpiData() {
    // Connect site on non-error key and id.
    $this->connectSite();

    $edit_fields = [
      'name' => $this->acqtestName,
      'machine_name' => $this->acqtestMachineName,
    ];
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settingsPath, $edit_fields, $submit_button);

    $spi = new SpiControllerTest();
    $spi_data = $spi->get();

    $this->assertFalse($this->isContainObjects($spi_data), 'SPI data does not contain PHP objects.');
  }

  /**
   * Test Acquia SPI send.
   */
  public function _testAcquiaSpiSend() {
    // Connect site on invalid credentials.
    $edit_fields = [
      'acquia_identifier' => $this->acqtestErrorId,
      'acquia_key' => $this->acqtestErrorKey,
    ];
    $submit_button = 'Connect';
    $this->drupalPostForm($this->credentialsPath, $edit_fields, $submit_button);

    // Attempt to send something.
    $client = \Drupal::service('acquia_connector.client');
    // Connect site on valid credentials.
    $this->connectSite();

    // Check that result is an array.
    $spi = new SpiControllerTest();
    $spi_data = $spi->get();
    unset($spi_data['spi_def_update']);
    $result = $client->sendNspi($this->acqtestId, $this->acqtestKey, $spi_data);
    $this->assertTrue(is_array($result), 'SPI update result is an array');

    // Trigger a validation error on response.
    $spi_data['test_validation_error'] = TRUE;
    unset($spi_data['spi_def_update']);
    $result = $client->sendNspi($this->acqtestId, $this->acqtestKey, $spi_data);
    $this->assertFalse($result, 'SPI result is false if validation error.');
    unset($spi_data['test_validation_error']);

    // Trigger a SPI definition update response.
    $spi_data['spi_def_update'] = TRUE;
    $result = $client->sendNspi($this->acqtestId, $this->acqtestKey, $spi_data);
    $this->assertTrue(!empty($result['body']['update_spi_definition']), 'SPI result array has expected "update_spi_definition" key.');
  }

  /**
   * Test Acquia SPI update response.
   */
  public function _testAcquiaSpiUpdateResponse() {
    $def_timestamp = \Drupal::config('acquia_connector.settings')->get('spi.def_timestamp');
    $this->assertNotEqual($def_timestamp, 0, 'SPI definition timestamp set');
    $def_vars = \Drupal::config('acquia_connector.settings')->get('spi.def_vars');
    $this->assertTrue(!empty($def_vars), 'SPI definition variable set');
    \Drupal::configFactory()->getEditable('acquia_connector.settings')->set('spi.def_waived_vars', ['test_variable_3'])->save();
    // Test that new variables are in SPI data.
    $spi = new SpiControllerTest();
    $spi_data = $spi->get();
    $vars = Json::decode($spi_data['system_vars']);
    $this->assertTrue(!empty($vars['test_variable_1']), 'New variables included in SPI data');
    $this->assertTrue(!isset($vars['test_variable_3']), 'test_variable_3 not included in SPI data');
  }

  /**
   * Test Acquia SPI set variables.
   */
  public function _testAcquiaSpiSetVariables() {
    // Connect site on non-error key and id.
    $this->connectSite();

    $edit_fields = [
      'name' => $this->acqtestName,
      'machine_name' => $this->acqtestMachineName,
    ];
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settingsPath, $edit_fields, $submit_button);

    $spi = new SpiControllerTest();
    $spi_data = $spi->get();
    $vars = Json::decode($spi_data['system_vars']);
    $this->assertTrue(empty($vars['acquia_spi_saved_variables']['variables']), 'Have not saved any variables');
    // Set error reporting so variable is saved.
    $edit = [
      'error_level' => 'verbose',
    ];
    $this->drupalPostForm('admin/config/development/logging', $edit, 'Save configuration');

    // Turn off error reporting.
    $set_variables = ['error_level' => 'hide'];
    $variables = new VariablesControllerTest();
    $variables->setVariables($set_variables);

    $new = \Drupal::config('system.logging')->get('error_level');
    $this->assertTrue($new === 'hide', 'Set error reporting to log only');
    $vars = Json::decode($variables->getVariablesData());
    $this->assertTrue(in_array('error_level', $vars['acquia_spi_saved_variables']['variables']), 'SPI data reports error level was saved');
    $this->assertTrue(isset($vars['acquia_spi_saved_variables']['time']), 'Set time for saved variables');

    // Attemp to set variable that is not whitelisted.
    $current = \Drupal::config('system.site')->get('name');
    $set_variables = ['site_name' => 0];
    $variables->setVariables($set_variables);
    $after = \Drupal::config('system.site')->get('name');
    $this->assertIdentical($current, $after, 'Non-whitelisted variable cannot be automatically set');
    $vars = Json::decode($variables->getVariablesData());
    $this->assertFalse(in_array('site_name', $vars['acquia_spi_saved_variables']['variables']), 'SPI data does not include anything about trying to save clean url');

    // Test override of approved variable list.
    \Drupal::configFactory()->getEditable('acquia_connector.settings')->set('spi.set_variables_override', FALSE)->save();
    // Variables controller stores old config.
    $variables = new VariablesControllerTest();
    $set_variables = ['acquia_spi_set_variables_automatic' => 'test_variable'];
    $variables->setVariables($set_variables);
    $vars = Json::decode($variables->getVariablesData());
    $this->assertFalse(isset($vars['test_variable']), 'Using default list of approved list of variables');
    \Drupal::configFactory()->getEditable('acquia_connector.settings')->set('spi.set_variables_override', TRUE)->save();
    // Variables controller stores old config.
    $variables = new VariablesControllerTest();
    $set_variables = ['acquia_spi_set_variables_automatic' => 'test_variable'];
    $variables->setVariables($set_variables);
    $vars = Json::decode($variables->getVariablesData());
    $this->assertIdentical($vars['acquia_spi_set_variables_automatic'], 'test_variable', 'Altered approved list of variables that can be set');
  }

  /**
   * Helper function determines whether given array contains PHP object.
   */
  protected function isContainObjects($arr) {
    foreach ($arr as $item) {
      if (is_object($item)) {
        return TRUE;
      }
      if (is_array($item) && $this->isContainObjects($item)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Helper function connects to valid subscription.
   */
  protected function connectSite() {
    $edit_fields = [
      'acquia_identifier' => $this->acqtestId,
      'acquia_key' => $this->acqtestKey,
    ];
    $submit_button = 'Connect';
    $this->drupalPostForm($this->credentialsPath, $edit_fields, $submit_button);
  }

}

/**
 * Class spiControllerTest.
 *
 * @package Drupal\acquia_connector\Tests
 */
class SpiControllerTest extends SpiController {
  protected $client;

  /**
   * Construction method.
   */
  public function __construct() {
    $client = \Drupal::service('acquia_connector.client');
    $this->client = $client;
  }

  /**
   * Gather site profile information about this site.
   *
   * @param string $method
   *   Optional identifier for the method initiating request.
   *   Values could be 'cron' or 'menu callback' or 'drush'.
   *
   * @return array
   *   An associative array keyed by types of information.
   */
  public function get($method = '') {
    return parent::get($method);
  }

  /**
   * Put SPI data in local storage.
   *
   * @param array $data
   *   Keyed array of data to store.
   * @param int $expire
   *   Expire time or null to use default of 1 day.
   */
  public function dataStoreSet($data, $expire = NULL) {
    parent::dataStoreSet($data, $expire);
  }

  /**
   * Get SPI data out of local storage.
   *
   * @param array $keys
   *   Array of keys to extract data for.
   *
   * @return array
   *   Stored data or false if no data is retrievable from storage.
   */
  public function dataStoreGet($keys) {
    return parent::dataStoreGet($keys);
  }

  /**
   * Gather full SPI data and send to Acquia Network.
   *
   * @param string $method
   *   Optional identifier for the method initiating request.
   *   Values could be 'cron' or 'menu callback' or 'drush'.
   *
   * @return mixed
   *   FALSE if data not sent else NSPI result array,
   */
  public function sendFullSpi($method = '') {
    return parent::sendFullSpi($method);
  }

  /**
   * Generate the machine name for acquia hosted sites.
   *
   * @return string
   *   The suggested Acquia Hosted machine name.
   */
  public function getAcquiaHostedMachineName() {
    return parent::getAcquiaHostedMachineName();
  }

  /**
   * Generate the name for acquia hosted sites.
   *
   * @return string
   *   The suggested Acquia Hosted name.
   */
  public function getAcquiaHostedName() {
    return parent::getAcquiaHostedName();
  }

}

/**
 * Class VariablesControllerTest.
 *
 * @package Drupal\acquia_connector\Tests
 */
class VariablesControllerTest extends VariablesController {

  /**
   * {@inheritdoc}
   */
  public function setVariables($set_variables) {
    parent::setVariables($set_variables);
  }

  /**
   * {@inheritdoc}
   */
  public function getVariablesData() {
    return parent::getVariablesData();
  }

}
