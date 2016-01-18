<?php

/**
 * @file
 * Definition of Drupal\stage_file_proxy\FetchManager.
 */

namespace Drupal\stage_file_proxy;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\StreamWrapper\PublicStream;
use GuzzleHttp\ClientInterface;

class FetchManager implements FetchManagerInterface {

  public function __construct(ClientInterface $client) {
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($server, $remote_file_dir, $relative_path) {
    // Fetch remote file.
    $url = $server . '/' . UrlHelper::encodePath($remote_file_dir . '/' . $relative_path);
    $response = $this->client->get($url);

    if ($response->getStatusCode() == 200) {
      // Prepare local target directory and save downloaded file.
      $file_dir = $this->filePublicPath();
      $target_dir = $file_dir . '/' . dirname($relative_path);
      if (file_prepare_directory($target_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
        file_put_contents($file_dir . '/' . $relative_path, $response->getBody(TRUE));
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function filePublicPath() {
    return PublicStream::basePath();
  }

  /**
   * {@inheritdoc}
   */
  public function styleOriginalPath($uri, $style_only = TRUE) {
    $scheme = file_uri_scheme($uri);
    if ($scheme) {
      $path = file_uri_target($uri);
    }
    else {
      $path = $uri;
      $scheme = file_default_scheme();
    }

    // It is a styles path, so we extract the different parts.
    if (strpos($path, 'styles') === 0) {
      // Then the path is like styles/[style_name]/[schema]/[original_path].
      return preg_replace('/styles\/.*\/(.*)\/(.*)/U', '$1://$2', $path);
    }
    // Else it seems to be the original.
    elseif ($style_only == FALSE) {
      return "$scheme://$path";
    }
    else {
      return FALSE;
    }
  }
}
