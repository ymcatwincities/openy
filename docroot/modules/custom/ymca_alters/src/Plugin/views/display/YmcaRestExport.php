<?php

namespace Drupal\ymca_alters\Plugin\views\display;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\rest\Plugin\views\display\RestExport;
use Drupal\views\Plugin\views\display\ResponseDisplayPluginInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * The plugin that handles Data response callbacks for REST resources.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "ymca_rest_export",
 *   title = @Translation("YMCA REST export"),
 *   help = @Translation("Create a REST export resource."),
 *   uses_route = TRUE,
 *   admin = @Translation("YMCA REST export"),
 *   returns_response = TRUE
 * )
 */
class YmcaRestExport extends RestExport implements ResponseDisplayPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteProviderInterface $route_provider, StateInterface $state, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_provider, $state, $renderer);
  }

  /**
   * {@inheritdoc}
   */
  public static function buildResponse($view_id, $display_id, array $args = []) {
    $build = static::buildBasicRenderable($view_id, $display_id, $args);

    /** @var RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $output = $renderer->renderRoot($build);

    $response = new CacheableResponse($output, 200);
    $cache_metadata = CacheableMetadata::createFromRenderArray($build);

    if ($view_id == 'contact_messages_csv_export') {
      $cache_metadata->setCacheTags([]);
      $cache_metadata->setCacheMaxAge(0);
    }

    $response->addCacheableDependency($cache_metadata);

    $response->headers->set('Content-type', $build['#content_type']);

    return $response;
  }

}
