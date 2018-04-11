<?php

namespace Drupal\openy_campaign\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
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
class MigrationCampaignMenu extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The process plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $processPluginManager;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The migration to be executed.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrationPluginManagerInterface $migration_plugin_manager, MigratePluginManagerInterface $process_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->processPluginManager = $process_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.migrate.process')
    );
  }

  /**
   * {@inheritdoc}
   *
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $result = [];
    if (empty($this->configuration['source'])
      || empty($this->configuration['migration'])
      || empty($value)
    ) {
      return serialize($result);
    }

    $migrationLookupConfig = [
      'migration' => $this->configuration['migration'],
    ];
    $migration = new MigrationLookup($migrationLookupConfig, '', [], $this->migration, $this->migrationPluginManager, $this->processPluginManager);

    foreach ($value as $landingPage) {
      $landingPageId = $migration->transform($landingPage['landing_page_id'], $migrate_executable, $row, '');
      $result[$landingPageId]['links'] = [];

      foreach ($landingPage['links'] as $link) {
        $linkPageId = $migration->transform($link['page'], $migrate_executable, $row, '');

        $result[$landingPageId]['links'][] = [
          'page' => [
            '0' => [
              'target_id' => $linkPageId,
            ]
          ],
          'weight' => $link['weight'],
          'title' => $link['title'],
          'logged' => $link['logged'],
        ];

      }
    }

    return serialize($result);
  }

}
