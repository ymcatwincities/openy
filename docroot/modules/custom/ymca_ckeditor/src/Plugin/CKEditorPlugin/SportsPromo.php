<?php

namespace Drupal\ymca_ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "SportsPromo" plugin.
 *
 * @CKEditorPlugin(
 *   id = "sports_promo",
 *   label = @Translation("Sports Promo")
 * )
 */
class SportsPromo extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'SportsPromo' => array(
        'label' => t('Sports Promo'),
        'image' => drupal_get_path('module', 'ymca_ckeditor') . '/js/plugins/sports_promo/sports_promo.png',
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
    return drupal_get_path('module', 'ymca_ckeditor') . '/js/plugins/sports_promo/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

}
