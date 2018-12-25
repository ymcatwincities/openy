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
   * Routes array.
   *
   * @var array
   */
  protected $routes;

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
    $this->routes = [
      'ymca_groupex.all_schedules_search',
      'ymca_groupex.all_schedules_search_results',
      'ymca_frontend.location_schedules',
      'ymca_blog_listing.news_events_page_controller',
      'entity.openy_digital_signage_screen.canonical',
      'ymca_frontend.2014_annual_report',
      'ymca_frontend.2014_annual_report_leaders',
      'openy_hf.footer',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if (in_array($route_match->getRouteName(), $this->routes)) {
      return TRUE;
    }

    if ($route_match->getRouteName() == 'entity.node.canonical' && $node = $route_match->getParameter('node')) {
      if ($node->bundle() == 'article' || $node->bundle() == 'location' || $node->bundle() == 'camp') {
        return TRUE;
      }

      // Blog post related to News & Events also should have old theme.
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $section */
      if ($node->hasField('field_site_section')) {
        $section = $node->field_site_section;
        if (!$section->isEmpty()) {
          return TRUE;
        }
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
