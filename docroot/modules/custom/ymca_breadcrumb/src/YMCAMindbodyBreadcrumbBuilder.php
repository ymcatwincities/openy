<?php

namespace Drupal\ymca_breadcrumb;

use Drupal\ymca_mindbody\Form\MindbodyPTForm;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class YMCAMindbodyBreadcrumbBuilder.
 *
 * @package Drupal\ymca_breadcrumb.
 */
class YMCAMindbodyBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;
  use LinkGeneratorTrait;

  /**
   * YMCAMindbodyBreadcrumbBuilder constructor.
   */
  public function __construct() {
    $container = \Drupal::getContainer();
    $this->proxy = $container->get('mindbody_cache_proxy.client');
    $this->trainingsMapping = $container->get('ymca_mindbody.trainings_mapping');
    $this->requestGuard = $container->get('ymca_mindbody.request_guard');
    $this->entityQuery = $container->get('entity.query');
    $this->entityTypeManager = $container->get('entity_type.manager');
  }

  /**
   * Handled routes.
   *
   * @var array
   */
  static private $routes = [
    'ymca_mindbody.pt',
    'ymca_mindbody.pt.results'
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
    if ($site_context = \Drupal::service('pagecontext.service')->getContext()) {
      $query = \Drupal::request()->query->all();
      if (isset($query['location']) && is_numeric($query['location'])) {
        $node_uri = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $site_context->id());
        $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Locations'), 'ymca_frontend.locations'));
        $breadcrumb->addLink(Link::fromTextAndUrl($site_context->getTitle(), Url::fromUri('internal:' . $node_uri)));
        $breadcrumb->addLink(Link::fromTextAndUrl($this->t('Health & Fitness'), Url::fromUri('internal:' . $node_uri . '/health__fitness')));
        $breadcrumb->addLink(Link::fromTextAndUrl($this->t('Personal Training'), Url::fromUri('internal:' . $node_uri . '/health__fitness/personal_training')));
        if (isset($query['trainer']) && $query['trainer'] !== 'all') {
          $form = new MindbodyPTForm($this->proxy, $this->trainingsMapping, $this->requestGuard, $this->entityQuery, $this->entityTypeManager);
          $trainers = $form->getTrainers($query['session_type'], $query['location']);
          $trainer_name = isset($trainers[$query['trainer']]) ? $trainers[$query['trainer']] : '';
          $trainer_name_safe = strtolower($trainer_name);
          $trainer_name_safe = str_replace(' ', '_', $trainer_name_safe);
          $breadcrumb->addLink(Link::fromTextAndUrl($trainer_name, Url::fromUri('internal:' . $node_uri . '/health__fitness/personal_training/' . $trainer_name_safe)));
        }
        if ($route_match->getRouteName() == 'ymca_mindbody.pt.results') {
          $url = Url::fromRoute('ymca_mindbody.pt')->toString();
          $breadcrumb->addLink(Link::fromTextAndUrl($this->t('Book Personal Training'), Url::fromUri('internal:' . $url, ['query' => ['location' => $query['location']]])));
        }
        $breadcrumb->addCacheContexts(['url.query_args']);
      }
    }
    return $breadcrumb;

  }

}
