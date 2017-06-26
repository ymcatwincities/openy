<?php

/**
 * @file
 * Contains \Drupal\entity_embed\Plugin\Derivative\FieldFormatterDeriver.
 */

namespace Drupal\entity_embed\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FormatterPluginManager;

/**
 * Provides Entity Embed Display plugin definitions for field formatters.
 *
 * @see \Drupal\entity_embed\FieldFormatterEntityEmbedDisplayBase
 */
class FieldFormatterDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The manager for formatter plugins.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager.
   */
  protected $formatterManager;

  /**
   * Constructs new FieldFormatterEntityEmbedDisplayBase.
   *
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_manager
   *   The field formatter plugin manager.
   */
  public function __construct(FormatterPluginManager $formatter_manager) {
    $this->formatterManager = $formatter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.field.formatter')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \LogicException
   *   Throws an exception if field type is not defined in the annotation of the
   *   Entity Embed Display plugin.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // The field type must be defined in the annotation of the Entity Embed
    // Display plugin.
    if (!isset($base_plugin_definition['field_type'])) {
      throw new \LogicException("Undefined field_type definition in plugin {$base_plugin_definition['id']}.");
    }
    foreach ($this->formatterManager->getOptions($base_plugin_definition['field_type']) as $formatter => $label) {
      $this->derivatives[$formatter] = $base_plugin_definition;
      $this->derivatives[$formatter]['label'] = $label;
    }
    return $this->derivatives;
  }

}
