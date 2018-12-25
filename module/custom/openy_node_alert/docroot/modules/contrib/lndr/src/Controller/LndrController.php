<?php

namespace Drupal\lndr\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Exception\ClientException;

/**
 * Controller routines for page example routes.
 */
class LndrController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'lndr';
  }

  /**
   * Page callback to manually sync all of the lndr pages
   * @return mixed
   */
  public function lndr_sync() {
    $path = '';
    if (isset($_GET['path'])) {
      // Sanitize $_GET['path']
      $path = \Drupal\Component\Utility\UrlHelper::filterBadProtocol($_GET['path']);
    }
    $batch = array(
      'title' => t('Deploying Lndr page'),
      'operations' => array(
        array(
          '\Drupal\lndr\Controller\LndrController::sync_processing',
          array(array(1), $path),
        ),
      ),
      'finished' => '\Drupal\lndr\Controller\LndrController::sync_processing_finish_callback',
    );
    batch_set($batch);
    return batch_process();
  }

  /**
   * Process the batch operation
   * @param $ids
   * @param $path
   * @param $context
   */
  public static function sync_processing($ids, $path, &$context){
    // @todo: making it truly batch in the future?
    $message = 'Deploying Lndr pages... ';
    $controller = new LndrController();
    $controller->sync_path();
    $results = array();
    // If we run this process with a $path passed in, it means it comes from a
    // /lndr/reserved => /somepage
    if ($path != '') {
      // We check after running the sync, if that path has been updated from reserved to actual lndr page id
      // Which means it has been published
      $url_alias = \Drupal::service('path.alias_storage')->load(['alias' => $path]);
      if (!empty($url_alias)) {
        // We flag it and send it to the finishing process for proper redirect.
        if ($url_alias['source'] != '/lndr/reserved') {
          $results['path_updated'] = $path;
        }
      }
    }
    $context['message'] = $message;
    $context['results'] = $results;
  }

  /**
   * Callback for batch finished operation
   * @param $success
   * @param $results
   * @param $operations
   */
  function sync_processing_finish_callback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = t('Process complete');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
    // if there's a redirect
    global $base_url;

    // If we were sent from a placeholder (/lndr/reserved => path) but the page has been
    // published, we redirect back to that alias so user can see the published page
    if (array_key_exists('path_updated', $results)) {
      $response = new RedirectResponse($base_url . $results['path_updated']);
      $response->send();
      return;
    } else {
      // if not, let's go home so we don't create an infinite loop
      $response = new RedirectResponse($base_url);
      $response->send();
      return;
    }
  }

  /**
   * Syncing URL alias from Lndr based on the web service endpoint
   */
  public function sync_path() {
    // Get the API token
    $config = \Drupal::config('lndr.settings');
    $api_token = $config->get('lndr_token');
    if($api_token == '') {
      return;
    }

    // loading dummy data if we are in debug mode
    if ($config->get('lndr_debug_mode')) {
      global $base_url;
      $service_url = $base_url . '/examples/lndr/service';
      $response = \Drupal::httpClient()->request('GET', $service_url);

      $result = $response->getBody();
      $data = json_decode($result, true);
      // Create or update alias in Drupal
      $this->upsert_alias($data['projects']);

      // Delete alias in Drupal
      $this->remove_alias($data['projects']);
    }
    else {
      try {
        $response = \Drupal::httpClient()->request('GET', LNDR_API_GET_PROJECT, [
          'headers' => [
            'Authorization' => 'Token token=' . $api_token,
          ]
        ]);
        $result = $response->getBody();

        $data = json_decode($result, true);

        // Create or update alias in Drupal
        $this->upsert_alias($data['projects']);

        // Delete alias in Drupal
        $this->remove_alias($data['projects']);
      }
      catch(ClientException $e) {
        \Drupal::logger('lndr')->notice($e->getMessage());
      }
    }
  }

  /**
   * Create or update alias in Drupal for Lndr pages
   * @param $projects
   */
  private function upsert_alias($projects) {
    global $base_url;
    $drupal_pages = array();
    foreach ($projects as $project) {
      if (strstr($project['publish_url'], $base_url)) {
        $drupal_pages[] = $project;
      }
    }
    // Nothing to process
    if (empty($drupal_pages)) {
      return;
    }
    // Going through all the pages that are published to this URL
    foreach ($drupal_pages as $page) {
      $path_alias = substr($page['publish_url'], strlen($base_url));
      $existing_alias_by_alias = \Drupal::service('path.alias_storage')->load(['alias' => $path_alias]);
      if (!empty($existing_alias_by_alias)) {
        // case 1. this alias was reserved for this page, UPDATE IT
        if ($existing_alias_by_alias['source'] === '/lndr/reserved') {
          $system_path = '/lndr/' . $page['id'];
          // @todo: throw an error if not saving correctly.
          \Drupal::service('path.alias_storage')->save($system_path, $path_alias, 'und', $existing_alias_by_alias['pid']);
        }
      }
      else
      {
        // case 3. let's see if a previous alias is stored, but we updated to a new one from Lndr
        $existing_alias_by_source = \Drupal::service('path.alias_storage')->load(['source' => '/lndr/' . $page['id']]);
        if (!empty($existing_alias_by_source)) {
          // Making sure that it is still on the same domain
          if (substr($page['publish_url'], 0, strlen($base_url)) === $base_url) {
            $_path = substr($page['publish_url'], strlen($base_url));
            if ($_path !== $existing_alias_by_source['alias']) {
              // @todo: throw an error if not saving correctly.
              \Drupal::service('path.alias_storage')->save($existing_alias_by_source['source'], $_path, 'und', $existing_alias_by_source['pid']);
            }
          }
        }
        else
        {
          // case 2. No Drupal alias exist at all, change from some other URL to Drupal domain URL
          // @todo: throw an error if not saving correctly.
          \Drupal::service('path.alias_storage')->save('/lndr/' . $page['id'], $path_alias);
        }
      }
    }
  }

  /**
   * Removing url alias if the page has been unpublished or path changed
   * from Lndr
   * @param $projects
   */
  private function remove_alias($projects) {

    global $base_url;
    // Re-format the projects a bit to give them keys as project id
    $_projects = array();
    foreach ($projects as $project) {
      $_projects[$project['id']] = $project;
    }

    // Get all alias lndr uses (lndr/[project_id])
    $existing_alias = $this->load_lndr_alias();
    if (empty($existing_alias)) {
      return;
    }

    foreach ($existing_alias as $project_id => $alias) {
      // Case 5. Remove any local path not presented in the web service (deleted or unpublished on Lndr)
      if (!array_key_exists($project_id, $_projects)) {
        // @todo: catch error when delete is unsuccessful
        \Drupal::service('path.alias_storage')->delete(['pid' => $alias['pid']]);
      }
      else
      {
        // Case 4. There is a local alias, however, remotely it has been changed to something not on this Domain
        if (substr($_projects[$project_id]['publish_url'], 0, strlen($base_url)) !== $base_url) {
          // @todo: catch error when delete is unsuccessful
          \Drupal::service('path.alias_storage')->delete(['pid' => $alias['pid']]);
        }
      }
    }
  }

  /**
   * Helper function that loads all of the URL alias that has a source of lndr/%
   * that are not reserved URL.
   * @return array
   */
  private function load_lndr_alias() {
    $data = array();
    $query = db_select('url_alias', 'u')
      ->fields('u', array('pid', 'source', 'alias'))
      ->condition('u.source', '/lndr/%', 'LIKE');

    $results = $query->execute();
    foreach ($results as $result) {
      $path = explode('/', $result->source);
        $data[$path[2]] = (array) $result;
    }
    return $data;
  }

  /**
   * @param $page_id
   * @return bool|Response
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function page($page_id) {
    // Make sure you don't trust the URL to be safe! Always check for exploits.
    if ($page_id == 'reserved') {
      // When users hit the my_campaign -> lndr/reserved path, let's actually run the sync process
      // This way we can deploy this page faster, we can also check if this path reservation is orphaned
      $current_path = \Drupal::service('path.current')->getPath();
      $alias = \Drupal::service('path.alias_manager')->getAliasByPath($current_path);

      global $base_url;
      $response = new RedirectResponse($base_url . '/lndr_sync?path=' . $alias);
      $response->send();
    }
    $internal_url = LNDR_BASE . 'projects/' . $page_id;
    return $this->import_page($internal_url);
  }

  /**
   * Taking a Lndr page, parse and display it
   * @param $url
   * @return bool|Response
   */
  private function import_page($url) {
    $page_response = new Response();
    try {
      $response = \Drupal::httpClient()->request('GET', $url, [
        'allow_redirects' => [
          'max'             => 10,
          'referer'         => true,
          'track_redirects' => true
        ]
      ]);

      $status_code = (string) $response->getStatusCode();
      // error with fetching the url
      if ($status_code !== '200') {
        \Drupal::logger('lndr')->notice('Lndr was unable to fetch the url: @url with code: %code',
          array(
            '@url' => $url,
            '%code' => $status_code,
          ));
        return $page_response;
      }

      // If there is a header for referral, let's take the last one
      $last_referral = $response->getHeader('x-guzzle-redirect-history');
      $referral = end($last_referral);
      if ($referral != '') {
        $url = $referral;
      }

      // Start to parse the content
      module_load_include('inc', 'lndr', 'simple_html_dom');
      $html = str_get_html((string)$response->getBody());

      // prepend the url of the page to all of the images
      foreach($html->find('img') as $key => $element) {
        $src= $element->src;
        $html->find('img', $key)->src = $url . $src;
      }

      // prepend url to internal stylesheets
      foreach($html->find('link[rel="stylesheet"]') as $key => $element) {
        if (substr($element->href, 0, 4) !== 'http') {
          $html->find('link[rel="stylesheet"]', $key)->href = $url . $element->href;
        }
      }

      // prepend javascripts
      foreach($html->find('script') as $key => $element) {
        $src = $element->src;
        if (isset($src)) {
          $html->find('script', $key)->src = $url . $src;
        }
      }

      $elements = array(
        'div',
        'a',
        'section',
      );

      foreach ($elements as $element) {
        foreach ($html->find($element . '[data-background-image]') as $key => $_element) {
          $bg_image = $_element->{'data-background-image'};
          $html->find($element . '[data-background-image]', $key)->{'data-background-image'} = $url . $bg_image;
        }
      }

      $page_response->headers->set('Content-Type', 'text/html; charset=utf-8');
      $page_response->setContent($html);
      return $page_response;
    }
    catch (RequestException $e) {
      return $page_response;
    }
  }
}
