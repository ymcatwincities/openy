<?php

namespace Drupal\simple_sitemap\Controller;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\simple_sitemap\Simplesitemap;

/**
 * Class SimplesitemapController
 * @package Drupal\simple_sitemap\Controller
 */
class SimplesitemapController extends ControllerBase {

  /**
   * The sitemap generator.
   *
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  protected $generator;

  /**
   * SimplesitemapController constructor.
   *
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   *   The sitemap generator.
   */
  public function __construct(Simplesitemap $generator) {
    $this->generator = $generator;
  }

  /**
   * Returns the whole sitemap, a requested sitemap chunk, or the sitemap index file.
   *
   * @param int $chunk_id
   *   Optional ID of the sitemap chunk. If none provided, the first chunk or
   *   the sitemap index is fetched.
   *
   * @throws NotFoundHttpException
   *
   * @return object
   *   Returns an XML response.
   */
  public function getSitemap($chunk_id = NULL) {
    $output = $this->generator->getSitemap($chunk_id);
    if (!$output) {
      throw new NotFoundHttpException();
    }

    // Display sitemap with correct XML header.
    $response = new CacheableResponse($output, Response::HTTP_OK, ['content-type' => 'application/xml']);
    $meta_data = $response->getCacheableMetadata();
    $meta_data->addCacheTags(['simple_sitemap']);
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('simple_sitemap.generator'));
  }

}
