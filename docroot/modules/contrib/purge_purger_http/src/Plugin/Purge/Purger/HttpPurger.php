<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Plugin\Purge\Purger\HttpPurger.
 */

namespace Drupal\purge_purger_http\Plugin\Purge\Purger;

use Drupal\purge\Plugin\Purge\Purger\PurgerInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge_purger_http\Plugin\Purge\Purger\HttpPurgerBase;

/**
 * HTTP Purger
 *
 * @PurgePurger(
 *   id = "http",
 *   label = @Translation("HTTP Purger"),
 *   configform = "\Drupal\purge_purger_http\Form\HttpPurgerForm",
 *   cooldown_time = 0.0,
 *   description = @Translation("Configurable purger that makes HTTP requests for each given invalidation instruction."),
 *   multi_instance = TRUE,
 *   types = {},
 * )
 */
class HttpPurger extends HttpPurgerBase implements PurgerInterface {

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $invalidations) {
    $logger = \Drupal::logger('purge_purger_http');

    // Iterate every single object and fire a request per object.
    foreach ($invalidations as $invalidation) {
      $token_data = ['invalidation' => $invalidation];
      $uri = $this->getUri($token_data);
      $opt = $this->getOptions($token_data);

      try {
        $this->client->request($this->settings->request_method, $uri, $opt);
        $invalidation->setState(InvalidationInterface::SUCCEEDED);
      }
      catch (\Exception $e) {
        $invalidation->setState(InvalidationInterface::FAILED);
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

}
