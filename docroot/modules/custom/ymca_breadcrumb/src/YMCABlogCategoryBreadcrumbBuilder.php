<?php

namespace Drupal\ymca_breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class YMCABlogCategoryBreadcrumbBuilder.
 *
 * @package Drupal\ymca_breadcrumb.
 */
class YMCABlogCategoryBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;
  use LinkGeneratorTrait;

  /**
   * Current term.
   *
   * @var object
   */
  private $term;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($route_match->getRouteName() != 'entity.taxonomy_term.canonical') {
      return FALSE;
    }
    if (!$term = $route_match->getParameter('taxonomy_term') or $term->bundle() !== 'tags') {
      return FALSE;
    }
    $this->term = $term;

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->setLinks([
      Link::createFromRoute($this->t('Home'), '<front>'),
      Link::fromTextAndUrl($this->t('Blog'), Url::fromUri('internal:/blog')),
      Link::createFromRoute($this->term->getName(), '<none>'),
    ]);
    $breadcrumb->addCacheContexts(['url.path']);

    return $breadcrumb;
  }

}
