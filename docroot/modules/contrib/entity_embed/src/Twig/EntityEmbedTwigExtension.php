<?php

/**
 * @file
 * Contains \Drupal\entity_embed\Twig\EntityEmbedTwigExtension.
 */

namespace Drupal\entity_embed\Twig;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\entity_embed\EntityHelperTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager;

/**
 * Provide entity embedding function within Twig templates.
 */
class EntityEmbedTwigExtension extends \Twig_Extension {
  use EntityHelperTrait;

  /**
   * Constructs a new EntityEmbedTwigExtension.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager $plugin_manager
   *   The plugin manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, EntityEmbedDisplayManager $plugin_manager) {
    $this->setEntityManager($entity_manager);
    $this->setModuleHandler($module_handler);
    $this->setDisplayPluginManager($plugin_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'entity_embed.twig.entity_embed_twig_extension';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction('entity_embed', array($this, 'getRenderArray')),
    );
  }

  /**
   * Return the render array for an entity.
   *
   * @param string $entity_type
   *   The machine name of an entity_type like 'node'.
   * @param string $entity_id
   *   The entity ID or entity UUID.
   * @param string $display_plugin
   *   (optional) The Entity Embed Display plugin to be used to render the
   *   entity.
   * @param array $display_settings
   *   (optional) A list of settings for the Entity Embed Display plugin.
   *
   * @return array
   *   A render array from entity_view().
   */
  public function getRenderArray($entity_type, $entity_id, $display_plugin = 'default', array $display_settings = []) {
    $entity = $this->loadEntity($entity_type, $entity_id);
    $context = array(
      'data-entity-type' => $entity_type,
      'data-entity-id' => $entity_id,
      'data-entity-embed-display' => $display_plugin,
      'data-entity-embed-settings' => $display_settings,
    );
    return $this->renderEntityEmbed($entity, $context);
  }

}
