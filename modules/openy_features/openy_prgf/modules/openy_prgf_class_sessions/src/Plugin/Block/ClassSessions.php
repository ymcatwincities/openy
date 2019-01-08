<?php

namespace Drupal\openy_prgf_class_sessions\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\openy_prgf_class_sessions\ClassSessionsServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a leader board block.
 *
 * @Block(
 *   id = "class_sessions",
 *   admin_label = @Translation("Class Sessions block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class ClassSessions extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Class Sessions service.
   *
   * @var \Drupal\openy_prgf_class_sessions\ClassSessionsServiceInterface
   */
  protected $classSessionsService;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new ClassSessions.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param ClassSessionsServiceInterface $class_sessions_service
   *   The Class Sessions service.
   * @param RouteMatchInterface $route_match
   *   The Route match service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClassSessionsServiceInterface $class_sessions_service, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->classSessionsService = $class_sessions_service;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('openy_prgf_class_sessions.sessions_handler'),
      $container->get('current_route_match')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Extract node from route, return empty if none.
    if (!$node = $this->routeMatch->getParameter('node')) {
      return [];
    }

    // Array of key (field) value pairs.
    $conditions = [];

    // Set cache contexts with 'url.query_args'.
    $contexts[] = 'url.query_args';

    // Set cache tags from node.
    $tags = $node->getCacheTags();

    // Location parameter add to conditions.
    $conditions['location'] = $this->getQueryParamInt('location');

    // If session parameter is set add to conditions.
    if (!empty($session_id = $this->getQueryParamInt('session'))) {
      $conditions['session'] = $session_id;
    }

    $class_sessions = [
      '#theme' => 'class_sessions',
      '#cache' => [
        'tags' => Cache::mergeTags(['class_sessions'], $tags),
        'contexts' => $contexts,
      ],
    ];

    if (!empty($location_id)) {
      $class_sessions['#conditions_location'] = $location_id;
    }

    // There is no session instances associated with the class node.
    if (!$session_instances = $this->classSessionsService->getClassNodeSessionInstances($node, $conditions)) {
      return $class_sessions;
    }

    // Get Session Instances rows.
    $session_instances_rows = $this->classSessionsService->getSessionInstancesRows($session_instances, $tags);

    $class_sessions['#session_instances_rows'] = $session_instances_rows;

    return $class_sessions;
  }

  private function getQueryParamInt($param) {
    // Get request object for query param.
    $request = \Drupal::request();

    // If param is set return it's value.
    if (!empty($request->query->get($param)) && filter_var($request->query->get($param), FILTER_VALIDATE_INT) !== FALSE) {
      return $request->query->get($param);
    }

    return NULL;
  }

}
