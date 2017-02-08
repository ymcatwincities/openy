<?php

namespace Drupal\location_finder\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\openy_socrates\OpenySocratesFacade;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Openy Socrates Facade.
   *
   * @var OpenySocratesFacade
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
      [
        '#type' => 'view',
        '#name' => 'locations',
        '#display_id' => 'locations_branches_block',
      ],
      [
        '#type' => 'view',
        '#name' => 'locations',
        '#display_id' => 'locations_camps_block',
      ],
    ];
  }

}
