<?php

/**
 * @file
 * Definition of Drupal\stage_file_proxy\FetchManagerInterface.
 */

namespace Drupal\stage_file_proxy;


use GuzzleHttp\ClientInterface;

interface FetchManagerInterface {

  public function __construct(ClientInterface $client);

  /**
   * Downloads a remote file and saves it to the local files directory.
   *
   * @param string $server
   *   The origin server URL.
   * @param string $remote_file_dir
   *   The relative path to the files directory on the origin server.
   * @param string $relative_path
   *   The path to the requested resource relative to the files directory.
   *
   * @return bool
   *   Returns true if the content was downloaded, otherwise false.
   */
  public function fetch($server, $remote_file_dir, $relative_path);

  /**
   * Helper to retrieve the file directory.
   */
  public function filePublicPath();

  /**
   * Helper to retrieves original path for a styled image.
   *
   * @param string $uri
   *   A uri or path (may be prefixed with scheme)
   * @param bool $style_only
   *   Indicates if, the function should only return paths retrieved from style
   *   paths. Defaults to TRUE.
   *
   * @return bool|mixed|string
   *   A file URI pointing to the given original image.
   *   If $style_only is set to TRUE and $uri is no style-path, FALSE is returned.
   */
  public function styleOriginalPath($uri, $style_only = TRUE);
}
