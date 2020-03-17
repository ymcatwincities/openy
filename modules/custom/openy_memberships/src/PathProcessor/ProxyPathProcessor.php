<?php
/**
 * Provide Drupal service to process proxying client query.
 * @file
 * Contains Drupal\openy_memberships\PathProcessor
 */

namespace Drupal\openy_memberships\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provide class for Drupal path (route) processor to be able proxying the client's query.
 */
class ProxyPathProcessor implements InboundPathProcessorInterface {

  /** 
   * Main route method for make client request. 
   * @param string $path Requested path (route)
   * @param \Symfony\Component\HttpFoundation\Request $request Request data from client. 
   * @return string Return Drupal path (route).
   */
  public function processInbound($path, Request $request) {
    // if (strpos($path, '/memberships/') === 0 && !$request->query->has('path')) {
    //   $file_path = preg_replace('|^\/memberships\/|', '', $path);
    //   $request->query->set('path', $file_path);
    //   return '/memberships';
    // }
    return $path;
  }

}
