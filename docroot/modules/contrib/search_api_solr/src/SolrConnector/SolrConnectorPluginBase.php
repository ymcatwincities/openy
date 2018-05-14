<?php

namespace Drupal\search_api_solr\SolrConnector;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Url;
use Drupal\search_api\Plugin\ConfigurablePluginBase;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api_solr\SearchApiSolrException;
use Drupal\search_api_solr\SolrConnectorInterface;
use Solarium\Client;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Client\Request;
use Solarium\Core\Client\Response;
use Solarium\Core\Query\Helper;
use Solarium\Core\Query\QueryInterface;
use Solarium\Exception\HttpException;
use Solarium\QueryType\Extract\Result as ExtractResult;
use Solarium\QueryType\Update\Query\Query as UpdateQuery;
use Solarium\QueryType\Select\Query\Query;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base class for Solr connector plugins.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. These definition arrays may be altered through
 * hook_search_api_solr_connector_info_alter(). The definition includes the
 * following keys:
 * - id: The unique, system-wide identifier of the backend class.
 * - label: The human-readable name of the backend class, translated.
 * - description: A human-readable description for the backend class,
 *   translated.
 *
 * A complete plugin definition should be written as in this example:
 *
 * @code
 * @SolrConnector(
 *   id = "my_connector",
 *   label = @Translation("My connector"),
 *   description = @Translation("Authenticates with SuperAuth™.")
 * )
 * @endcode
 *
 * @see \Drupal\search_api_solr\Annotation\SolrConnector
 * @see \Drupal\search_api_solr\SolrConnector\SolrConnectorPluginManager
 * @see \Drupal\search_api_solr\SolrConnectorInterface
 * @see plugin_api
 */
abstract class SolrConnectorPluginBase extends ConfigurablePluginBase implements SolrConnectorInterface, PluginFormInterface {

  use PluginFormTrait {
    submitConfigurationForm as traitSubmitConfigurationForm;
  }

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * A connection to the Solr server.
   *
   * @var \Solarium\Client
   */
  protected $solr;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $plugin->eventDispatcher = $container->get('event_dispatcher');

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'scheme' => 'http',
      'host' => 'localhost',
      'port' => '8983',
      'path' => '/solr',
      'core' => '',
      'timeout' => 5,
      'index_timeout' => 5,
      'optimize_timeout' => 10,
      'solr_version' => '',
      'http_method' => 'AUTO',
      'commit_within' => 1000,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['scheme'] = array(
      '#type' => 'select',
      '#title' => $this->t('HTTP protocol'),
      '#description' => $this->t('The HTTP protocol to use for sending queries.'),
      '#default_value' => isset($this->configuration['scheme']) ? $this->configuration['scheme'] : 'http',
      '#options' => array(
        'http' => 'http',
        'https' => 'https',
      ),
    );

    $form['host'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Solr host'),
      '#description' => $this->t('The host name or IP of your Solr server, e.g. <code>localhost</code> or <code>www.example.com</code>.'),
      '#default_value' => isset($this->configuration['host']) ? $this->configuration['host'] : '',
      '#required' => TRUE,
    );

    $form['port'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Solr port'),
      '#description' => $this->t('The Jetty example server is at port 8983, while Tomcat uses 8080 by default.'),
      '#default_value' => isset($this->configuration['port']) ? $this->configuration['port'] : '',
      '#required' => TRUE,
    );

    $form['path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Solr path'),
      '#description' => $this->t('The path that identifies the Solr instance to use on the server.'),
      '#default_value' => isset($this->configuration['path']) ? $this->configuration['path'] : '',
    );

    $form['core'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Solr core'),
      '#description' => $this->t('The name that identifies the Solr core to use on the server.'),
      '#default_value' => isset($this->configuration['core']) ? $this->configuration['core'] : '',
    );

    $form['timeout'] = array(
      '#type' => 'number',
      '#min' => 1,
      '#max' => 180,
      '#title' => $this->t('Query timeout'),
      '#description' => $this->t('The timeout in seconds for search queries sent to the Solr server.'),
      '#default_value' => isset($this->configuration['timeout']) ? $this->configuration['timeout'] : 5,
      '#required' => TRUE,
    );

    $form['index_timeout'] = array(
      '#type' => 'number',
      '#min' => 1,
      '#max' => 180,
      '#title' => $this->t('Index timeout'),
      '#description' => $this->t('The timeout in seconds for indexing requests to the Solr server.'),
      '#default_value' => isset($this->configuration['index_timeout']) ? $this->configuration['index_timeout'] : 5,
      '#required' => TRUE,
    );

    $form['optimize_timeout'] = array(
      '#type' => 'number',
      '#min' => 1,
      '#max' => 180,
      '#title' => $this->t('Optimize timeout'),
      '#description' => $this->t('The timeout in seconds for background index optimization queries on a Solr server.'),
      '#default_value' => isset($this->configuration['optimize_timeout']) ? $this->configuration['optimize_timeout'] : 10,
      '#required' => TRUE,
    );

    $form['commit_within'] = array(
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Commit within'),
      '#description' => $this->t('The limit in milliseconds within a (soft) commit on Solr is forced after any updating the index in any way. Setting the value to "0" turns off this dynamic enforcement and lets Solr behave like configured solrconf.xml.'),
      '#default_value' => isset($this->configuration['commit_within']) ? $this->configuration['commit_within'] : 1000,
      '#required' => TRUE,
    );

    $form['workarounds'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Connector Workarounds'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $form['workarounds']['solr_version'] = array(
      '#type' => 'select',
      '#title' => $this->t('Solr version override'),
      '#description' => $this->t('Specify the Solr version manually in case it cannot be retrived automatically. The version can be found in the Solr admin interface under "Solr Specification Version" or "solr-spec"'),
      '#options' => array(
        '' => $this->t('Determine automatically'),
        '4' => '4.x',
        '5' => '5.x',
        '6' => '6.x',
      ),
      '#default_value' => isset($this->configuration['solr_version']) ? $this->configuration['solr_version'] : '',
    );

    $form['workarounds']['http_method'] = array(
      '#type' => 'select',
      '#title' => $this->t('HTTP method'),
      '#description' => $this->t('The HTTP method to use for sending queries. GET will often fail with larger queries, while POST should not be cached. AUTO will use GET when possible, and POST for queries that are too large.'),
      '#default_value' => isset($this->configuration['http_method']) ? $this->configuration['http_method'] : 'AUTO',
      '#options' => array(
        'AUTO' => $this->t('AUTO'),
        'POST' => 'POST',
        'GET' => 'GET',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['port']) && (!is_numeric($values['port']) || $values['port'] < 0 || $values['port'] > 65535)) {
      $form_state->setError($form['port'], $this->t('The port has to be an integer between 0 and 65535.'));
    }
    if (!empty($values['path']) && strpos($values['path'], '/') !== 0) {
      $form_state->setError($form['path'], $this->t('If provided the path has to start with "/".'));
    }
    if (!empty($values['core']) && strpos($values['core'], '/') === 0) {
      $form_state->setError($form['core'], $this->t('The core must not start with "/".'));
    }

    if (!$form_state->hasAnyErrors()) {
      // Try to orchestrate a server link from form values.
      $solr = new Client(NULL, $this->eventDispatcher);
      $solr->createEndpoint($values + ['key' => 'core'], TRUE);
      try {
        $this->getServerLink();
      }
      catch (\InvalidArgumentException $e) {
        foreach (['scheme', 'host', 'port', 'path', 'core'] as $part) {
          $form_state->setError($form[$part], $this->t('The server link generated from the form values is illegal.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Since the form is nested into another, we can't simply use #parents for
    // doing this array restructuring magic. (At least not without creating an
    // unnecessary dependency on internal implementation.)
    foreach ($values['workarounds'] as $key => $value) {
      $form_state->setValue($key, $value);
    }

    // Clean-up the form to avoid redundant entries in the stored configuration.
    $form_state->unsetValue('workarounds');

    $this->traitSubmitConfigurationForm($form, $form_state);
  }

  /**
   * Prepares the connection to the Solr server.
   */
  protected function connect() {
    if (!$this->solr) {
      $this->solr = new Client(NULL, $this->eventDispatcher);
      $this->solr->createEndpoint($this->configuration + ['key' => 'core'], TRUE);
      $this->attachServerEndpoint();
    }
  }

  /**
   * Attaches an endpoint to the Solr connection to communicate with the server.
   *
   * This endpoint is different from the core endpoint which is the default one.
   * The default endpoint for the core is used to communicate with the index.
   * But for some administrative tasks the server itself needs to be contacted.
   * This function is meant to be overwritten as soon as we deal with Solr
   * service provider specific implementations of SolrHelper.
   */
  protected function attachServerEndpoint() {
    $this->connect();
    $configuration = $this->configuration;
    $configuration['core'] = NULL;
    $configuration['key'] = 'server';
    $this->solr->createEndpoint($configuration);
  }

  /**
   * Returns a the Solr server URI.
   */
  protected function getServerUri() {
    $this->connect();
    $url_path = $this->solr->getEndpoint('server')->getBaseUri();
    if ($this->configuration['host'] == 'localhost' && !empty($_SERVER['SERVER_NAME'])) {
      $url_path = str_replace('localhost', $_SERVER['SERVER_NAME'], $url_path);
    }

    return $url_path;
  }

  /**
   * {@inheritdoc}
   */
  public function getServerLink() {
    $url_path = $this->getServerUri();
    $url = Url::fromUri($url_path);

    return Link::fromTextAndUrl($url_path, $url);
  }

  /**
   * {@inheritdoc}
   */
  public function getCoreLink() {
    $url_path = $this->getServerUri() . '#/' . $this->configuration['core'];
    $url = Url::fromUri($url_path);

    return Link::fromTextAndUrl($url_path, $url);
  }

  /**
   * {@inheritdoc}
   */
  public function getSolrVersion($force_auto_detect = FALSE) {
    // Allow for overrides by the user.
    if (!$force_auto_detect && !empty($this->configuration['solr_version'])) {
      // In most cases the already stored solr_version is just the major version
      // number as integer. In this case we will expand it to the minimum
      // corresponding full version string.
      $min_version = ['0', '0', '0'];
      $version = explode('.', $this->configuration['solr_version']) + $min_version;

      return implode('.', $version);
    }

    $info = [];
    try {
      $info = $this->getCoreInfo();
    }
    catch (SearchApiSolrException $e) {
      try {
        $info = $this->getServerInfo();
      }
      catch (SearchApiSolrException $e) {
      }
    }

    // Get our solr version number.
    if (isset($info['lucene']['solr-spec-version'])) {
      return $info['lucene']['solr-spec-version'];
    }

    return '0.0.0';
  }

  /**
   * {@inheritdoc}
   */
  public function getSolrMajorVersion($version = '') {
    list($major, ,) = explode('.', $version ?: $this->getSolrVersion());
    return $major;
  }

  /**
   * {@inheritdoc}
   */
  public function getSolrBranch($version = '') {
    return $this->getSolrMajorVersion($version) . '.x';
  }

  /**
   * {@inheritdoc}
   */
  public function getLuceneMatchVersion($version = '') {
    list($major, $minor,) = explode('.', $version ?: $this->getSolrVersion());
    return $major . '.' . $minor;
  }

  /**
   * {@inheritdoc}
   */
  public function getServerInfo($reset = FALSE) {
    return $this->getDataFromHandler('server', 'admin/info/system', $reset);
  }

  /**
   * {@inheritdoc}
   */
  public function getCoreInfo($reset = FALSE) {
    return $this->getDataFromHandler('core', 'admin/system', $reset);
  }

  /**
   * {@inheritdoc}
   */
  public function getLuke() {
    return $this->getDataFromHandler('core', 'admin/luke', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaVersionString($reset = FALSE) {
    return $this->getCoreInfo($reset)['core']['schema'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaVersion($reset = FALSE) {
    $parts = explode('-', $this->getSchemaVersionString($reset));
    return $parts[1];
  }

  /**
   * Gets data from a Solr endpoint using a given handler.
   *
   * @param $endpoint_name
   * @param $handler
   * @param bool $reset
   *   If TRUE the server will be asked regardless if a previous call is cached.
   *
   * @return object
   *   A response object with system information.
   */
  protected function getDataFromHandler($endpoint_name, $handler, $reset = FALSE) {
    static $previous_calls = [];

    $this->connect();

    $endpoint = $this->solr->getEndpoint($endpoint_name);
    $endpoint_uri = $endpoint->getBaseUri();
    $state_key = 'search_api_solr.endpoint.data';
    $state = \Drupal::state();
    $endpoint_data = $state->get($state_key);

    if (!isset($previous_calls[$endpoint_uri][$handler]) || $reset) {
      // Don't retry multiple times in case of an exception.
      $previous_calls[$endpoint_name] = TRUE;

      if (!is_array($endpoint_data) || !isset($endpoint_data[$endpoint_uri][$handler]) || $reset) {
        // @todo Finish https://github.com/solariumphp/solarium/pull/155 and stop
        // abusing the ping query for this.
        $query = $this->solr->createPing(array('handler' => $handler));
        $endpoint_data[$endpoint_uri][$handler] = $this->execute($query, $endpoint)->getData();
        $state->set($state_key, $endpoint_data);
      }
    }

    return $endpoint_data[$endpoint_uri][$handler];
  }

  /**
   * {@inheritdoc}
   */
  public function pingCore() {
    return $this->doPing();
  }

  /**
   * {@inheritdoc}
   */
  public function pingServer() {
    return $this->doPing(['handler' => 'admin/info/system'], 'server');
  }

  /**
   * Pings the Solr server to tell whether it can be accessed.
   *
   * @param string $endpoint_name
   *   The endpoint to be pinged on the Solr server.
   *
   * @return mixed
   *   The latency in milliseconds if the core can be accessed,
   *   otherwise FALSE.
   */
  protected function doPing($options = [], $endpoint_name = 'core') {
    $this->connect();
    // Default is ['handler' => 'admin/ping'].
    $query = $this->solr->createPing($options);

    try {
      $start = microtime(TRUE);
      $result = $this->solr->execute($query, $endpoint_name);
      if ($result->getResponse()->getStatusCode() == 200) {
        // Add 1 µs to the ping time so we never return 0.
        return (microtime(TRUE) - $start) + 1E-6;
      }
    }
    catch (HttpException $e) {
      // Don't handle the exception. Just return FALSE below.
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatsSummary() {
    $this->connect();

    $summary = array(
      '@pending_docs' => '',
      '@autocommit_time_seconds' => '',
      '@autocommit_time' => '',
      '@deletes_by_id' => '',
      '@deletes_by_query' => '',
      '@deletes_total' => '',
      '@schema_version' => '',
      '@core_name' => '',
      '@index_size' => '',
    );

    $query = $this->solr->createPing();
    $query->setResponseWriter(Query::WT_PHPS);
    $query->setHandler('admin/mbeans?stats=true');
    $stats = $this->execute($query)->getData();
    if (!empty($stats)) {
      $update_handler_stats = $stats['solr-mbeans']['UPDATEHANDLER']['updateHandler']['stats'];
      $summary['@pending_docs'] = (int) $update_handler_stats['docsPending'];
      $max_time = (int) $update_handler_stats['autocommit maxTime'];
      // Convert to seconds.
      $summary['@autocommit_time_seconds'] = $max_time / 1000;
      $summary['@autocommit_time'] = \Drupal::service('date.formatter')->formatInterval($max_time / 1000);
      $summary['@deletes_by_id'] = (int) $update_handler_stats['deletesById'];
      $summary['@deletes_by_query'] = (int) $update_handler_stats['deletesByQuery'];
      $summary['@deletes_total'] = $summary['@deletes_by_id'] + $summary['@deletes_by_query'];
      $summary['@schema_version'] = $this->getSchemaVersionString(TRUE);
      $summary['@core_name'] = $stats['solr-mbeans']['CORE']['core']['stats']['coreName'];
      if (version_compare($this->getSolrVersion(TRUE), '6.4', '>=')) {
        // @see https://issues.apache.org/jira/browse/SOLR-3990
        $summary['@index_size'] = $stats['solr-mbeans']['CORE']['core']['stats']['size'];
      }
      else {
        $summary['@index_size'] = $stats['solr-mbeans']['QUERYHANDLER']['/replication']['stats']['indexSize'];
      }
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function coreRestGet($path) {
    return $this->restRequest('core', $path);
  }

  /**
   * {@inheritdoc}
   */
  public function coreRestPost($path, $command_json = '') {
    return $this->restRequest('core', $path, Request::METHOD_POST, $command_json);
  }

  /**
   * {@inheritdoc}
   */
  public function serverRestGet($path) {
    return $this->restRequest('server', $path);
  }

  /**
   * {@inheritdoc}
   */
  public function serverRestPost($path, $command_json = '') {
    return $this->restRequest('server', $path, Request::METHOD_POST, $command_json);
  }

  /**
   * Sends a REST request to the Solr server endpoint and returns the result.
   *
   * @param string $endpoint_key
   *   The endpoint that refelcts the base URI.
   * @param string $path
   *   The path to append to the base URI.
   * @param string $method
   *   The HTTP request method.
   * @param string $command_json
   *   The command to send encoded as JSON.
   *
   * @return string
   *   The decoded response.
   */
  protected function restRequest($endpoint_key, $path, $method = Request::METHOD_GET, $command_json = '') {
    $this->connect();

    $request = new Request();
    $request->setMethod($method);
    $request->addHeader('Accept: application/json');
    if (Request::METHOD_POST == $method) {
      $request->addHeader('Content-type: application/json');
      $request->setRawData($command_json);
    }
    $request->setHandler($path);

    $endpoint = $this->solr->getEndpoint($endpoint_key);
    $timeout = $endpoint->getTimeout();
    // @todo Destinguish between different flavors of REST requests and use
    //   different timeout settings.
    $endpoint->setTimeout($this->configuration['optimize_timeout']);
    $response = $this->executeRequest($request, $endpoint);
    $endpoint->setTimeout($timeout);
    $output = Json::decode($response->getBody());
    // \Drupal::logger('search_api_solr')->info(print_r($output, true));.
    if (!empty($output['errors'])) {
      throw new SearchApiSolrException('Error trying to send a REST request.' .
        "\nError message(s):" . print_r($output['errors'], TRUE));
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdateQuery() {
    $this->connect();
    return $this->solr->createUpdate();
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectQuery() {
    $this->connect();
    return $this->solr->createSelect();
  }

  /**
   * {@inheritdoc}
   */
  public function getMoreLikeThisQuery() {
    $this->connect();
    return $this->solr->createMoreLikeThis();
  }

  /**
   * {@inheritdoc}
   */
  public function getTermsQuery() {
    $this->connect();
    return $this->solr->createTerms();
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryHelper(QueryInterface $query = NULL) {
    if ($query) {
      return $query->getHelper();
    }

    return new Helper();
  }

  /**
   * {@inheritdoc}
   */
  public function getExtractQuery() {
    $this->connect();
    return $this->solr->createExtract();
  }

  /**
   * @return \Solarium\Plugin\CustomizeRequest\CustomizeRequest
   */
  protected function customizeRequest() {
    $this->connect();
    return $this->solr->getPlugin('customizerequest');
  }

  /**
   * {@inheritdoc}
   */
  public function search(Query $query, Endpoint $endpoint = NULL) {
    $this->connect();

    if (!$endpoint) {
      $endpoint = $this->solr->getEndpoint('core');
    }

    // Use the 'postbigrequest' plugin if no specific http method is
    // configured. The plugin needs to be loaded before the request is
    // created.
    if ($this->configuration['http_method'] == 'AUTO') {
      $this->solr->getPlugin('postbigrequest');
    }

    // Use the manual method of creating a Solarium request so we can control
    // the HTTP method.
    $request = $this->solr->createRequest($query);

    // Set the configured HTTP method.
    if ($this->configuration['http_method'] == 'POST') {
      $request->setMethod(Request::METHOD_POST);
    }
    elseif ($this->configuration['http_method'] == 'GET') {
      $request->setMethod(Request::METHOD_GET);
    }

    return $this->executeRequest($request, $endpoint);
  }

  /**
   * {@inheritdoc}
   */
  public function createSearchResult(Query $query, Response $response) {
    return $this->solr->createResult($query, $response);
  }

  /**
   * {@inheritdoc}
   */
  public function update(UpdateQuery $query, Endpoint $endpoint = NULL) {
    $this->connect();

    if (!$endpoint) {
      $endpoint = $this->solr->getEndpoint('core');
    }
    // The default timeout is set for search queries. The configured timeout
    // might differ and needs to be set now because solarium doesn't
    // distinguish between these types.
    $timeout = $endpoint->getTimeout();
    $endpoint->setTimeout($this->configuration['index_timeout']);
    if ($this->configuration['commit_within']) {
      // Do a commitWithin since that is automatically a softCommit since Solr 4
      // and a delayed hard commit with Solr 3.4+.
      // By default we wait 1 second after the request arrived for solr to parse
      // the commit. This allows us to return to Drupal and let Solr handle what
      // it needs to handle.
      // @see http://wiki.apache.org/solr/NearRealtimeSearch
      /** @var \Solarium\Plugin\CustomizeRequest\CustomizeRequest $request */
      $request = $this->customizeRequest();
      $request->createCustomization('id')
        ->setType('param')
        ->setName('commitWithin')
        ->setValue($this->configuration['commit_within']);
    }

    $result = $this->execute($query, $endpoint);

    // Reset the timeout setting to the default value for search queries.
    $endpoint->setTimeout($timeout);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(QueryInterface $query, Endpoint $endpoint = NULL) {
    $this->connect();

    if (!$endpoint) {
      $endpoint = $this->solr->getEndpoint('core');
    }

    try {
      return $this->solr->execute($query, $endpoint);
    }
    catch (HttpException $e) {
      $this->handleHttpException($e, $endpoint);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function executeRequest(Request $request, Endpoint $endpoint = NULL) {
    $this->connect();

    if (!$endpoint) {
      $endpoint = $this->solr->getEndpoint('core');
    }

    try {
      return $this->solr->executeRequest($request, $endpoint);
    }
    catch (HttpException $e) {
      $this->handleHttpException($e, $endpoint);
    }
  }

  /**
   * Converts a HttpException in an easier to read SearchApiSolrException.
   *
   * @param \Solarium\Exception\HttpException $e
   * @param \Solarium\Core\Client\Endpoint $endpoint
   *
   * @throws \Drupal\search_api_solr\SearchApiSolrException
   */
  protected function handleHttpException(HttpException $e, Endpoint $endpoint) {
    $response_code = $e->getCode();
    switch ($response_code) {
      case 404:
        $description = $this->t('not found');
        break;

      case 401:
      case 403:
        $description = $this->t('access denied');
        break;

      default:
        $description = $this->t('unreachable');
    }
    throw new SearchApiSolrException($this->t('Solr endpoint @endpoint @description.', ['@endpoint' => $endpoint->getBaseUri(), '@description' => $description]), $response_code, $e);
  }

  /**
   * {@inheritdoc}
   */
  public function optimize(Endpoint $endpoint = NULL) {
    $this->connect();

    if (!$endpoint) {
      $endpoint = $this->solr->getEndpoint('core');
    }
    // The default timeout is set for search queries. The configured timeout
    // might differ and needs to be set now because solarium doesn't
    // distinguish between these types.
    $timeout = $endpoint->getTimeout();
    $endpoint->setTimeout($this->configuration['optimize_timeout']);

    $update_query = $this->solr->createUpdate();
    $update_query->addOptimize(TRUE, FALSE);

    $this->execute($update_query, $endpoint);

    // Reset the timeout setting to the default value for search queries.
    $endpoint->setTimeout($timeout);
  }

  /**
   * {@inheritdoc}
   */
  public function extract(QueryInterface $query) {
    return $this->execute($query);
  }

  /**
   * {@inheritdoc}
   */
  public function getContentFromExtractResult(ExtractResult $result, $filepath){
    $response = $result->getResponse();
    $json_data = $response->getBody();
    $array_data = Json::decode($json_data);
    return $array_data[$filepath];
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoint($key = 'core') {
    $this->connect();
    return $this->solr->getEndpoint($key);
  }

  /**
   * {@inheritdoc}
   */
  public function getFile($file = NULL) {
    $this->connect();

    $query = $this->solr->createPing();
    $query->setHandler('admin/file');
    $query->addParam('contentType', 'text/xml;charset=utf-8');
    if ($file) {
      $query->addParam('file', $file);
    }

    return $this->execute($query)->getResponse();
  }

  /**
   * {@inheritdoc}
   */
  public function viewSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    // It's safe to unset the solr client completely before serialization
    // because connect() will set it up again correctly after deserialization.
    unset($this->solr);
    return parent::__sleep();
  }

}
