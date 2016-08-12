<?php

namespace Drupal\ymca_ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "SportsTopSubheader" plugin.
 *
 * @CKEditorPlugin(
 *   id = "sports_top_subheader",
 *   label = @Translation("Sports Top Subheader")
 * )
 */
class SportsTopSubheader extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'SportsTopSubheader' => array(
        'label' => t('Sports Top Subheader'),
        'image' => drupal_get_path('module', 'ymca_ckeditor') . '/js/plugins/sports_top_subheader/sports_top_subheader.png',
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
    return drupal_get_path('module', 'ymca_ckeditor') . '/js/plugins/sports_top_subheader/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

}
