<?php

namespace Drupal\ymca_retention\Plugin\views\display;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Render\RendererInterface;
use Drupal\views_rest_feed\Plugin\views\display\RestExportFeed;

/**
 * The plugin that handles Data response callbacks for REST resources.
 *
 * The plugin disable cache tags for this display.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "ymca_retention_rest_export",
 *   title = @Translation("YMCA Retention REST export feed"),
 *   help = @Translation("Create a REST export resource feed."),
 *   uses_route = TRUE,
 *   admin = @Translation("REST export feed"),
 *   returns_response = TRUE
 * )
 */
class YmcaRetentionRestExport extends RestExportFeed {

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

    $cache_metadata->setCacheTags([]);
    $cache_metadata->setCacheMaxAge(0);

    $response->addCacheableDependency($cache_metadata);

    $response->headers->set('Content-type', $build['#content_type']);

    return $response;
  }

}
