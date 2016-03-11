<?php

/**
 * @file
 * Contains \Drupal\sitemap\Controller\SitemapController.
 */

namespace Drupal\sitemap\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for update routes.
 */
class SitemapController implements ContainerInjectionInterface {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs update status data.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module Handler Service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * Controller for /sitemap.
   *
   * @return array
   *   Renderable string.
   */
  public function buildPage() {
    $sitemap = array(
      '#theme' => 'sitemap',
    );

    $config = \Drupal::config('sitemap.settings');
    if ($config->get('css') != 1) {
      $sitemap['#attached']['library'] = array(
        'sitemap/sitemap.theme',
      );
    }

    return $sitemap;
  }

  /**
   * Returns sitemap page's title.
   *
   * @return string
   *   Sitemap page title.
   */
  public function getTitle() {
    $config = \Drupal::config('sitemap.settings');
    return $config->get('page_title');
  }

}
