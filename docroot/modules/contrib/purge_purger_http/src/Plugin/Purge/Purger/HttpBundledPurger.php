<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Plugin\Purge\Purger\HttpBundledPurger.
 */

namespace Drupal\purge_purger_http\Plugin\Purge\Purger;

use Drupal\purge\Plugin\Purge\Purger\PurgerInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge_purger_http\Plugin\Purge\Purger\HttpPurgerBase;

/**
 * HTTP Bundled Purger
 *
 * @PurgePurger(
 *   id = "httpbundled",
 *   label = @Translation("HTTP Bundled Purger"),
 *   configform = "\Drupal\purge_purger_http\Form\HttpBundledPurgerForm",
 *   cooldown_time = 0.0,
 *   description = @Translation("Configurable purger that sends a single HTTP request for a set of invalidation instructions."),
 *   multi_instance = TRUE,
 *   types = {},
 * )
 */
class HttpBundledPurger extends HttpPurgerBase implements PurgerInterface {

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $invalidations) {

    // Create a simple closure to mass-update states on the objects.
    $set_state = function($state) use($invalidations) {
      foreach ($invalidations as $invalidation) {
        $invalidation->setState($state);
      }
    };

    // Build up a single HTTP request, execute it and log errors.
    $logger = \Drupal::logger('purge_purger_http');
    $token_data = ['invalidations' => $invalidations];
    $uri = $this->getUri($token_data);
    $opt = $this->getOptions($token_data);

    try {
      $this->client->request($this->settings->request_method, $uri, $opt);
      $set_state(InvalidationInterface::SUCCEEDED);
    }
    catch (\Exception $e) {
      $set_state(InvalidationInterface::FAILED);
      $headers = $opt['headers'];
      unset($opt['headers']);
      $logger->emergency(
        "%exception thrown by %id, invalidation marked as failed. URI: %uri# METHOD: %request_method# HEADERS: %headers#mOPT: %opt#MSG: %exceptionmsg#",
        [
          '%exception' => get_class($e),
          '%exceptionmsg' => $e->getMessage(),
          '%request_method' => $this->settings->request_method,
          '%opt' => $this->exportDebuggingSymbols($opt),
          '%headers' => $this->exportDebuggingSymbols($headers),
          '%uri' => $uri,
          '%id' => $this->getid()
        ]
      );
    }
  }

}
