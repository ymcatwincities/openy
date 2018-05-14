<?php

namespace Drupal\panels_ipe;

use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\Element;

/**
 * Provides methods to render Blocks for display in Panels IPE.
 */
trait PanelsIPEBlockRendererTrait {

  /**
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface $contextHandler
   */
  protected $contextHandler;

  /**
   * Compiles a render array for the given Block instance based on the form.
   *
   * @param \Drupal\Core\Block\BlockBase $block_instance
   *   The Block instance you want to render.
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   *   The Panels Display that contains the Block instance.
   *
   * @return array $build
   *   The Block render array.
   */
  protected function buildBlockInstance($block_instance, $panels_display) {
    // Get the new block configuration.
    $configuration = $block_instance->getConfiguration();

    // Add context to the block.
    if ($this->contextHandler && $block_instance instanceof ContextAwarePluginInterface) {
      $this->contextHandler->applyContextMapping($block_instance, $panels_display->getContexts());
    }

    // Build the block content.
    $content = $block_instance->build();

    // Compile the render array.
    $build = [
      '#theme' => 'block',
      '#attributes' => [],
      '#contextual_links' => [],
      '#configuration' => $configuration,
      '#plugin_id' => $block_instance->getPluginId(),
      '#base_plugin_id' => $block_instance->getBaseId(),
      '#derivative_plugin_id' => $block_instance->getDerivativeId(),
      'content' => $content,
    ];

    return $build;
  }

  /**
   * Bubble block attributes up if possible. This allows modules like
   * Quickedit to function.
   *
   * @see \Drupal\block\BlockViewBuilder::preRender for reference.
   *
   * @param array $build
   *   The Block render array.
   */
  protected function bubbleBlockAttributes(&$build) {
    // Bubble block attributes up if possible. This allows modules like
    // Quickedit to function.
    // See \Drupal\block\BlockViewBuilder::preRender() for reference.
    if ($build['content'] !== NULL && !Element::isEmpty($build['content'])) {
      foreach (['#attributes', '#contextual_links'] as $property) {
        if (isset($build['content'][$property])) {
          $build[$property] += $build['content'][$property];
          unset($build['content'][$property]);
        }
      }
    }
  }

}
