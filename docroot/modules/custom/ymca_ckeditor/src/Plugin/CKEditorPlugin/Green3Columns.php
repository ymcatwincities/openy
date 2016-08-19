<?php

namespace Drupal\ymca_ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Green3Columns" plugin.
 *
 * @CKEditorPlugin(
 *   id = "green_3_col",
 *   label = @Translation("Green 3x columns")
 * )
 */
class Green3Columns extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'Green3Columns' => array(
        'label' => t('Green 3x columns'),
        'image' => drupal_get_path('module', 'ymca_ckeditor') . '/js/plugins/green_3_col/green_3_col.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ymca_ckeditor') . '/js/plugins/green_3_col/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

}
