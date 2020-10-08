<?php

namespace Drupal\openy_media;

use Drupal\embed\Entity\EmbedButton;
use Drupal\file\Entity\File;

/**
 * Class that helps in uploading icons for embed buttons.
 */
class EmbedButtonIconHelper {

  /**
   * Helper that upload icon for embed button and add it to configuration.
   *
   * @param string $module_name
   *   Media bundle module name.
   * @param string $file_name
   *   File name within /images folder.
   * @param string $embed_button_name
   *   Embed button name.
   */
  public static function setEmbedButtonIcon($module_name, $file_name, $embed_button_name) {
    $icon = \Drupal::moduleHandler()->getModule($module_name)->getPath() . '/images/' . $file_name;
    $fs = \Drupal::service('file_system');
    $destination = \Drupal::service('file_system')->copy($icon, 'public://' . $fs->basename($icon));

    if ($destination) {
      $file = File::create(['uri' => $destination]);
      $file->setPermanent();
      $file->save();

      $button = EmbedButton::load($embed_button_name);

      $button
        ->set('icon_uuid', $file->uuid())
        ->save();
      $button->set('icon', EmbedButton::convertImageToEncodedData($file->getFileUri()));
      $button->save();

    }
  }

}
