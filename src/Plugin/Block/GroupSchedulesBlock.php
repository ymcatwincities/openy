<?php

namespace Drupal\openy_group_schedules\Plugin\Block;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Group Schedules' block.
 *
 * @Block(
 *   id = "group_schedules",
 *   admin_label = @Translation("Group Schedules Block"),
 *   category = @Translation("Forms")
 * )
 */
class GroupSchedulesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new Programs Search Block instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\openy_group_schedules\Form\GroupexFormFull');
    return [
      'form' => $form,
    ];
  }

}
