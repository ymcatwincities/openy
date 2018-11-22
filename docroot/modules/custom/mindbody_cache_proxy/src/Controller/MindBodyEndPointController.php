<?php

namespace Drupal\mindbody_cache_proxy\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class MindBodyEndPointController.
 *
 * @package Drupal\mindbody_cache_proxy\Controller
 */
class MindBodyEndPointController extends ControllerBase {

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Cache proxy.
   *
   * @var \Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface
   */
  protected $cacheProxy;

  /**
   * MindBodyEndPointController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack.
   * @param \Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface $cacheProxy
   *   Cache proxy.
   */
  public function __construct(RequestStack $requestStack, MindbodyCacheProxyInterface $cacheProxy) {
    $this->requestStack = $requestStack;
    $this->cacheProxy = $cacheProxy;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('mindbody_cache_proxy.client')
    );
  }

  /**
   * Provides "status" endpoint.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json formatted response.
   */
  public function endPointStatus() {
    // We should increment 'miss' count each time endpoint accessed.
    $this->cacheProxy->updateStats('miss');

    $result = ['status' => $this->cacheProxy->getStatus()];
    return new JsonResponse($result);
  }

  /**
   * Check access to "status" endpoint.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If $condition is TRUE, isAllowed() will be TRUE, otherwise isNeutral()
   *   will be TRUE.
   */
  public function endPointStatusAccess() {
    // Allow endpoint only on the primary host.
    $is_primary = $this->config('mindbody_cache_proxy.settings')->get('primary');

    // Server and client tokens should match.
    $is_valid = FALSE;
    $token_server = $this->config('mindbody_cache_proxy.settings')->get('token');
    $token_client = $this->requestStack->getCurrentRequest()->get('token');
    if (0 === strcmp($token_server, $token_client)) {
      $is_valid = TRUE;
    }

    return AccessResult::allowedIf($is_primary && $is_valid);
  }

}
