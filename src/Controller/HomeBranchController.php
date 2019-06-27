<?php

namespace Drupal\openy_home_branch\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Home Branch Controller for vue app.
 */
class HomeBranchController extends ControllerBase {

  /**
   * The Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Creates a new HomeBranchController.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The Database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLocations() {
    $query = $this->database->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->condition('n.status', 1);
    $query->condition('n.type', 'branch');
    $query->orderBy('n.title');
    $query->addTag('openy_home_branch_get_locations');
    $result = $query->execute()->fetchAll();
    return new JsonResponse($result);
  }

}
