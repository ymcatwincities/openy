<?php

namespace Drupal\glyphicons\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "glyphicons" plugin.
 *
 * @CKEditorPlugin(
 *   id = "glyphicons",
 *   label = @Translation("Bootstrap Glyphicons"),
 * )
 */
class Glyphicons extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return ['colordialog'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/glyphicons/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'allowedContent' => TRUE,
      'contentsCss' => '/libraries/glyphicons/css/style.css',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'Glyphicons' => array(
        'label' => $this->t('Glyphicons'),
        'image' => 'libraries/glyphicons/icons/glyphicons.png',
      ),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'glyphicons/ckeditor',
    ];
  }

}
