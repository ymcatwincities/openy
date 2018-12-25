<?php

namespace Drupal\file_entity\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FormatterInterface;
use Drupal\image\Entity\ImageStyle;

/**
 * View builder for File Entity.
 */
class FileEntityViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);

    // We suppress file entity cache tags, because they are almost exclusively
    // embedded in other pages, except when viewed as a standalone page. To
    // support cache invalidations on those, we pick the first cache tag from
    // the references and add that.
    // @todo Make this available as a method?
    foreach (\Drupal::service('file.usage')->listUsage($entity) as $module => $module_references) {
      foreach ($module_references as $type => $ids) {
        if (\Drupal::entityManager()->hasDefinition($type)) {
          $build['#cache']['tags'] = Cache::mergeTags($build['#cache']['tags'], array($type . ':' . key($ids)));
          break 2;
        }
      }
    }
    return $build;
  }
}
