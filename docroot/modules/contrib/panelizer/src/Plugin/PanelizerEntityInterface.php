<?php

namespace Drupal\panelizer\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * Defines an interface for Panelizer entity plugins.
 */
interface PanelizerEntityInterface extends PluginInspectionInterface {

  // @todo: Do we need get the form alterations in the right place or is this standardized in D8?

  // @todo: Do we need something to tell page_manager where the entity view page is?

  // @todo: In D7 there's lots more stuff on the plugin, like permissions, admin routes, etc..

  /**
   * Creates a default Panels display from the core Entity display.
   *
   * As much as possible, this should attempt to make the settings on the
   * Panels display match the existing core settings, so that ideally the user
   * doesn't notice any change upon Panelizing an entity's view mode.
   *
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display holding the display options configured for the
   *   entity components.
   * @param string $bundle
   *   The bundle to panelize.
   * @param string $view_mode
   *   The view mode to panelize.
   *
   * @return \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant
   */
  public function getDefaultDisplay(EntityViewDisplayInterface $display, $bundle, $view_mode);

  /**
   * Alter the built entity view in an entity specific way before rendering.
   *
   * This is useful for adding things like contextual links.
   *
   * @param array $build
   *   The render array that is being created.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be prepared.
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   *   The Panels display used to render this entity.
   * @param string $view_mode
   *   The view mode that should be used to prepare the entity.
   */
  public function alterBuild(array &$build, EntityInterface $entity, PanelsDisplayVariant $panels_display, $view_mode);

  /**
   * Preprocess the variables for the view mode template.
   *
   * @param array $variables
   *   The variables to pass to the view mode template.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   *   The Panels display used to render this entity.
   * @param string $view_mode
   *   The view mode.
   */
  public function preprocessViewMode(array &$variables, EntityInterface $entity, PanelsDisplayVariant $panels_display, $view_mode);

}
