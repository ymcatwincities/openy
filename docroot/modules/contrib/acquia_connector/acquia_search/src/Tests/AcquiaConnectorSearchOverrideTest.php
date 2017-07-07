<?php

namespace Drupal\acquia_search\Tests;

use Drupal\acquia_connector\Helper\Storage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the automatic switching behavior of the Acquia Search module.
 *
 * @group Acquia search
 */
class AcquiaConnectorSearchOverrideTest extends WebTestBase {
  protected $strictConfigSchema = FALSE;
  protected $id;
  protected $key;
  protected $salt;
  protected $server;
  protected $index;


  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'acquia_connector',
    'search_api',
    'search_api_solr',
    'toolbar',
    'acquia_connector_test',
    'node',
    'acquia_search_test',
  ];


  /**
   * {@inheritdoc}
   */
  public function setUp() {

    parent::setUp();
    // Generate and store a random set of credentials.
    $this->id =  'TEST_AcquiaConnectorTestID';
    $this->key = 'TEST_AcquiaConnectorTestKey';
    $this->salt = $this->randomString(32);
    $this->server = 'acquia_search_server';
    $this->index = 'acquia_search_index';

    // Create a new content type.
    $content_type = $this->drupalCreateContentType();

    // Add a node of the new content type.
    $node_data = array(
      'type' => $content_type->id(),
    );

    $this->drupalCreateNode($node_data);
    $this->_connect();
    $this->_setAvailableSearchCores();

  }


  /**
   * Main function that calls the rest of the tests (names start with case*)
   */
  public function testOverrides() {

    $this->caseNonAcquiaHosted();
    $this->caseAcquiaHostingEnvironmentDetected();
    $this->caseAcquiaHostingEnvironmentDetectedNoAvailableCores();
    $this->caseAcquiaHostingProdEnvironmentDetectedWithoutProdFlag();
    $this->caseAcquiaHostingProdEnvironmentDetectedWithProdFlag();

  }


  /**
   * No Acquia hosting and DB detected - should override into Readonly.
   */
  public function caseNonAcquiaHosted() {

    $this->drupalGet('/admin/config/search/search-api/server/' . $this->server);

    $this->assertText('automatically enforced read-only mode on this connection.');

    $delete_btn = $this->xpath('//input[@value="Delete all indexed data on this server"]');
    $this->assertEqual((string) $delete_btn[0]['disabled'], 'disabled');

    $this->drupalGet('/admin/config/search/search-api/index/' . $this->index);

    $this->assertText('automatically enforced read-only mode on this connection.');

  }


  /**
   * Acquia Dev hosting environment detected - configs point to the index on the
   * Dev environment.
   */
  public function caseAcquiaHostingEnvironmentDetected() {

    $overrides = [
      'env-overrides' => 1,
      'AH_SITE_ENVIRONMENT' => 'dev',
      'AH_SITE_NAME' => 'testsite1dev',
      'AH_SITE_GROUP' => 'testsite1',
    ];

    $this->drupalGet('/admin/config/search/search-api/server/' . $this->server, ['query' => $overrides ]);

    $this->assertNoText('automatically enforced read-only mode on this connection.');
    $this->assertNoText('The following Acquia Search Solr index IDs would have worked for your current environment');

    $delete_btn = $this->xpath('//input[@value="Delete all indexed data on this server"]');
    $this->assertNotEqual((string) $delete_btn[0]['disabled'], 'disabled');

    $this->drupalGet('/admin/config/search/search-api/index/' . $this->index, ['query' => $overrides ]);

    $this->assertNoText('automatically enforced read-only mode on this connection.');
    $this->assertNoText('The following Acquia Search Solr index IDs would have worked for your current environment');

  }


  /**
   * Acquia Test environment and a DB name. According to the mock, no cores
   * available for the Test environment so it is read only.
   */
  public function caseAcquiaHostingEnvironmentDetectedNoAvailableCores() {

    $overrides = [
      'env-overrides' => 1,
      'AH_SITE_ENVIRONMENT' => 'test',
      'AH_SITE_NAME' => 'testsite1test',
      'AH_SITE_GROUP' => 'testsite1',
    ];

    $this->drupalGet('/admin/config/search/search-api/server/' . $this->server, ['query' => $overrides ]);

    $this->assertText('automatically enforced read-only mode on this connection.');

    $this->assertText('The following Acquia Search Solr index IDs would have worked for your current environment');
    $this->assertText($this->id . '.test.' . $this->_getDbName());
    $this->assertText($this->id . '.test.' . $this->_getSiteFolderName());

    $delete_btn = $this->xpath('//input[@value="Delete all indexed data on this server"]');
    $this->assertEqual((string) $delete_btn[0]['disabled'], 'disabled');

    $this->drupalGet('/admin/config/search/search-api/index/' . $this->index, ['query' => $overrides ]);

    // On index edit page, check the read-only mode state.
    $this->assertText('automatically enforced read-only mode on this connection.');

  }


  /**
   * Acquia Prod environment and a DB name but AH_PRODUCTION isn't set - so read
   * only.
   */
  public function caseAcquiaHostingProdEnvironmentDetectedWithoutProdFlag() {

    $overrides = [
      'env-overrides' => 1,
      'AH_SITE_ENVIRONMENT' => 'prod',
      'AH_SITE_NAME' => 'testsite1prod',
      'AH_SITE_GROUP' => 'testsite1',
    ];

    $this->drupalGet('/admin/config/search/search-api/server/' . $this->server, ['query' => $overrides ]);

    $this->assertText('automatically enforced read-only mode on this connection.');

    $this->assertText('The following Acquia Search Solr index IDs would have worked for your current environment');
    $this->assertText($this->id . '.prod.' . $this->_getDbName());
    $this->assertText($this->id . '.prod.' . $this->_getSiteFolderName());

    $delete_btn = $this->xpath('//input[@value="Delete all indexed data on this server"]');
    $this->assertEqual((string) $delete_btn[0]['disabled'], 'disabled');

    $this->drupalGet('/admin/config/search/search-api/index/' . $this->index, ['query' => $overrides ]);

    $this->assertText('automatically enforced read-only mode on this connection.');

  }


  /**
   * Acquia Prod environment and a DB name and AH_PRODUCTION is set - so it
   * should override to connect to the prod index.
   */
  public function caseAcquiaHostingProdEnvironmentDetectedWithProdFlag() {

    $overrides = [
      'env-overrides' => 1,
      'AH_SITE_ENVIRONMENT' => 'prod',
      'AH_SITE_NAME' => 'testsite1prod',
      'AH_SITE_GROUP' => 'testsite1',
      'AH_PRODUCTION' => 1,
    ];

    $this->drupalGet('/admin/config/search/search-api/server/' . $this->server, ['query' => $overrides ]);

    $this->assertNoText('automatically enforced read-only mode on this connection.');
    $this->assertNoText('The following Acquia Search Solr index IDs would have worked for your current environment');

    $delete_btn = $this->xpath('//input[@value="Delete all indexed data on this server"]');
    $this->assertNotEqual((string) $delete_btn[0]['disabled'], 'disabled');

    $this->drupalGet('/admin/config/search/search-api/index/' . $this->index, ['query' => $overrides ]);

    $this->assertNoText('automatically enforced read-only mode on this connection.');
    $this->assertNoText('The following Acquia Search Solr index IDs would have worked for your current environment');

  }

  /**
   * Connect to the Acquia Subscription.
   */
  public function _connect() {
    global $base_url;
    \Drupal::configFactory()->getEditable('acquia_connector.settings')->set('spi.ssl_verify', FALSE)->save();
    \Drupal::configFactory()->getEditable('acquia_connector.settings')->set('spi.ssl_override', TRUE)->save();
    \Drupal::configFactory()->getEditable('acquia_connector.settings')->set('spi.server', $base_url)->save();

    $admin_user = $this->_createAdminUser();
    $this->drupalLogin($admin_user);

    $edit_fields = array(
      'acquia_identifier' => $this->id,
      'acquia_key' => $this->key,
    );

    $submit_button = 'Connect';
    $this->drupalPostForm('admin/config/system/acquia-connector/credentials', $edit_fields, $submit_button);

    \Drupal::service('module_installer')->install(array('acquia_search'));
    drupal_flush_all_caches();

  }


  /**
   * Creates an admin user.
   */
  public function _createAdminUser() {

    $permissions = array(
      'administer site configuration',
      'access administration pages',
      'access toolbar',
      'administer search_api',
    );
    return $this->drupalCreateUser($permissions);

  }


  /**
   * Sets available search cores into the subscription heartbeat data.
   *
   * @param bool $no_db_flag
   *   Allows to set a limited number of search cores by excluding the one that
   *   contains the DB name.
   */
  public function _setAvailableSearchCores($no_db_flag = FALSE) {

    $acquia_identifier = $this->id;
    $solr_hostname = 'mock.acquia-search.com';
    $site_folder = $this->_getSiteFolderName();
    $ah_db_name = $this->_getDbName();

    $core_with_folder_name = array(
      'balancer' => $solr_hostname,
      'core_id' => "{$acquia_identifier}.dev.{$site_folder}"
    );

    $core_with_db_name = array(
      'balancer' => $solr_hostname,
      'core_id' => "{$acquia_identifier}.dev.{$ah_db_name}"
    );

    $core_with_acquia_identifier = array(
      'balancer' => $solr_hostname,
      'core_id' => "{$acquia_identifier}"
    );

    if ($no_db_flag) {
      $available_cores = array(
        $core_with_folder_name,
        $core_with_acquia_identifier,
      );
    }
    else {
      $available_cores = array(
        $core_with_folder_name,
        $core_with_db_name,
        $core_with_acquia_identifier,
      );
    }

    $storage = new Storage();
    $storage->setIdentifier($acquia_identifier);

    $subscription = \Drupal::config('acquia_connector.settings')->get('subscription_data');
    $subscription['heartbeat_data'] = array('search_cores' => $available_cores);

    \Drupal::configFactory()->getEditable('acquia_connector.settings')
      ->set('subscription_data', $subscription)->save();

  }


  /**
   * Returns the folder name of the current site folder.
   */
  public function _getSiteFolderName() {

    $conf_path = \Drupal::service('site.path');
    return substr($conf_path, strrpos($conf_path, '/') + 1);

  }


  /**
   * Returns the current DB name.
   */
  public function _getDbName() {

    $db_conn_options = \Drupal\Core\Database\Database::getConnection()->getConnectionOptions();
    return $db_conn_options['database'];

  }

}
