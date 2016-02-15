<?php

namespace Drupal\ymca_breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;

/**
 * Class YMCANewsBlogBreadcrumbBuilder.
 *
 * @package Drupal\ymca_breadcrumb.
 */
class YMCANewsBlogListingsBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;
  use LinkGeneratorTrait;

  /**
   * Handled routes.
   *
   * @var array
   */
  static private $routes = [
    'view.ymca_news.page_news',
    'view.ymca_news_archive.page_news',
    'view.ymca_twin_cities_blog.blog_page',
    'view.ymca_twin_cities_blog_archive.blog_page',
  ];

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if (in_array($route_match->getRouteName(), self::$routes)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));

    switch ($route_match->getRouteName()) {
      case 'view.ymca_news.page_news':
        $breadcrumb->addLink(Link::createFromRoute($this->t('News'), '<none>'));
        break;

      case 'view.ymca_news_archive.page_news':
        $breadcrumb->addLink(Link::createFromRoute($this->t('News'), 'view.ymca_news.page_news'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('YMCA News Archive'), '<none>'));
        break;

      case 'view.ymca_twin_cities_blog.blog_page':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Blog'), '<none>'));
        break;

      case 'view.ymca_twin_cities_blog_archive.blog_page':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Blog'), 'view.ymca_twin_cities_blog.blog_page'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Archive'), '<none>'));
        break;
    }
    $breadcrumb->addCacheContexts(['url.path']);

    return $breadcrumb;
  }

}
