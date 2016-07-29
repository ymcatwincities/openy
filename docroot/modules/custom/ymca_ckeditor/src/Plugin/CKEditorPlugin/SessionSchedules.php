<?php

namespace Drupal\ymca_ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "SessionSchedules" plugin.
 *
 * @CKEditorPlugin(
 *   id = "session_schedules",
 *   label = @Translation("Session Schedules")
 * )
 */
class SessionSchedules extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'SessionSchedules' => array(
        'label' => t('Session Schedules'),
        'image' => drupal_get_path('module', 'ymca_ckeditor') . '/js/plugins/session_schedules/session_schedules.png',
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
    return drupal_get_path('module', 'ymca_ckeditor') . '/js/plugins/session_schedules/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

}
