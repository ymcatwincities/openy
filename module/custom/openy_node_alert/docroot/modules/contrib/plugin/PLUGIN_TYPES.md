Modules can expose their plugin types through `$module.plugin_type.yml` files in 
their root folders, where `$module` is the name of the module itself.
The files' contents are objects. Top-level keys are plugin type IDs (strings), 
and the values are objects. The only required object property is `class`, which
must be the fully qualified name of a class that implements 
`\Drupal\plugin\PluginType\PluginTypeInterface`, or be left empty so it 
defaults to `\Drupal\plugin\PluginType\PluginType`. All other properties depend 
on the class, but the default class takes the following:

- label (required): the human-readable US English plugin type label.
- description (optional): the human-readable US English plugin description.
- plugin_manager_service_id (required): the ID of the plugin type's plugin 
  manager in the service container.
- plugin_definition_decorator_class (optional): the fully qualified name of a class 
  that implements 
  `\Drupal\plugin\PluginDefinition\PluginDefinitionDecoratorInterface` if the 
  original plugin definitions do not implement 
  `\Drupal\plugin\PluginDefinition\PluginDefinitionInterface`.
- operations_provider_class (optional): the fully qualified name of a class that
  implements `\Drupal\plugin\PluginType\PluginTypeOperationsProviderInterface`. 
  Defaults to `\Drupal\plugin\PluginType\DefaultPluginTypeOperationsProvider`.
- plugin_configuration_schema_id (optional): the ID of the plugin's 
  configuration schema. Two following replacement tokens are supported:
  - [plugin_type_id]: The ID of the plugin's type.
  - [plugin_id]: the plugin's ID.
  Defaults to "plugin.plugin_configuration.[plugin_type_id].[plugin_id]"

A configuration schema MAY be provided for all configurable plugin types. If a 
schema is provided, its name MAY be like
`plugin.plugin_configuration.$plugin_type_id.$plugin_id`, where 
`$plugin_type_id` is the plugin type ID as specified in
`$module.plugin_type.yml` and `$plugin_id` is the ID of a specific plugin, or an 
asterisk (`*`)  to apply to all plugins of that type. This is recommended over 
specifying a custom ID in the plugin type definition.
