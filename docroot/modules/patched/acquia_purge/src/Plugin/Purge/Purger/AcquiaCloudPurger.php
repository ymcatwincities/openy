<?php

/**
 * @file
 * Contains \Drupal\acquia_purge\Plugin\Purge\Purger\HttpPurger.
 */

namespace Drupal\acquia_purge\Plugin\Purge\Purger;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Drupal\purge\Plugin\Purge\Purger\PurgerInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\acquia_purge\HostingInfoInterface;

/**
 * Acquia Cloud.
 *
 * @PurgePurger(
 *   id = "acquia_purge",
 *   label = @Translation("Acquia Cloud"),
 *   configform = "",
 *   cooldown_time = 0.2,
 *   description = @Translation("Invalidates Varnish powered load balancers on your Acquia Cloud site."),
 *   multi_instance = FALSE,
 *   types = {"url"},
 * )
 */
class AcquiaCloudPurger extends PurgerBase implements PurgerInterface {

  /**
   * The number of HTTP requests executed in parallel during purging.
   */
  const PARALLEL_REQUESTS = 6;

  /**
   * The number of seconds before a purge attempt times out.
   */
  const REQUEST_TIMEOUT = 2;

  /**
   * @var \Drupal\acquia_purge\HostingInfoInterface
   */
  protected $acquiaPurgeHostinginfo;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Associative array with invalidation type (key) and measurement (value).
   *
   * @var float[]
   */
  protected $typeMeasurements = [];

  /**
   * The name of the state API key in which type measurements are stored.
   *
   * @var string
   */
  protected $typeMeasurementsStateKey = 'acquia_purge_type_measurements';

  /**
   * Constructs a AcquiaCloudPurger object.
   *
   * @param \Drupal\acquia_purge\HostingInfoInterface $acquia_purge_hostinginfo
   *   Technical information accessors for the Acquia Cloud environment.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  function __construct(HostingInfoInterface $acquia_purge_hostinginfo, StateInterface $state, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->acquiaPurgeHostingInfo = $acquia_purge_hostinginfo;
    $this->typeMeasurements = $state->get($this->typeMeasurementsStateKey, []);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('acquia_purge.hostinginfo'),
      $container->get('state'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->state->delete($this->typeMeasurementsStateKey);
  }

  /**
   * Execute a set of HTTP requests.
   *
   * Executes a set of HTTP requests using the cUrl PHP extension and adds
   * resulting information to the ->attributes parameter bag on each request
   * object. It will perform parallel processing to reduce the PHP execution
   * time taken.
   *
   * @param \Symfony\Component\HttpFoundation\Request[] $requests
   *   Unassociative list of Request objects to execute. When the 'connect_to'
   *   attribute key is present, this value will be used to connect to instead
   *   of the 'host' header.
   *
   * @return void
   */
  protected function executeRequests(array $requests) {

    // Presort the request objects in request groups based on the maximum amount
    // of requests we can perform in parallel. Max SELF::PARALLEL_REQUESTS each!
    $request_groups = [];
    $unprocessed = count($requests);
    reset($requests);
    while ($unprocessed > 0) {
      $group = [];
      for ($n = 0; $n < SELF::PARALLEL_REQUESTS; $n++) {
        if (!is_null($i = key($requests))) {
          $group[] = $requests[$i];
          $unprocessed--;
          next($requests);
        }
      }
      if (count($group)) {
        $request_groups[] = $group;
      }
    }

    // Perform HTTP processing for each request group.
    foreach ($request_groups as $group) {
      $multihandler = (count($group) === 1) ? FALSE : curl_multi_init();

      // Prepare the cUrl handlers for each Request.
      foreach ($group as $r) {
        $handler = curl_init();
        curl_setopt($handler, CURLOPT_TIMEOUT, SELF::REQUEST_TIMEOUT);
        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, $r->getMethod());
        curl_setopt($handler, CURLOPT_FAILONERROR, TRUE);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, TRUE);
        $r->attributes->set('curl_handler', $handler);

        // Confgure the URL to connect to on the handler.
        $url = $r->getUri();
        if ($connect_to = $r->attributes->get('connect_to')) {
          $url = str_replace($r->getHttpHost(), $connect_to, $url);
        }
        curl_setopt($handler, CURLOPT_URL, $url);

        // Generate and set the list of headers to send.
        $headers = [];
        foreach (explode("\r\n", trim($r->headers->__toString())) as $line) {
          $headers[] = $line;
        }
        curl_setopt($handler, CURLOPT_HTTPHEADER, $headers);

        // For requests over SSL, we disable host and peer verification. This
        // is usually a red flag to the security concerned, but avoids a great
        // deal of trouble with self-signed certificates. Above all, this is
        // only used for external cache invalidation.
        if ($r->isSecure()) {
          curl_setopt($handler, CURLOPT_SSL_VERIFYHOST, FALSE);
          curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, FALSE);
        }

        // With parallel processing, add this resource to the multihandler.
        if (is_resource($multihandler)) {
          curl_multi_add_handle($multihandler, $handler);
        }
      }

      // Let cUrl execute the requests (single mode or multihandling).
      if (is_resource($multihandler)) {
        $active = NULL;
        do {
          $mrc = curl_multi_exec($multihandler, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        while ($active && $mrc == CURLM_OK) {
          if (curl_multi_select($multihandler) != -1) {
            do {
              $mrc = curl_multi_exec($multihandler, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
          }
        }
      }
      else {
        $handler = $group[0]->attributes->get('curl_handler');
        curl_exec($handler);
        $single_info = ['result' => curl_errno($handler)];
      }

      // Query the handlers to put the results as attributes onto the request.
      foreach ($group as $r) {
        if (!($handler = $r->attributes->get('curl_handler'))) {
          continue;
        }

        // Set the general request results as attributes to the request.
        if (is_resource($multihandler)) {
          $info = curl_multi_info_read($multihandler);
        }
        else {
          $info = $single_info;
        }
        $r->attributes->set('curl_result', $info['result']);
        $r->attributes->set('curl_result_ok', $info['result'] == CURLE_OK);

        // Add all other cUrl information as attributes to the request.
        foreach (curl_getinfo($handler) as $key => $value) {
          $r->attributes->set('curl_' . $key, $value);
        }

        // Remove all cUrl resources except the results of course.
        if (is_resource($multihandler)) {
          curl_multi_remove_handle($multihandler, $handler);
        }
        curl_close($handler);
        $r->attributes->remove('curl_handler');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIdealConditionsLimit() {
    // The max amount of outgoing HTTP requests that can be made during script
    // execution time. Although always respected as outer limit, it will be lower
    // in practice as PHP resource limits (max execution time) bring it further
    // down. However, the maximum amount of requests will be higher on the CLI.
    $balancers = count($this->acquiaPurgeHostingInfo->getBalancerAddresses());
    if ($balancers) {
      return intval(ceil(200 / $balancers));
    }
    return 100;
  }

  /**
   * {@inheritdoc}
   */
  public function hasRuntimeMeasurement() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $invalidations) {

    // Since we implemented ::routeTypeToMethod(), this Latin preciousness
    // shouldn't ever occur and when it does, will be easily recognized.
    throw new \Exception("Malum consilium quod mutari non potest!");
  }

  /**
   * Invalidate a set of tag invalidations.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::invalidate()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::routeTypeToMethod()
   */
  public function invalidateTags(array $invalidations) {
    throw new \Exception(__METHOD__);
    // $logger = \Drupal::logger('purge_purger_http');
    //
    // // Iterate every single object and fire a request per object.
    // foreach ($invalidations as $invalidation) {
    //   $token_data = ['invalidation' => $invalidation];
    //   $uri = $this->getUri($token_data);
    //   $opt = $this->getOptions($token_data);
    //
    //   try {
    //     $this->client->request($this->settings->request_method, $uri, $opt);
    //     $invalidation->setState(InvalidationInterface::SUCCEEDED);
    //   }
    //   catch (\Exception $e) {
    //     $invalidation->setState(InvalidationInterface::FAILED);
    //     $headers = $opt['headers'];
    //     unset($opt['headers']);
    //     $logger->emergency(
    //       "%exception thrown by %id, invalidation marked as failed. URI: %uri# METHOD: %request_method# HEADERS: %headers#mOPT: %opt#MSG: %exceptionmsg#",
    //       [
    //         '%exception' => get_class($e),
    //         '%exceptionmsg' => $e->getMessage(),
    //         '%request_method' => $this->settings->request_method,
    //         '%opt' => $this->exportDebuggingSymbols($opt),
    //         '%headers' => $this->exportDebuggingSymbols($headers),
    //         '%uri' => $uri,
    //         '%id' => $this->getid()
    //       ]
    //     );
    //   }
    // }
  }

  /**
   * Invalidate a set of URL invalidations.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::invalidate()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::routeTypeToMethod()
   */
  public function invalidateUrls(array $invalidations) {
    $balancer_addresses = $this->acquiaPurgeHostingInfo->getBalancerAddresses();
    $balancer_token = $this->acquiaPurgeHostingInfo->getBalancerToken();

    // Set all invalidation states to PROCESSING before we kick off purging.
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::PROCESSING);
    }

    // Define HTTP requests for every URL*BAL that we are going to invalidate.
    $requests = [];
    foreach ($invalidations as $invalidation) {
      foreach ($balancer_addresses as $balancer_address) {
        $r = Request::create($invalidation->getExpression(), 'PURGE');
        $r->attributes->set('connect_to', $balancer_address);
        $r->attributes->set('invalidation_id', $invalidation->getId());
        $r->headers->remove('Accept-Language');
        $r->headers->remove('Accept-Charset');
        $r->headers->remove('Accept');
        $r->headers->set('X-Acquia-Purge', $balancer_token);
        $r->headers->set('Accept-Encoding', 'gzip');
        $r->headers->set('User-Agent', 'Acquia Purge');
        $requests[] = $r;
      }
    }

    // Perform the requests, results will be set as attributes onto the objects.
    $this->executeRequests($requests);

    // Collect all results per invalidation object based on the cUrl data.
    $results = [];
    foreach ($requests as $request) {
      if (!is_null($inv_id = $request->attributes->get('invalidation_id'))) {

        // URLs not in varnish return 404, that's also seen as a success.
        if ($request->attributes->get('curl_http_code') === 404) {
          $results[$inv_id][] = TRUE;
        }
        else {
          $results[$inv_id][] = $request->attributes->get('curl_result_ok');
        }
      }
    }

    // Triage and set all invalidation states correctly.
    foreach ($invalidations as $invalidation) {
      $inv_id = $invalidation->getId();
      if (isset($results[$inv_id]) && count($results[$inv_id])) {
        if (!in_array(FALSE, $results[$inv_id])) {
          $invalidation->setState(InvalidationInterface::SUCCEEDED);
          continue;
        }
      }
      $invalidation->setState(InvalidationInterface::SUCCEEDED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function routeTypeToMethod($type) {
    $methods = [
      'tag'  => 'invalidateTags',
      'url'  => 'invalidateUrls',
    ];
    return isset($methods[$type]) ? $methods[$type] : 'invalidate';
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHint() {
    return 10.0;
  }

}
