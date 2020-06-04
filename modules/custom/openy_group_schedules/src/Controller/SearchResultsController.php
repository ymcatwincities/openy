<?php

namespace Drupal\openy_group_schedules\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Implements SearchResultsController.
 */
class SearchResultsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs All Search Results.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager.
   */

  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entityTypeManager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Show the page.
   */
  public function pageView(NodeInterface $node) {
    $conf = $this->configFactory->get('openy_group_schedules.settings');
    $max_age = is_numeric($conf->get('cache_max_age')) ? $conf->get('cache_max_age') : 3600;

    // It catches cases with old arguments and redirect to this page without arguments.
    // @var  \Symfony\Component\HttpFoundation\Request $request
    $request = \Drupal::request();
    $query = $request->query->all();
    if (array_key_exists('location', $query)) {
      unset($query['location']);
      return $this->redirect('ymca_frontend.location_schedules', ['node' => $node->id()], ['query' => $query]);
    }
    $viewBuilder = $this->entityTypeManager->getViewBuilder('node');
    $node_view = $viewBuilder->view($node, 'groupex');
    $markup = render($node_view);

    return [
      '#markup' => $markup,
      '#cache' => [
        'max-age' => $max_age,
        'contexts' => ['url.query_args'],
      ],
    ];
  }

}
