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
 * Class YMCANewsBlogBreadcrumbBuilder.
 *
 * @package Drupal\ymca_breadcrumb.
 */
class YMCANewsBlogBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;
  use LinkGeneratorTrait;

  /**
   * News taxonomy term id.
   *
   * @var int
   */
  const NEWS_TERM_TID = 6;

  /**
   * Current node.
   *
   * @var object
   */
  private $node;

  /**
   * This builder's handled routes.
   *
   * @var array
   */
  static private $routes = [
    'entity.node.preview',
    'entity.node.canonical',
  ];

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if (!in_array($route_match->getRouteName(), self::$routes)) {
      return FALSE;
    }
    if (!$node = $route_match->getParameter('node')) {
      if (!$node = $route_match->getParameter('node_preview')) {
        return FALSE;
      }
    }
    if ($node->bundle() != 'blog' || $node->field_site_section->getValue()) {
      return FALSE;
    }
    $this->node = $node;

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    // Check if the node is a 'News'.
    $is_news = FALSE;
    if ($field_tags_value = $this->node->field_tags->getValue()) {
      foreach ($field_tags_value as $id) {
        if ($id['target_id'] == self::NEWS_TERM_TID) {
          $is_news = TRUE;
          break;
        }
      }
    }
    $listing_name = $is_news ? 'News' : 'Blog';
    $listing_uri = $is_news ? 'internal:/news' : 'internal:/blog';

    // Special logic for Day Camp News.
    if ($this->node->hasField('field_related_camps_locations')) {
      $day_camp = $this->node->get('field_related_camps_locations');
      if (!$day_camp->isEmpty()) {
        $listing_name = 'Day Camp News';
        $listing_uri = 'internal:/day_camp_news';
      }
    }

    $breadcrumb = new Breadcrumb();
    $breadcrumb->setLinks([
      Link::createFromRoute($this->t('Home'), '<front>'),
      Link::fromTextAndUrl($this->t($listing_name), Url::fromUri($listing_uri)),
      Link::createFromRoute($this->node->getTitle(), '<none>'),
    ]);
    $breadcrumb->addCacheContexts(['url.path']);

    return $breadcrumb;
  }

}
