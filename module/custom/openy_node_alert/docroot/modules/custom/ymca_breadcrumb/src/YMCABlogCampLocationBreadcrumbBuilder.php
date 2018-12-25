<?php

namespace Drupal\ymca_breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing\RequestContext;
use Drupal\system\PathBasedBreadcrumbBuilder;
use Drupal\Core\Access\AccessManagerInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;

/**
 * Class YMCABlogCampLocationBreadcrumbBuilder.
 *
 * @package Drupal\ymca_breadcrumb.
 */
class YMCABlogCampLocationBreadcrumbBuilder extends PathBasedBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use LinkGeneratorTrait;

  /**
   * Current node.
   *
   * @var object
   */
  private $node;

  /**
   * Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
  public function __construct(RequestContext $context, AccessManagerInterface $access_manager, RequestMatcherInterface $router, InboundPathProcessorInterface $path_processor, ConfigFactoryInterface $config_factory, TitleResolverInterface $title_resolver, AccountInterface $current_user, CurrentPathStack $current_path) {
    parent::__construct($context, $access_manager, $router, $path_processor, $config_factory, $title_resolver, $current_user, $current_path);
    $this->configFactory = $config_factory;
  }

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
    if ($node->bundle() != 'blog') {
      return FALSE;
    }
    $this->node = $node;
    $context = \Drupal::service('pagecontext.service')->getContext();
    if (!$context) {
      return FALSE;
    }
    if ($context->bundle() == 'location' || $context->bundle() == 'camp') {
      return TRUE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $context = \Drupal::service('pagecontext.service')->getContext();
    $url = Url::fromRoute('ymca_blog_listing.news_events_page_controller', ['node' => $context->id()]);
    $path_alias = \Drupal::service('path.alias_manager')->getAliasByPath('/' . $url->getInternalPath(), 'en');
    $path = trim($path_alias, '/');

    $request_context = new RequestContext(
      $this->context->getBaseUrl(),
      $this->context->getMethod(),
      $this->context->getHost(),
      $this->context->getScheme(),
      $this->context->getHttpPort(),
      $this->context->getHttpsPort(),
      $path
    );

    // Path-based builder with modified request context.
    $path_based_breadcrumb_builder = new PathBasedBreadcrumbBuilder(
      $request_context,
      $this->accessManager,
      $this->router,
      $this->pathProcessor,
      $this->configFactory,
      $this->titleResolver,
      $this->currentUser,
      $this->currentPath
    );

    /*
     * @var \Drupal\Core\Breadcrumb\Breadcrumb
     */
    $breadcrumb = $path_based_breadcrumb_builder->build($route_match);
    $breadcrumb->addLink(Link::fromTextAndUrl($this->t('News & Events'), $url));
    $breadcrumb->addLink(Link::createFromRoute($this->node->getTitle(), '<none>'));

    return $breadcrumb;
  }

}
