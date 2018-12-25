<?php

namespace Drupal\panelizer\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\panelizer\PanelizerInterface;

/**
 *
 */
class DefaultAccess implements AccessInterface {

  /**
   * The Panelizer service.
   *
   * @var \Drupal\panelizer\PanelizerInterface
   */
  protected $panelizer;

  /**
   * DefaultAccess constructor.
   *
   * @param \Drupal\panelizer\PanelizerInterface $panelizer
   *   The Panelizer service.
   */
  public function __construct(PanelizerInterface $panelizer) {
    $this->panelizer = $panelizer;
  }

  /**
   * Determines access to a default Panelizer layout.
   *
   * @param string $entity_type_id
   *   The panelized entity type ID.
   * @param string $bundle
   *   The panelized bundle ID.
   * @param string $view_mode_name
   *   The panelized view mode ID.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access($entity_type_id, $bundle, $view_mode_name) {
    $settings = $this->panelizer->getPanelizerSettings($entity_type_id, $bundle, $view_mode_name);
    return $settings['enable'] ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
