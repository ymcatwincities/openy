<?php

namespace Drupal\ymca_search_alter;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;

/**
 * Breadcrumb service for search.
 */
class SearchResultsBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;
  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'search.view_content';
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $links = [
      Link::createFromRoute($this->t('Home'), '<front>'),
      Link::createFromRoute($this->t('Search results'), '<none>'),
    ];
    if (isset($_GET['f'][0]) && $_GET['f'][0] == 'type:blog') {
      $links = [
        Link::createFromRoute($this->t('Home'), '<front>'),
        Link::createFromRoute($this->t('Blog'), 'view.ymca_twin_cities_blog.blog_page'),
        Link::createFromRoute($this->t('Search Blog'), '<none>'),
      ];
    }
    $breadcrumb->setLinks($links);
    $breadcrumb->addCacheContexts(['url.path', 'url.query_args:f']);

    return $breadcrumb;
  }

}
