<?php

/**
 * @file
 * Contains \Drupal\video\Plugin\Field\FieldFormatter\VideoPlayerListFormatter.
 */

namespace Drupal\video\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\video\Plugin\Field\FieldFormatter\VideoPlayerFormatter;

/**
 * Plugin implementation of the 'video_player_list' formatter.
 *
 * @FieldFormatter(
 *   id = "video_player_list",
 *   label = @Translation("HTML5 Video Player"),
 *   field_types = {
 *     "video"
 *   }
 * )
 */

class VideoPlayerListFormatter extends VideoPlayerFormatter implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    // Collect cache tags to be added for each item in the field.
    $video_items = array();
    foreach ($files as $delta => $file) {
      $video_uri = $file->getFileUri();
      $video_items[] = Url::fromUri(file_create_url($video_uri));
    }
    $elements[] = array(
      '#theme' => 'video_player_formatter',
      '#items' => $video_items,
      '#player_attributes' => $this->getSettings(),
    );
    return $elements;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if(empty($field_definition->getTargetBundle()) && $field_definition->isList()){
      return TRUE;
    }
    else{
      $entity_form_display = entity_get_form_display($field_definition->getTargetEntityTypeId(), $field_definition->getTargetBundle(), 'default');
      $widget = $entity_form_display->getRenderer($field_definition->getName());
      $widget_id = $widget->getBaseId();
      if($field_definition->isList() && $widget_id == 'video_upload'){
        return TRUE;
      }
    }
    return FALSE;
  }
}