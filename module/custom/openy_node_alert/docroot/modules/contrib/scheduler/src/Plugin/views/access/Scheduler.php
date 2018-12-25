<?php

namespace Drupal\scheduler\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides access control for Scheduler.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "scheduler",
 *   title = @Translation("Scheduled content access"),
 *   help = @Translation("All Scheduler users can see their own scheduled content via their user page. In addition, if they have 'view scheduled content' permission they will be able to see all scheduled content by all authors."),
 * )
 */
class Scheduler extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return \Drupal::service('access_checker.scheduler_content')->access($account);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_access_scheduler_content', 'TRUE');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
