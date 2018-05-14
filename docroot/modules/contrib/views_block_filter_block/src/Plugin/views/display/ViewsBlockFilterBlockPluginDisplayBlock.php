<?php

namespace Drupal\views_block_filter_block\Plugin\views\display;

use Drupal\views\Plugin\views\display\Block;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * The plugin that handles a block.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "views_block_filter_block_plugin_display_block",
 *   title = @Translation("Block Display Filter Block"),
 *   help = @Translation("Display the exposed filters as a block for block views."),
 *   theme = "views_view",
 *   register_theme = FALSE,
 *   uses_hook_block = TRUE,
 *   contextual_links_locations = {"views_block_filter_block"},
 *   admin = @Translation("Block Display Filter Block")
 * )
 *
 * @see \Drupal\views\Plugin\block\ViewsBlock
 * @see \Drupal\views\Plugin\Derivative\ViewsBlock
 */
class ViewsBlockFilterBlockPluginDisplayBlock extends Block {

  /**
   * Allows block views to put exposed filter forms in blocks.
   */
  public function usesExposedFormInBlock() {
    return TRUE;
  }

  /**
   * Block views use exposed widgets only if AJAX is set.
   */
  public function usesExposed() {
    return DisplayPluginBase::usesExposed();
  }

}
