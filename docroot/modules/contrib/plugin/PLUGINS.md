Plugin offers the following plugin types for which plugins can be provided by 
other modules:

# Plugin selector
Plugin selectors allow users to select and configure a plugins. They are
classes that implement
`\Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface` 
and live in
`\Drupal\$module\Plugin\PluginSelector\PluginSelector`, where `$module` is the 
machine name of the module that provides the plugins. The classes are annotated 
using `\Drupal\plugin\Annotations\PluginSelector`.

If a plugin provides configuration, it must also provide a configuration schema
for this configuration of which the type is
`plugin.plugin_configuration.plugin_selector.[plugin_id]`, where `[plugin_id]` 
is the plugin's ID.

See the drupal.org handbook for more information about configuration schemas.
