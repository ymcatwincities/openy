<?php

/**
 * @file
 * Definition of Drupal\acquia_connector\Tests\AcquiaConnectorModuleTest.
 */

namespace Drupal\acquia_connector\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\acquia_connector\Subscription;
use Drupal\acquia_connector\Controller\StatusController;

/**
 * Tests the functionality of the Acquia Connector module.
 *
 * @group Acquia connector
 */
class AcquiaConnectorModuleTest extends WebTestBase {
  protected $strictConfigSchema = FALSE;

  protected $acqtest_email        = 'TEST_networkuser@example.com';
  protected $acqtest_pass         = 'TEST_password';
  protected $acqtest_id           = 'TEST_AcquiaConnectorTestID';
  protected $acqtest_key          = 'TEST_AcquiaConnectorTestKey';
  protected $acqtest_name         = 'test name';
  protected $acqtest_machine_name = 'test_name';
  protected $acqtest_expired_id   = 'TEST_AcquiaConnectorTestIDExp';
  protected $acqtest_expired_key  = 'TEST_AcquiaConnectorTestKeyExp';
  protected $acqtest_503_id       = 'TEST_AcquiaConnectorTestID503';
  protected $acqtest_503_key      = 'TEST_AcquiaConnectorTestKey503';
  protected $acqtest_error_id     = 'TEST_AcquiaConnectorTestIDErr';
  protected $acqtest_error_key    = 'TEST_AcquiaConnectorTestKeyErr';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('acquia_connector', 'toolbar', 'acquia_connector_test', 'node');

  /**
   *{@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    global $base_url;
    // Create and log in our privileged user.
    $this->privileged_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'access administration pages',
      'access toolbar',
    ));
    $this->drupalLogin($this->privileged_user);

    // Create a user that has a Network subscription.
    $this->network_user = $this->drupalCreateUser();
    $this->network_user->mail = $this->acqtest_email;
    $this->network_user->pass = $this->acqtest_pass;
    $this->network_user->save();
    //$this->drupalLogin($this->network_user);
    //Setup variables.
    $this->cloud_free_url = 'https://www.acquia.com/acquia-cloud-free';
    $this->setup_path = 'admin/config/system/acquia-connector/setup';
    $this->credentials_path = 'admin/config/system/acquia-connector/credentials';
    $this->settings_path = 'admin/config/system/acquia-connector';
    $this->migrate_path = 'admin/config/system/acquia-agent/migrate';
    $this->environment_change_path = '/admin/config/system/acquia-connector/environment-change';
    $this->status_report_url = 'admin/reports/status';
    $this->base_url = $base_url;

    \Drupal::configFactory()->getEditable('acquia_connector.settings')->set('spi.server', $base_url)->save();
    \Drupal::configFactory()->getEditable('acquia_connector.settings')->set('spi.ssl_verify', FALSE)->save();
    \Drupal::configFactory()->getEditable('acquia_connector.settings')->set('spi.ssl_override', TRUE)->save();
  }

  /**
   * Helper function for storing UI strings.
   */
  private function acquiaConnectorStrings($id) {
    switch ($id) {
      case 'free':
        return 'Sign up for Acquia Cloud Free, a free Drupal sandbox to experiment with new features, test your code quality, and apply continuous integration best practices.';
      case 'get-connected':
        return 'If you have an Acquia Subscription, connect now. Otherwise, you can turn this message off by disabling the Acquia Connector modules.';
      case 'enter-email':
        return 'Enter the email address you use to login to the Acquia Subscription';
      case 'enter-password':
        return 'Enter your Acquia Subscription password';
      case 'account-not-found':
        return 'Account not found';
      case 'id-key':
        return 'Enter your identifier and key from your subscriptions overview or log in to connect your site to the Acquia Subscription.';
      case 'enter-key':
        return 'Network key';
      case 'subscription-not-found':
        return 'Error: Subscription not found (1000)';
      case 'saved':
        return 'The configuration options have been saved.';
      case 'subscription':
        return 'Subscription: ' . $this->acqtest_id; // Assumes subscription name is same as id.
      case 'migrate':
        return 'Transfer a fully-functional copy of your site to Acquia Cloud.';
      case 'migrate-hosting-404':
        return 'Error: Hosting not available under your subscription. Upgrade your subscription to continue with import.';
      case 'migrate-select-environments':
        return 'Select environment for migration';
      case 'migrate-files-label':
        return 'Migrate files directory';
      case 'menu-active':
        return 'Subscription active (expires 2023/10/8)';
      case 'menu-inactive':
        return 'Subscription not active';
      case 'site-name-required':
        return 'Name field is required.';
      case 'site-machine-name-required':
        return 'Machine name field is required.';
      case 'first-connection':
        return 'This is the first connection from this site, it may take awhile for it to appear on the Acquia Network.';
    }
  }

  /**
   * Test get connected.
   */
  public function testAcquiaConnectorGetConnected() {
    // Check for call to get connected.
    $this->drupalGet('admin');
    $this->assertText($this->acquiaConnectorStrings('free'), 'The explanation of services text exists');
    $this->assertLinkByHref($this->cloud_free_url, 0, 'Link to Acquia.com Cloud Services exists');
    $this->assertText($this->acquiaConnectorStrings('get-connected'), 'The call-to-action to connect text exists');
    $this->assertLink('connect now', 0, 'The "connect now" link exists');

    // Check connection setup page.
    $this->drupalGet($this->setup_path);
    $this->assertText($this->acquiaConnectorStrings('enter-email'), 'The email address field label exists');
    $this->assertText($this->acquiaConnectorStrings('enter-password'), 'The password field label exists');
    $this->assertLinkByHref($this->cloud_free_url, 0, 'Link to Acquia.com free signup exists');

    // Check errors on automatic setup page.
    $edit_fields = array(
      'email' => $this->randomString(),
      'pass' => $this->randomString(),
    );
    $submit_button = 'Next';
    $this->drupalPostForm($this->setup_path, $edit_fields, $submit_button);
    $this->assertText($this->acquiaConnectorStrings('account-not-found'), 'Account not found for random automatic setup attempt');
    $this->assertText($this->acquiaConnectorStrings('menu-inactive'), 'Subscription not active menu message appears');

    // Check manual connection.
    $this->drupalGet($this->credentials_path);
    $this->assertText($this->acquiaConnectorStrings('id-key'), 'The network key and id description exists');
    $this->assertText($this->acquiaConnectorStrings('enter-key'), 'The network key field label exists');
    $this->assertLinkByHref($this->cloud_free_url, 0, 'Link to Acquia.com free signup exists');

    // Check errors on connection page.
    $edit_fields = array(
      'acquia_identifier' => $this->randomString(),
      'acquia_key' => $this->randomString(),
    );
    $submit_button = 'Connect';
    $this->drupalPostForm($this->credentials_path, $edit_fields, $submit_button);
    $this->assertText($this->acquiaConnectorStrings('subscription-not-found'), 'Subscription not found for random credentials');
    $this->assertText($this->acquiaConnectorStrings('menu-inactive'), 'Subscription not active menu message appears');

    // Connect site on key and id.
    $edit_fields = array(
      'acquia_identifier' => $this->acqtest_id,
      'acquia_key' => $this->acqtest_key,
    );
    $submit_button = 'Connect';
    $this->drupalPostForm($this->credentials_path, $edit_fields, $submit_button);
    $this->drupalGet($this->settings_path);
    $this->assertText($this->acquiaConnectorStrings('subscription'), 'Subscription connected with key and identifier');
    $this->assertLinkByHref($this->setup_path, 0, 'Link to change subscription exists');
    $this->assertText($this->acquiaConnectorStrings('migrate'), 'Acquia Cloud Migrate description exists');

    // Connect via automatic setup.
    \Drupal::configFactory()->getEditable('acquia_connector.settings')->clear('identifier')->save();
    \Drupal::configFactory()->getEditable('acquia_connector.settings')->clear('key')->save();
    $edit_fields = array(
      'email' => $this->acqtest_email,
      'pass' => $this->acqtest_pass,
    );
    $submit_button = 'Next';
    $this->drupalPostForm($this->setup_path, $edit_fields, $submit_button);
    $this->drupalGet($this->setup_path);
    $this->drupalGet($this->settings_path);
    $this->assertText($this->acquiaConnectorStrings('subscription'), 'Subscription connected with credentials');
    // Confirm menu reports active subscription.
    $this->drupalGet('admin');
    $this->assertText($this->acquiaConnectorStrings('menu-active'), 'Subscription active menu message appears');

    // Check errors if name or machine name empty.
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settings_path, array(), $submit_button);
    $this->assertText($this->acquiaConnectorStrings('site-name-required'), 'Name is required message appears');
    $this->assertText($this->acquiaConnectorStrings('site-machine-name-required'), 'Machine name is required message appears');

    // Acquia hosted sites.
    $edit_fields = array(
      'acquia_dynamic_banner' => TRUE,
      'name' => 'test_name',
      'machine_name' => 'test_name',
    );
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settings_path, $edit_fields, $submit_button);
    $this->assertFieldChecked('edit-acquia-dynamic-banner', '"Receive updates from Acquia" option stays saved');

    // Test acquia hosted site.
    $settings['_SERVER']['AH_SITE_NAME'] = (object) [
      'value' => 'acqtest_drupal',
      'required' => TRUE,
    ];
    $settings['_SERVER']['AH_SITE_ENVIRONMENT'] = (object) [
      'value' => 'dev',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    sleep(10);
    $this->drupalGet($this->settings_path);
    $elements = $this->xpath('//input[@name=:name]', array(':name' => 'name'));
    foreach ($elements as $element) {
      $this->assertIdentical((string) $element['disabled'], 'disabled', 'Name field is disabled.');
    }

  }

  /**
   * Test Connector subscription methods.
   */
  public function testAcquiaConnectorSubscription() {
    // Starts as inactive.
    $is_active = Subscription::isActive();
    $this->assertFalse($is_active, 'Subscription is not currently active.');
    // Confirm HTTP request count is 0 because without credentials no request
    // should have been made.
    $this->assertIdentical(\Drupal::state()->get('acquia_connector_test_request_count', 0), 0);
    $check_subscription  = Subscription::update();
    \Drupal::state()->resetCache();
    $this->assertFalse($check_subscription, 'Subscription is currently false.');
    // Confirm HTTP request count is still 0.
    $this->assertIdentical(\Drupal::state()->get('acquia_connector_test_request_count', 0), 0);

    // Fail a connection.
    $random_id = $this->randomString();
    $edit_fields = array(
      'acquia_identifier' => $random_id,
      'acquia_key' => $this->randomString(),
    );
    $submit_button = 'Connect';
    $this->drupalPostForm($this->credentials_path, $edit_fields, $submit_button);

    // Confirm HTTP request count is 1.
    $this->assertIdentical(\Drupal::state()->get('acquia_connector_test_request_count', 0), 1, 'Made 1 HTTP request in attempt to connect subscription.');
    $is_active = Subscription::isActive();
    $this->assertFalse($is_active, 'Subscription is not active after failed attempt to connect.');
    $this->assertIdentical(\Drupal::state()->get('acquia_connector_test_request_count', 0), 1, 'Still have made only 1 HTTP request');
    $check_subscription  = Subscription::update();
    \Drupal::state()->resetCache();
    $this->assertFalse($check_subscription, 'Subscription is false after failed attempt to connect.');
    $this->assertIdentical(\Drupal::state()->get('acquia_connector_test_request_count', 0), 1, 'Still have made only 1 HTTP request');
    // Test default from acquia_agent_settings().
    $stored = \Drupal::config('acquia_connector.settings');
    $current_subscription = $stored->get('subscription_data');
    // Not identical since acquia_agent_has_credentials() causes stored to be
    // deleted.
    $this->assertNotIdentical($check_subscription, $current_subscription, 'Stored subscription data not same before connected subscription.');
    $this->assertTrue($current_subscription['active'] === FALSE, 'Default is inactive.');

    // Reset HTTP request counter;
    \Drupal::state()->set('acquia_connector_test_request_count', 0);

    // Connect.
    $edit_fields = array(
      'acquia_identifier' => $this->acqtest_id,
      'acquia_key' => $this->acqtest_key,
    );
    $this->drupalPostForm($this->credentials_path, $edit_fields, $submit_button);
    // HTTP requests should now be 3 (acquia.agent.subscription.name and
    //acquia.agent.subscription and acquia.agent.validate.
    $this->assertIdentical(\Drupal::state()->get('acquia_connector_test_request_count', 0), 3, '3 HTTP requests were made during first connection.');
    $is_active = Subscription::isActive();
    $this->assertTrue($is_active, 'Subscription is active after successful connection.');
    $check_subscription = Subscription::update();
    \Drupal::state()->resetCache();
    $this->assertTrue(is_array($check_subscription), 'Subscription is array after successful connection.');

    // Now stored subscription data should match.
    $stored = \Drupal::config('acquia_connector.settings');
    $this->assertIdentical(\Drupal::state()->get('acquia_connector_test_request_count', 0), 4, '1 additional HTTP request made via acquia_agent_check_subscription().');
    $this->drupalGet($this->base_url);
    $this->drupalGet('admin');
    $this->assertIdentical(\Drupal::state()->get('acquia_connector_test_request_count', 0), 4, 'No extra requests made during visits to other pages.');

    // Reset HTTP request counter;
    \Drupal::state()->set('acquia_connector_test_request_count', 0);
    // Connect on expired subscription.
    $edit_fields = array(
      'acquia_identifier' => $this->acqtest_expired_id,
      'acquia_key' => $this->acqtest_expired_key,
    );
    $this->drupalPostForm($this->credentials_path, $edit_fields, $submit_button);
    $this->assertIdentical(\Drupal::state()->get('acquia_connector_test_request_count', 0), 3, '3 HTTP requests were made during expired connection attempt.');
    $is_active = Subscription::isActive();
    $this->assertFalse($is_active, 'Subscription is not active after connection with expired subscription.');
    $this->assertIdentical(\Drupal::state()->get('acquia_connector_test_request_count', 0), 3, 'No additional HTTP requests made via acquia_agent_subscription_is_active().');
    $this->drupalGet($this->base_url);
    $this->drupalGet('admin');
    $this->assertIdentical(\Drupal::state()->get('acquia_connector_test_request_count', 0), 3, 'No HTTP requests made during visits to other pages.');

    // Stored subscription data will now be the expired integer.
    $check_subscription = Subscription::update();
    \Drupal::state()->resetCache();

    $this->assertIdentical($check_subscription, 1200, 'Subscription is expired after connection with expired subscription.');
    $this->assertIdentical(\Drupal::state()->get('acquia_connector_test_request_count', 0), 4, '1 additional request made via acquia_agent_check_subscription().');
    $stored = \Drupal::config('acquia_connector.settings');
    $current_subscription = $stored->get('subscription_data');
    $this->assertIdentical($check_subscription, $current_subscription, 'Stored expected subscription data.');

    // Reset HTTP request counter;
    \Drupal::state()->set('acquia_connector_test_request_count', 0);
    // Connect on subscription that will trigger a 503 response..
    $edit_fields = array(
      'acquia_identifier' => $this->acqtest_503_id,
      'acquia_key' => $this->acqtest_503_key,
    );
    $this->drupalPostForm($this->credentials_path, $edit_fields, $submit_button);
    $is_active = Subscription::isActive();
    $this->assertTrue($is_active, 'Subscription is active after successful connection.');
    // Make another request which will trigger 503 server error.
    $check_subscription = Subscription::update();
    \Drupal::state()->resetCache();

    // Hold onto subcription data for comparison.
    $stored = \Drupal::config('acquia_connector.settings');
    $this->assertNotIdentical($check_subscription, '503', 'Subscription is not storing 503.');
    $this->assertTrue(is_array($check_subscription), 'Storing subscription array data.');
    $this->assertIdentical(\Drupal::state()->get('acquia_connector_test_request_count', 0), 4, 'Have made 4 HTTP requests so far.');
  }

  /**
   * Test Migrate methods.
   */
  public function testAcquiaConnectorCloudMigrate() {
    // Connect site on pair that will trigger an error for migration.
    $edit_fields = array(
      'acquia_identifier' => $this->acqtest_error_id,
      'acquia_key' => $this->acqtest_error_key,
    );
    $submit_button = 'Connect';
    $this->drupalPostForm($this->credentials_path, $edit_fields, $submit_button);
    $this->drupalGet($this->migrate_path);
    $this->assertText($this->acquiaConnectorStrings('migrate-hosting-404'), 'Cannot migrate when hosting not enabled on subscription.');
    // Connect with correct pair.
    $edit_fields = array(
      'acquia_identifier' => $this->acqtest_id,
      'acquia_key' => $this->acqtest_key,
    );
    $this->drupalPostForm($this->credentials_path, $edit_fields, $submit_button);
    $this->drupalGet($this->migrate_path);
    $this->assertNoText($this->acquiaConnectorStrings('migrate-hosting-404'), 'Did not get "cannot migrate" text.');
    $this->assertText($this->acquiaConnectorStrings('migrate-select-environments'), 'Environment selection label appears.');
    $this->assertText($this->acquiaConnectorStrings('migrate-files-label'), 'The files label controls do appear.');

    \Drupal::state()->set('migrate.cloud', 'test');
    $this->drupalGet($this->migrate_path);
    $this->assertText($this->acquiaConnectorStrings('migrate-files-label'), 'The files label controls do appear after setting the migration variable.');
    $edit_fields = array(
      'environment' => 'dev',
      'migrate_files' => FALSE,
    );
    $submit_button = 'Migrate';
    $this->drupalPostForm($this->migrate_path, $edit_fields, $submit_button);
    $this->drupalGet($this->migrate_path);
    $this->assertNoFieldChecked('edit-migrate-files', "The migrate files checkbox is not checked.");
  }


  /**
   * Tests the site status callback.
   */
  public function testAcquiaConnectorSiteStatus() {
    $uuid = '0dee0d07-4032-44ea-a2f2-84182dc10d54';
    $test_url = "https://insight.acquia.com/node/uuid/{$uuid}/dashboard";

    $test_data = array(
      'active' => 1,
      'href' => $test_url,
    );
    // Set some sample test data.
    \Drupal::configFactory()->getEditable('acquia_connector.settings')->set('subscription_data', $test_data)->save();
    // Test StatusControllerTest::getIdFromSub
    $getIdFromSub = new StatusControllerTest();
    $key = $getIdFromSub->getIdFromSub($test_data);
    $this->assertIdentical($key, $uuid);

    // Add a 'uuid' key to the data and make sure that is returned.
    $test_data['uuid'] = $uuid;
    $test_data['href'] = 'http://example.com';

    $key = $getIdFromSub->getIdFromSub($test_data);
    $this->assertIdentical($key, $uuid);

    $query = array(
      'key' => hash('sha1', "{$key}:test"),
      'nonce' => 'test',
    );
    $json = $this->drupalGetAJAX('system/acquia-connector-status', array('query' => $query));

    // Test the version.
    $this->assertIdentical($json['version'], '1.0', 'Correct API version found.');

    // Test invalid query string parameters for access.
    // A random key value should fail.
    $query['key'] = $this->randomString(16);
    $this->drupalGetAJAX('system/acquia-connector-status', array('query' => $query));
    $this->assertResponse(403);
  }

  /**
   * Tests the SPI change form.
   */
  public function testSPIChangeForm() {
    // Connect site on key and id.
    $edit_fields = array(
      'acquia_identifier' => $this->acqtest_id,
      'acquia_key' => $this->acqtest_key,
    );
    $submit_button = 'Connect';
    $this->drupalPostForm($this->credentials_path, $edit_fields, $submit_button);
    $this->drupalGet($this->settings_path);
    $this->assertText($this->acquiaConnectorStrings('subscription'), 'Subscription connected with key and identifier');

    // No changes detected.
    $edit_fields = array(
      'acquia_dynamic_banner' => TRUE,
      'name' => $this->acqtest_name,
      'machine_name' => $this->acqtest_machine_name,
    );

    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settings_path, $edit_fields, $submit_button);
    $this->assertText($this->acquiaConnectorStrings('saved'), 'The configuration options have been saved.');

    $this->drupalGet($this->status_report_url);
    $this->clickLink('manually send SPI data');

    $this->drupalGet($this->environment_change_path);
    $this->assertText('No changes detected', 'No changes are currently detected.');

    // Detect Changes.
    $edit_fields = array(
      'acquia_dynamic_banner' => TRUE,
      'name' => $this->acqtest_name,
      'machine_name' => $this->acqtest_machine_name . '_change',
    );

    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settings_path, $edit_fields, $submit_button);
    $this->assertText($this->acquiaConnectorStrings('saved'), 'The configuration options have been saved.');

    $this->assertText('A change has been detected in your site environment. Please check the Acquia SPI status on your Status Report page for more information', 'Changes have been detected');
    $this->drupalGet($this->environment_change_path);

    // Check environment change action.
    $elements = $this->xpath('//input[@name=:name]', array(':name' => 'env_change_action'));
    $expected_values = array('block', 'update', 'create');
    foreach ($elements as $element) {
      $expected = array_shift($expected_values);
      $this->assertIdentical((string) $element['value'], $expected);
    }

    // Test "block" the connector from sending data to NSPI.
    $edit_fields = array(
      'env_change_action' => 'block',
    );

    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->environment_change_path, $edit_fields, $submit_button);

    $this->assertText('This site has been blocked from sending profile data to Acquia Cloud.');
    $this->assertText('You have blocked your site from sending data to Acquia Cloud.');

    // Test unblock site.
    $this->clickLink('Unblock this site');
    $this->assertText('The Acquia Connector is blocked and is not sending site profile data to Acquia Cloud for evaluation.');

    $edit_fields = array(
      'env_change_action[unblock]' => TRUE,
    );
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->environment_change_path, $edit_fields, $submit_button);
    $this->assertText('Your site has been unblocked and is sending data to Acquia Cloud.');
    $this->clickLink('manually send SPI data');
    $this->assertText('A change has been detected in your site environment. Please check the Acquia SPI status on your Status Report page for more information.');

    // Test update existing site.
    $this->clickLink('confirm the action you wish to take');
    $edit_fields = array(
      'env_change_action' => 'update',
    );
    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->environment_change_path, $edit_fields, $submit_button);

    // Test new site in Acquia Cloud.
    $edit_fields = array(
      'acquia_dynamic_banner' => TRUE,
      'name' => $this->acqtest_name,
      'machine_name' => $this->acqtest_machine_name,
    );

    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->settings_path, $edit_fields, $submit_button);
    $this->assertText($this->acquiaConnectorStrings('saved'), 'The configuration options have been saved.');
    $this->assertText('A change has been detected in your site environment. Please check the Acquia SPI status on your Status Report page for more information.');
    $this->drupalGet($this->status_report_url);
    $this->clickLink('confirm the action you wish to take');

    $edit_fields = array(
      'env_change_action' => 'create',
      'name' => '',
      'machine_name' => ''
    );

    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->environment_change_path, $edit_fields, $submit_button);
    $this->assertText($this->acquiaConnectorStrings('site-name-required'), 'Name field is required.');
    $this->assertText($this->acquiaConnectorStrings('site-machine-name-required'), 'Machine name field is required.');

    $edit_fields = array(
      'env_change_action' => 'create',
      'name' => $this->acqtest_name,
      'machine_name' => $this->acqtest_machine_name,
    );

    $submit_button = 'Save configuration';
    $this->drupalPostForm($this->environment_change_path, $edit_fields, $submit_button);
    $this->assertText($this->acquiaConnectorStrings('first-connection'), 'First connection from this site');
  }

}

/**
 * Class StatusControllerTest
 * @package Drupal\acquia_connector\Tests
 */
class StatusControllerTest extends StatusController {
  public function getIdFromSub($sub_data) {
    return parent::getIdFromSub($sub_data);
  }
}
