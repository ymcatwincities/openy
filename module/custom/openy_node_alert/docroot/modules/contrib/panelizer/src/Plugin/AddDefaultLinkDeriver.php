<?php

namespace Drupal\panelizer\Plugin;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class AddDefaultLinkDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The Panelizer entity manager.
   *
   * @var \Drupal\panelizer\Plugin\PanelizerEntityManagerInterface
   */
  protected $panelizerEntityManager;

  /**
   * AddDefaultLinkDeriver constructor.
   *
   * @param \Drupal\panelizer\Plugin\PanelizerEntityManagerInterface $panelizer_entity_manager
   *   The panelizer entity manager.
   */
  public function __construct(PanelizerEntityManagerInterface $panelizer_entity_manager) {
    $this->panelizerEntityManager = $panelizer_entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.panelizer_entity')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->panelizerEntityManager->getDefinitions() as $plugin_id => $definition) {
      $this->derivatives["$plugin_id"] = $base_plugin_definition;
      $this->derivatives["$plugin_id"]['appears_on'] = [
        "entity.entity_view_display.$plugin_id.default",
        "entity.entity_view_display.$plugin_id.view_mode"
      ];
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
