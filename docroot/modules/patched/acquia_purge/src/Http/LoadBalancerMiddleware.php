<?php

namespace Drupal\acquia_purge\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Drupal\acquia_purge\Http\FailedInvalidationException;

/**
 * HTTP middleware which throws FailedInvalidationException's on BAN and PURGE
 * requests sent to Acquia Cloud load balancers.
 */
class LoadBalancerMiddleware {

  /**
   * {@inheritdoc}
   */
  public function __invoke() {
    return function (callable $handler) {
      return function ($req, array $options) use ($handler) {

        // Don't interfere on requests not going to Acquia Load balancers.
        if (!isset($options['acquia_purge_middleware'])) {
          return $handler($req, $options);
        }

        // Return a handler that throws exceptions on bad responses.
        return $handler($req, $options)->then(
          function (ResponseInterface $rsp) use ($req, $handler, $options) {
            $status = $rsp->getStatusCode();
            $method = $req->getMethod();

            // Define a tiny closure that throws exceptions for us.
            $e = function($msg) use ($req, $rsp) {
              throw new FailedInvalidationException($msg, $req, $rsp);
            };

            // Flag up suspicious response types.
            if ($status === 403) {
              $e('Forbidden is abnormal and suggests that your balancer runs'
              . ' on a malfunctioning custom VCL configuration!');
            }
            elseif ($status == 405) {
              $e('Not allowed; Chances are that you customized the VCL file'
              . ' running on your balancer for a customized cache invalidation'
              . ' token. Please contact Acquia Support and consider reverting'
              . ' your configuration as these setups are no longer supported by'
              . ' the acquia_purge module.');
            }

            // Test response codes and reply messages per type of invalidation.
            if ($method == 'PURGE') {
              if (!in_array($status, [200, 404])) {
                $e("Expected 200 or 404 instead!");
              }
            }
            elseif ($method == 'BAN') {
              $path = $req->getRequestTarget();
              $reply = $rsp->getReasonPhrase();
              if ($status !== 200) {
                $e("Expected 200 instead!");
              }
              elseif (($path == '/site') && ($reply !== 'Site banned.')) {
                $e("Reply mismatch for /site.");
              }
              elseif (($path == '/tags') && ($reply !== 'Tags banned.')) {
                $e("Reply mismatch for /tags.");
              }
              elseif (!in_array($path, ['/site', '/tags'])) {
                if (!in_array($reply, ['WILDCARD URL banned.', 'URL banned.'])) {
                  $e("Reply mismatch for (wildcard)URL.");
                }
              }
            }
            else {
              $e("Unsupported HTTP method!");
            }

            return $rsp;
          }
        );
      };
    };
  }

}
