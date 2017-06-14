<?php

namespace Drupal\mindbody_cache_proxy\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class MindBodyEndPointController.
 *
 * @package Drupal\mindbody_cache_proxy\Controller
 */
class MindBodyEndPointController extends ControllerBase {

  protected $requestStack;

  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Get status.
   */
  protected function getStatus() {
    $stats = $this->state()->get('mindbody_cache_proxy');
    $calls = $this->config('mindbody_cache_proxy.settings')->get('calls');

    if ($stats->miss >= $calls) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Provides "status" endpoint.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function endPointStatus() {
    $result = ['status' => $this->getStatus()];
    return new JsonResponse($result);
  }

  /**
   * Check access to "status" endpoint.
   *
   * @return \Drupal\Core\Access\AccessResult
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
