<?php

namespace Drupal\panelizer\Plugin\PanelizerEntity;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\panelizer\Plugin\PanelizerEntityBase;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\Core\Render\Element;

/**
 * Panelizer entity plugin for integrating with users.
 *
 * @PanelizerEntity("user")
 */
class PanelizerUser extends PanelizerEntityBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultDisplay(EntityViewDisplayInterface $display, $bundle, $view_mode) {
    $panels_display = parent::getDefaultDisplay($display, $bundle, $view_mode)
      ->setPageTitle('[user:name]');

    // Remove the 'name' block because it's covered already.
    foreach ($panels_display->getRegionAssignments() as $region => $blocks) {
      /** @var \Drupal\Core\Block\BlockPluginInterface[] $blocks */
      foreach ($blocks as $block_id => $block) {
        if ($block->getPluginId() == 'entity_field:user:name') {
          $panels_display->removeBlock($block_id);
        }
      }
    }

    if ($display->getComponent('member_for')) {
      // @todo: add block for 'Member for'.
    }

    return $panels_display;
  }

  /**
   * {@inheritdoc}
   */
  public function alterBuild(array &$build, EntityInterface $entity, PanelsDisplayVariant $panels_display, $view_mode) {
    /** @var $entity \Drupal\user\Entity\User */
    parent::alterBuild($build, $entity, $panels_display, $view_mode);

    if ($entity->id()) {
      $build['#contextual_links']['user'] = [
        'route_parameters' => ['user' => $entity->id()],
        'metadata' => ['changed' => $entity->getChangedTime()],
      ];
    }

    // This function adds a default alt tag to the user_picture field to
    // maintain accessibility.
    if (user_picture_enabled() && !empty($build['content']['middle'])) {
      foreach (Element::children($build['content']['middle']) as $key) {
        if (isset($build['content']['middle'][$key]['content']['field'])) {
          foreach (Element::children($build['content']['middle'][$key]['content']['field']) as $field_key) {
            if ($build['content']['middle'][$key]['content']['field']['#field_name'] == 'user_picture') {
              if (empty($build['content']['middle'][$key]['content']['field'][$field_key]['#item_attributes'])) {
                $build['content']['middle'][$key]['content']['field'][$field_key]['#item_attributes'] = [
                  'alt' => \Drupal::translation()
                    ->translate('Profile picture for user @username', ['@username' => $entity->getUsername()])
                ];
              }
            }
          }
        }
      }
    }
  }

}
