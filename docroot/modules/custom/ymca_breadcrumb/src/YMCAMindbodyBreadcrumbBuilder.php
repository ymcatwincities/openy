<?php

namespace Drupal\ymca_breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class YMCAMindbodyBreadcrumbBuilder.
 *
 * @package Drupal\ymca_breadcrumb.
 */
class YMCAMindbodyBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;
  use LinkGeneratorTrait;

  /**
   * Query Factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * YMCAMindbodyBreadcrumbBuilder constructor.
   */
  public function __construct(QueryFactory $entityQuery, EntityTypeManagerInterface $entityTypeManager) {
    $this->entityQuery = $entityQuery;
    $this->entityTypeManager = $entityTypeManager;
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
        // Try to get trainer from mapping.
        if (isset($query['trainer']) && $query['trainer'] !== 'all') {
          $mapping_id = $this->entityQuery
            ->get('mapping')
            ->condition('type', 'trainer')
            ->condition('field_mindbody_trainer_id', $query['trainer'])
            ->execute();
          $mapping_id = reset($mapping_id);
          if ($mapping = $this->entityTypeManager->getStorage('mapping')->load($mapping_id)) {
            $name = explode(', ', $mapping->getName());
            if (isset($name[0]) && isset($name[0])) {
              $trainer_name = $name[1] . ' ' . $name[0];
              $trainer_name_safe = strtolower($trainer_name);
              $trainer_name_safe = str_replace(' ', '_', $trainer_name_safe);
              $breadcrumb->addLink(Link::fromTextAndUrl($trainer_name, Url::fromUri('internal:' . $node_uri . '/health__fitness/personal_training/' . $trainer_name_safe)));
            }
          }
        }
        $breadcrumb->addCacheContexts(['url.query_args']);
      }
    }
    return $breadcrumb;

  }

}
