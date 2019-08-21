<?php

namespace Drupal\openy_home_branch\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an home branch library annotation object.
 *
 * Plugin Namespace: Plugin\HomeBranchLibrary.
 *
 * For a working example, see
 * \Drupal\openy_home_branch\Plugin\HomeBranchLibrary\HbMenuSelector
 *
 * @see \Drupal\openy_home_branch\HomeBranchLibraryManager
 * @see \Drupal\openy_home_branch\HomeBranchLibraryInterface
 * @see plugin_api
 * @see hook_home_branch_library_info_alter()
 *
 * @Annotation
 */
class HomeBranchLibrary extends Plugin {

  /**
   * The home_branch_library plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the home_branch_library plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The home_branch_library plugin entity.
   *
   * For this entity will be attached library that referenced in plugin.
   *
   * @var string
   */
  public $entity;

}
