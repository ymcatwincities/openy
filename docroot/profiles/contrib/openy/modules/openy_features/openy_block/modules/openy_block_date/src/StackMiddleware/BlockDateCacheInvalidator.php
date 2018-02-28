<?php

namespace Drupal\openy_block_date\StackMiddleware;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Invalidate date block tags before the main kernel takes over the request.
 */
class BlockDateCacheInvalidator implements HttpKernelInterface {

  /**
   * Date block tag.
   */
  const TAG = 'block_date';

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a PageCache object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   Cache tags invalidator.
   */
  public function __construct(HttpKernelInterface $http_kernel, CacheTagsInvalidatorInterface $cacheTagsInvalidator) {
    $this->httpKernel = $http_kernel;
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    if ($type === static::MASTER_REQUEST) {
      $response = $this->httpKernel->handle($request, $type, $catch);
    }
    else {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    if (!$response instanceof CacheableResponseInterface) {
      return $response;
    }

    $requestTime = $request->server->get('REQUEST_TIME');
    $allTags = $response->getCacheableMetadata()->getCacheTags();
    $tagPrefix = self::TAG . ":";

    // Try to find even single outdated timestamp.
    foreach ($allTags as $tag) {
      if (strpos($tag, $tagPrefix) === FALSE) {
        continue;
      }

      $timestamp = substr($tag, strlen($tagPrefix));
      if ($requestTime > $timestamp) {
        // Found outdated tag. Let's invalidate it! And handle request again.
        $this->cacheTagsInvalidator->invalidateTags([$tag]);
        return $this->httpKernel->handle($request, $type, $catch);
      }
    }

    return $response;
  }

}
