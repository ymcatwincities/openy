<?php

namespace Drupal\ymca_alters\Theme;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Sets the active theme on old ymca pages.
 */
class YmcaNegotiator implements ThemeNegotiatorInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new YmcaNegotiator instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($route_match->getRouteName() == 'entity.node.canonical' && $node = $route_match->getParameter('node')) {
      if ($node->bundle() == 'article' || $node->bundle() == 'location' || $node->bundle() == 'camp') {
        return TRUE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return 'ymca';
  }

}
