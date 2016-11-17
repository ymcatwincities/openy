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
 * Class YMCAPersonifyBreadcrumbBuilder.
 *
 * @package Drupal\ymca_breadcrumb.
 */
class YMCAPersonifyBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;
  use LinkGeneratorTrait;

  /**
   * Handled routes.
   *
   * @var array
   */
  static private $routes = [
    'ymca_personify.childcare_payment_history',
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
    $breadcrumb->addLink(Link::createFromRoute($this->t('MyY'), 'ymca_personify.personify_account'));
    return $breadcrumb;
  }

}
