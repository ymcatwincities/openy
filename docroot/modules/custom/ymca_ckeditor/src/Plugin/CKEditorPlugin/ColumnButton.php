<?php

/**
 * @file
 * Contains \Drupal\ymca_ckeditor\Plugin\CKEditorPlugin\ColumnButton.
 */

namespace Drupal\ymca_ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "ColumnButton" plugin.
 *
 * @CKEditorPlugin(
 *   id = "columnbutton",
 *   label = @Translation("ColumnButton")
 * )
 */
class ColumnButton extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

  /**
   * @inheritDoc
   */
  public function getButtons() {
    return array(
      'ColumnButton' => array(
        'label' => t('Columns'),
        'image' => drupal_get_path('module', 'ymca_ckeditor') . '/js/plugins/columnbutton/columnbutton.png',
      ),
    );
  }

  /**
   * @inheritDoc
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function getDependencies(Editor $editor) {
    return array();
  }

  /**
   * @inheritDoc
   */
  public function getLibraries(Editor $editor) {
    return array();
  }

  /**
   * @inheritDoc
   */
  public function getFile() {
    return drupal_get_path('module', 'ymca_ckeditor') . '/js/plugins/columnbutton/plugin.js';
  }

  /**
   * @inheritDoc
   */
  public function getConfig(Editor $editor) {
    return array();
  }
}
