<?php
/**
 * @file
 * Drush integration.
 */

use Drupal\Component\Serialization\Yaml;

/**
 * Implements hook_drush_command().
 */
function layout_plugin_drush_command() {
  $items = array();

  $items['layout-plugin-list'] = array(
    'description' => 'List layouts.',
    'arguments' => array(),
    'options' => array(),
    'examples' => array(
      'drush layouts-list' => 'List layouts.',
    ),
  );

  $items['layout-plugin-region-list'] = array(
    'description' => 'List layout regions.',
    'arguments' => array(),
    'options' => array(),
    'examples' => array(
      'drush layouts-list' => 'List layouts.',
    ),
  );

  $items['layout-plugin-region-normalize'] = array(
    'description' => 'Parse regions.',
    'arguments' => array(),
    'options' => array(),
    'examples' => array(
      'drush layouts-list' => 'Parse layouts.',
    ),
  );

  return $items;
}

/**
 * Create a basic template and configuration file for new Display Suite layout.
 */
function drush_layout_plugin_list($name = NULL) {
  $layoutsManager = \Drupal::service('plugin.manager.layout_plugin');
  /** @var $layoutsManager \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager */
  $plugins = $layoutsManager->getDefinitions();
  foreach ($plugins as $id => $pluginInfo) {
    $plugin = $layoutsManager->createInstance($id, array());
    /** @var $plugin \Drupal\layout_plugin\Plugin\Layout\LayoutInterface */
    drush_print(dt('Layout !id: !regions', array('!id' => $id, '!regions' => print_r($plugin->getRegionNames(), TRUE))));
  }

}


/**
 * Create a basic template and configuration file for a new Display Suite layout.
 */
function drush_layout_plugin_region_list($name = NULL) {
  $layoutsManager = \Drupal::service('plugin.manager.layout_plugin.region');
  /** @var $layoutsManager \Drupal\layout_plugin\Plugin\LayoutRegion\LayoutRegionPluginManager */
  $plugins = $layoutsManager->getDefinitions();
  foreach ($plugins as $id => $pluginInfo) {
    $plugin = $layoutsManager->createInstance($id, array());
    /** @var $plugin \Drupal\layout_plugin\Plugin\Layout\LayoutInterface */
    drush_print(dt('Layout region !id could be loaded.', array('!id' => $id)));
  }

}

function drush_layout_plugin_region_normalize($file = NULL) {
  $layouts = Yaml::decode(file_get_contents(drupal_get_path('module', 'layout_plugin_example'). '/layout_plugin_example.layouts.yml'));
  foreach ($layouts as $layout_plugin_id => $layout_plugin_info) {
    if ($layout_plugin_id === 'koleary') {
      $regions = $layout_plugin_info['regions'];
      var_dump($layout_plugin_id, $regions);
    }
  }

}
