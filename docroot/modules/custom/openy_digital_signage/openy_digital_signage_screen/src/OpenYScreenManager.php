<?php

namespace Drupal\openy_digital_signage_screen;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class OpenYScreenManager.
 */
class OpenYScreenManager implements OpenYScreenManagerInterface {

  /**
   * Logger channel definition.
   */
  const CHANNEL = 'openy_digital_signage';

  /**
   * Collection name.
   */
  const STORAGE = 'openy_digital_signage_screen';

  /**
   * The query factory.
   *
   * @var QueryFactory
   */
  protected $entityQuery;

  /**
   * The entity type manager.
   *
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity storage.
   *
   * @var EntityStorageInterface
   */
  protected $storage;

  /**
   * LoggerChannelFactoryInterface definition.
   *
   * @var LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The Route Match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $entity_query, LoggerChannelFactoryInterface $logger_factory, RouteMatchInterface $route_match, RequestStack $request_stack) {
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get(self::CHANNEL);
    $this->storage = $this->entityTypeManager->getStorage(self::STORAGE);
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function dummy() {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getScreenContext() {
    $route_name = $this->routeMatch->getRouteName();
    $request = $this->requestStack->getCurrentRequest();
    if ($route_name == 'entity.openy_digital_signage_screen.canonical') {
      $screen = $request->get('openy_digital_signage_screen');
      return $screen;
    }
    else {
      $request = \Drupal::request();
      if ($request->query->has('screen')) {
        $storage = \Drupal::entityTypeManager()->getStorage('openy_digital_signage_screen');
        $screen = $storage->load($request->query->get('screen'));
        return $screen;
      }
    }

    return NULL;
  }

}
