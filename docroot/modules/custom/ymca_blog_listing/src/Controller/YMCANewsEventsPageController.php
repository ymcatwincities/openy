<?php

/**
 * @file
 * Contains \Drupal\ymca_blog_listing\Controller\YMCANewsEventsPageController.
 */

namespace Drupal\ymca_blog_listing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\views\ViewsData;
use Drupal\node\NodeInterface;

/**
 * Class YMCANewsEventsPageController.
 *
 * @package Drupal\ymca_blog_listing\Controller
 */
class YMCANewsEventsPageController extends ControllerBase {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request_stack;

  /**
   * Drupal\views\ViewsData definition.
   *
   * @var Drupal\views\ViewsData
   */
  protected $views_views_data;
  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $request_stack, ViewsData $views_views_data) {
    $this->request_stack = $request_stack;
    $this->views_views_data = $views_views_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('views.views_data')
    );
  }

  /**
   * Generate page content.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node object.
   *
   * @return array
   *   Return render array.
   */
  public function pageView(NodeInterface $node) {
    \Drupal::service('pagecontext.service')->setContext($node);
    $view = views_embed_view('camp_blog_listing', 'blog_listing_embed', $node->id());

    return [
      'view' => $view,
    ];
  }

}
