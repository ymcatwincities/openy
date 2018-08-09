<?php

namespace Drupal\openy_map\Plugin\Block;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\views\Views;
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
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
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
    // Location view.
    $locationsView = 'locations';
    $locationDisplay = 'locations_block';

    // Render Locations block display with changed arguments.
    $activeTypes = \Drupal::configFactory()->get('openy_map.settings')->get('active_types');
    $activeTypes = !empty($activeTypes) ? array_keys(array_filter($activeTypes)) : [];
    $blockLabels = $this->configFactory->get('openy_map.settings')->get('block_labels');
    $render = [];
    foreach ($activeTypes as $type) {
      $view = Views::getView($locationsView);
      $view->setDisplay($locationDisplay);
      $view->setArguments([$type]);
      $options = [
        'id' => 'area_text_custom',
        'table' => 'views',
        'field' => 'area_text_custom',
        'relationship' => 'none',
        'group_type' => 'none',
        'admin_label' => '',
        'empty' => FALSE,
        'tokenize' => FALSE,
        'content' => '<h2 class="location-title h1 color-purple" style="">' . $blockLabels[$type] . '</h2>',
        'plugin_id' => 'text_custom',
      ];
      $view->setHandler($locationDisplay, 'header', 'area_text_custom', $options);
      $view->preExecute();
      $view->execute();
      $render[] = $view->buildRenderable($locationDisplay, [$type]);
    }
    return $render;
  }

}
