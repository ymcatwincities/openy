<?php

namespace Drupal\ymca_ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "ColumnButton2x" plugin.
 *
 * @CKEditorPlugin(
 *   id = "columnbutton2x",
 *   label = @Translation("ColumnButton2x")
 * )
 */
class ColumnButton2x extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'ColumnButton2x' => array(
        'label' => t('Columns 2x'),
        'image' => drupal_get_path('module', 'ymca_ckeditor') . '/js/plugins/columnbutton/columnbutton2x.png',
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
    return drupal_get_path('module', 'ymca_ckeditor') . '/js/plugins/columnbutton2x/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

}
