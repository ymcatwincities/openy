<?php

namespace Drupal\ymca_breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\system\PathBasedBreadcrumbBuilder;

/**
 * Class YMCAPathBasedBreadcrumbBuilder.
 *
 * @package Drupal\ymca_breadcrumb.
 */
class YMCAPathBasedBreadcrumbBuilder extends PathBasedBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = parent::build($route_match);

    $path = trim($this->context->getPathInfo(), '/');
    $front = $this->config->get('page.front');
    if ($path && '/' . $path != $front) {
      $title = $this->titleResolver->getTitle(\Drupal::request(), $route_match->getRouteObject());

      // Add current page title.
      $breadcrumb->addLink(Link::createFromRoute($title, '<none>'));
    }

    return $breadcrumb;
  }

}
