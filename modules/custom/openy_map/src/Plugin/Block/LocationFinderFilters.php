<?php

namespace Drupal\openy_map\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\openy_socrates\OpenySocratesFacade;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with location finder.
 *
 * @Block(
 *   id = "location_finder_filters",
 *   admin_label = @Translation("Location finder filters"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class LocationFinderFilters extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Openy Socrates Facade.
   *
   * @var \Drupal\openy_socrates\OpenySocratesFacade
   */
  protected $socrates;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OpenySocratesFacade $socrates) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->socrates = $socrates;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('socrates')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      [
        '#type' => 'openy_map',
        '#show_controls' => TRUE,
        '#element_variables' => $this->socrates->getLocationPins(),
      ],
    ];
  }

}
