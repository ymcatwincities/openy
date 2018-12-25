<?php

namespace Drupal\panelizer\Plugin\PanelizerEntity;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\panelizer\Plugin\PanelizerEntityBase;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * Panelizer entity plugin for integrating with nodes.
 *
 * @PanelizerEntity("node")
 */
class PanelizerNode extends PanelizerEntityBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultDisplay(EntityViewDisplayInterface $display, $bundle, $view_mode) {
    $panels_display = parent::getDefaultDisplay($display, $bundle, $view_mode)
      ->setPageTitle('[node:title]');

    // Remove the 'title' block because it's covered already.
    foreach ($panels_display->getRegionAssignments() as $region => $blocks) {
      /** @var \Drupal\Core\Block\BlockPluginInterface[] $blocks */
      foreach ($blocks as $block_id => $block) {
        if ($block->getPluginId() == 'entity_field:node:title') {
          $panels_display->removeBlock($block_id);
        }
      }
    }

    if ($display->getComponent('links')) {
      // @todo: add block for node links.
    }

    if ($display->getComponent('langcode')) {
      // @todo: add block for node language.
    }

    return $panels_display;
  }

  /**
   * {@inheritdoc}
   */
  public function alterBuild(array &$build, EntityInterface $entity, PanelsDisplayVariant $panels_display, $view_mode) {
    /** @var $entity \Drupal\node\Entity\Node */
    parent::alterBuild($build, $entity, $panels_display, $view_mode);

    if ($entity->id()) {
      $build['#contextual_links']['node'] = [
        'route_parameters' => ['node' => $entity->id()],
        'metadata' => ['changed' => $entity->getChangedTime()],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessViewMode(array &$variables, EntityInterface $entity, PanelsDisplayVariant $panels_display, $view_mode) {
    parent::preprocessViewMode($variables, $entity, $panels_display, $view_mode);

    /** @var \Drupal\node\NodeInterface $node */
    $node = $entity;

    // Add node specific CSS classes.
    if ($node->isPromoted()) {
      $variables['attributes']['class'][] = 'node--promoted';
    }
    if ($node->isSticky()) {
      $variables['attributes']['class'][] = 'node--sticky';
    }
    if (!$node->isPublished()) {
      $variables['attributes']['class'][] = 'node--unpublished';
    }
  }

}