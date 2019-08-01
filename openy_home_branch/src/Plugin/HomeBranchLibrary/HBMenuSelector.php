<?php

namespace Drupal\openy_home_branch\Plugin\HomeBranchLibrary;

use Drupal\openy_home_branch\HomeBranchLibraryBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the home branch library plugin for user menu block.
 *
 * @HomeBranchLibrary(
 *   id="hb_menu_selector",
 *   label = @Translation("Home Branch Menu Selector"),
 *   entity="block"
 * )
 */
class HBMenuSelector extends HomeBranchLibraryBase implements ContainerFactoryPluginInterface {

  /**
   * The Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'openy_home_branch/menu_selector';
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowedForAttaching($variables) {
    return ($variables['plugin_id'] == 'system_menu_block:account');
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrarySettings() {
    // Get locations list.
    $query = $this->database->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->condition('n.status', 1);
    $query->condition('n.type', 'branch');
    $query->orderBy('n.title');
    $query->addTag('openy_home_branch_get_locations');
    $query->addTag('node_access');
    $result = $query->execute()->fetchAllKeyed();

    return [
      'menuSelector' => '.nav-global .page-head__top-menu ul.navbar-nav',
      'locations' => $result,
    ];
  }

}
