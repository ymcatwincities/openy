<?php

namespace Drupal\panelbutton\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "panelbutton" plugin.
 *
 * @CKEditorPlugin(
 *   id = "panelbutton",
 *   label = @Translation("Panel Button"),
 * )
 */
class PanelButton extends CKEditorPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/panelbutton/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }
}
