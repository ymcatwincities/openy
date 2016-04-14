<?php

/**
 * @file
 * Contains \Drupal\page_manager\Entity\PageAccessCheck.
 */

namespace Drupal\page_manager\Entity;

use Drupal\Core\Entity\EntityAccessCheck;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Mimics the generic entity access but with a custom key to prevent collisions.
 */
class PageAccessCheck extends EntityAccessCheck {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // Backup the original requirements.
    $original_requirements = $route->getRequirements();

    // Replace it with our entity access value and run the parent access check.
    $route->setRequirement('_entity_access', $route->getRequirement('_page_access'));
    $access = parent::access($route, $route_match, $account);

    // Restore the original requirements.
    $route->setRequirements($original_requirements);

    return $access;
  }

}
