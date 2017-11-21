<?php

namespace Drupal\lndr\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response;
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
      if (is_numeric($path[2])) {
        $data[$path[2]] = (array) $result;
      }
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
    if (!is_numeric($page_id)) {
      // We will just show a standard "access denied" page in this case.
      throw new AccessDeniedHttpException();
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

      // prepend url to stylesheet, assuming we only have one stylesheet so far
      $html->find('link[rel="stylesheet"]', 0)->href = $url . $html->find('link[rel="stylesheet"]', 0)->href;

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
