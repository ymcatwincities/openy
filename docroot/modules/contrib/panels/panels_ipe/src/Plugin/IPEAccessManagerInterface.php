<?php
/**
 * @file
 * Contains \Drupal\panels_ipe\Plugin\IPEAccessManagerInterface.php
 */
namespace Drupal\panels_ipe\Plugin;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;


/**
 * Provides the IPE Access plugin manager.
 */
interface IPEAccessManagerInterface {
  /**
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $display
   *
   * @return \Drupal\panels_ipe\Plugin\IPEAccessInterface[]
   */
  public function applies(PanelsDisplayVariant $display);

  /**
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $display
   *
   * @return bool
   */
  public function access(PanelsDisplayVariant $display);
}
