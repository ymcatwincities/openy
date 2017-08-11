<?php

namespace Drupal\openy_ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "ListBranchAmenities" plugin.
 *
 * @CKEditorPlugin(
 *   id = "list_branch_amenities",
 *   label = @Translation("List Branch Amenities")
 * )
 */
class ListBranchAmenities extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'ListBranchAmenities' => [
        'label' => t('List of Branch Amenities'),
        'image' => drupal_get_path('module', 'openy_ckeditor') . '/js/plugins/list_branch_amenities/icon.png',
      ],
    ];
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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'openy_ckeditor') . '/js/plugins/list_branch_amenities/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
