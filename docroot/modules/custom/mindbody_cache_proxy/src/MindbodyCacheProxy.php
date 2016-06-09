<?php

namespace Drupal\mindbody_cache_proxy;

use Drupal\mindbody\MindbodyClientInterface;

/**
 * Class MindbodyProxy.
 *
 * @package Drupal\ymca_mindbody
 */
class MindbodyCacheProxy implements MindbodyCacheProxyInterface {

  /**
   * MindbodyClient.
   *
   * @var MindbodyClientInterface
   */
  protected $mindbodyClient;

  /**
   * MindbodyProxy constructor.
   *
   * @param MindbodyClientInterface $mindbody_client
   */
  public function __construct(MindbodyClientInterface $mindbody_client) {
    $this->mindbodyClient = $mindbody_client;
  }

  /**
   * {@inheritdoc}
   */
  public function call($service, $endpoint, array $params = []) {
    return $this->mindbodyClient->call($service, $endpoint, $params);
  }

}
