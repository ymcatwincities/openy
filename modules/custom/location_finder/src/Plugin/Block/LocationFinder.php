<?php

namespace Drupal\location_finder\Plugin\Block;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a block with location finder.
 *
 * @Block(
 *   id = "location_finder",
 *   admin_label = @Translation("Location finder"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class LocationFinder extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new Block instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition,
                              ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->container = $container;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Location view
    $locationsView = 'locations';
    // Default view displays from OpenY profile content types
    $displayIds = [
      'locations_branches_block',
      'locations_camps_block',
      'locations_facilities_block',
    ];

    // For new displays
    $activeTypes = array_keys($this->configFactory->get('openy_map.settings')->get('active_types'));
    // TODO Dependency Injection
    $view = \Drupal\views\Entity\View::load($locationsView);
    $displays = $view->get('display');
    foreach ($displays as $id => $item) {
      if ($item['display_plugin'] == 'block' && !in_array($id, $displayIds)) {
        foreach ($activeTypes as $type) {
          if (strpos($id, $type) !== FALSE) {
            $displayIds[] = $id;
          }
        }
      }
    }

    $render = [];
    foreach ($displayIds as $display) {
      $render[] = [
        '#type' => 'view',
        '#name' => $locationsView,
        '#display_id' => $display,
      ];
    }
    return $render;
  }

}
