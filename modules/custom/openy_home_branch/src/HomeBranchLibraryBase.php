<?php

namespace Drupal\openy_home_branch;

use Drupal\Component\Plugin\PluginBase;

/**
 * Defines the base plugin for HomeBranchLibrary classes.
 *
 * @see \Drupal\openy_home_branch\HomeBranchLibraryManager
 * @see \Drupal\openy_home_branch\HomeBranchLibraryInterface
 * @see \Drupal\openy_home_branch\Annotation\HomeBranchLibrary
 * @see plugin_api
 */
class HomeBranchLibraryBase extends PluginBase implements HomeBranchLibraryInterface {

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityName() {
    return $this->pluginDefinition['entity'];
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowedForAttaching($variables) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrarySettings() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return FALSE;
  }

}
