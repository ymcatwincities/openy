<?php
/**
 * @file
 * Contains \Drupal\simple_sitemap\Controller\SimplesitemapController.
 */

namespace Drupal\simple_sitemap\Controller;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * SimplesitemapController.
 */
class SimplesitemapController extends ControllerBase {

  /**
   * The sitemap generator.
   *
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  protected $sitemapGenerator;

  /**
   * Returns the whole sitemap, a requested sitemap chunk, or the sitemap index file.
   *
   * @param int $sitemap_id
   *  Optional ID of the sitemap chunk. If none provided, the first chunk or
   *  the sitemap index is fetched.
   *
   * @return object Response
   *  Returns an XML response.
   */
  public function getSitemap($sitemap_id = NULL) {
    $output = $this->sitemapGenerator->getSitemap($sitemap_id);
    $output = !$output ? '' : $output;

    // Display sitemap with correct xml header.
    $response = new CacheableResponse($output, Response::HTTP_OK, array('content-type' => 'application/xml'));
    $meta_data = $response->getCacheableMetadata();
    $meta_data->addCacheTags(['simple_sitemap']);
    return $response;
  }

  /**
   * SimplesitemapController constructor.
   *
   * @param \Drupal\simple_sitemap\Simplesitemap $sitemap_generator
   *   The sitemap generator.
   */
  public function __construct($sitemap_generator) {
    $this->sitemapGenerator = $sitemap_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('simple_sitemap.generator'));
  }

}
