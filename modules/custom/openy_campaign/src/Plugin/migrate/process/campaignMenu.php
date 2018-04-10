<?php

namespace Drupal\openy_campaign\Plugin\migrate\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This plugin figures out menu link parent plugin IDs.
 *
 * @MigrateProcessPlugin(
 *   id = "campaign_menu",
 *   handle_multiples = TRUE
 * )
 */
class campaignMenu extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $migrationPlugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrateProcessInterface $migration_plugin) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrationPlugin = $migration_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $migration_configuration['migration'][] = $migration->id();
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.migrate.process')->createInstance('migration', $migration_configuration, $migration)
    );
  }

  /**
   * {@inheritdoc}
   *
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    print_r($value);
    print_r($row);


    $menu = [];
    foreach ($value as $landing) {
      foreach ($landing['pages'] as $menuItem) {
        $menu[$landing->id]['links'][] = [
          'page' => [
            'target_id' => $menuItem['title']
          ],
          'weight' => $menuItem['weight'],
          'title' => $menuItem['title'],
          'logged' => 0
        ];
      }
    }

  }

}
