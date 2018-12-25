<?php

namespace Drupal\views_data_export\Plugin\search_api\display;

use Drupal\search_api\Display\DisplayPluginBase;

/**
 * Represents a Views Data Export display.
 *
 * @SearchApiDisplay(
 *   id = "views_data",
 *   views_display_type = "data_export",
 *   deriver = "Drupal\search_api\Plugin\search_api\display\ViewsDisplayDeriver"
 * )
 */
class ViewsDataDisplay extends DisplayPluginBase {}
