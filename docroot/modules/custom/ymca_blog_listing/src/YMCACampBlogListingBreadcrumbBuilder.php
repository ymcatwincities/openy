<?php

namespace Drupal\ymca_blog_listing;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\system\PathBasedBreadcrumbBuilder;

/**
 * Class YMCACampBlogListingBreadcrumbBuilder.
 *
 * @package Drupal\ymca_blog_listing.
 */
class YMCACampBlogListingBreadcrumbBuilder extends PathBasedBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'ymca_blog_listing.news_events_page_controller';
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = parent::build($route_match);
    $breadcrumb->addLink(Link::createFromRoute($this->t('News & Events'), '<none>'));

    return $breadcrumb;
  }

}
