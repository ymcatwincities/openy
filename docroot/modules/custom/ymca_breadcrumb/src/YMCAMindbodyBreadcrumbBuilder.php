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
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\ymca_page_context\PageContextService;
use Drupal\Core\Path\AliasManager;

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
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Page Context.
   *
   * @var \Drupal\ymca_page_context\PageContextService
   */
  protected $pagecontextService;

  /**
   * Path Alias Manager.
   *
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $aliasManager;

  /**
   * YMCAMindbodyBreadcrumbBuilder constructor.
   */
  public function __construct(QueryFactory $entityQuery, EntityTypeManagerInterface $entityTypeManager, RequestStack $requestStack, PageContextService $pagecontextService, AliasManager $aliasManager) {
    $this->entityQuery = $entityQuery;
    $this->entityTypeManager = $entityTypeManager;
    $this->request = $requestStack;
    $this->pagecontextService = $pagecontextService;
    $this->aliasManager = $aliasManager;
  }

  /**
   * Handled routes.
   *
   * @var array
   */
  static private $routes = [
    'ymca_mindbody.pt',
    'ymca_mindbody.pt.results',
    'ymca_mindbody.location.pt',
    'ymca_mindbody.location.pt.results'
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
    if ($site_context = $this->pagecontextService->getContext()) {
      $query = $this->request->getCurrentRequest()->query->all();
      if (isset($query['context']) && isset($query['location']) && is_numeric($query['location'])) {
        $node_uri = $this->aliasManager->getAliasByPath('/node/' . $site_context->id());
        $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
        $breadcrumb->addLink(Link::createFromRoute($this->t('Locations'), 'ymca_frontend.locations'));
        $breadcrumb->addLink(Link::fromTextAndUrl($site_context->getTitle(), Url::fromUri('internal:' . $node_uri)));
        $breadcrumb->addLink(Link::fromTextAndUrl($this->t('Health & Fitness'), Url::fromUri('internal:' . $node_uri . '/health__fitness')));
        $breadcrumb->addLink(Link::fromTextAndUrl($this->t('Personal Training'), Url::fromUri('internal:' . $node_uri . '/health__fitness/personal_training')));
        // Try to get trainer from mapping.
        if (isset($query['trainer']) && $query['trainer'] !== 'all' && $query['context'] == 'trainer') {
          $mapping_id = $this->entityQuery
            ->get('mapping')
            ->condition('type', 'trainer')
            ->condition('field_mindbody_trainer_id', $query['trainer'])
            ->execute();
          $mapping_id = reset($mapping_id);
          if (is_numeric($mapping_id) && $mapping = $this->entityTypeManager->getStorage('mapping')->load($mapping_id)) {
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
    else {
      // Default breadcrumbs.
      $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
      $breadcrumb->addLink(Link::fromTextAndUrl($this->t('Health & Fitness'), Url::fromUri('internal:/health__fitness')));
      $breadcrumb->addLink(Link::fromTextAndUrl($this->t('Personal Training'), Url::fromUri('internal:/health__fitness/personal_training')));
      $breadcrumb->addCacheContexts(['url.query_args']);
    }
    return $breadcrumb;

  }

}
