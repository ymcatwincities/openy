# How to support upgrade path
All changes in configurations should be added to appropriate hook\_update\_N in order to update already existing environments. We suggest to use https://www.drupal.org/project/confi for working with hook\_update\_N.

### `openy.install` in profile
In this file we should put updates that are related to the distribution in general and don't fit into any feature.

- Enable/Disable module
- General configs

### `openy_*.install` in modules
In case if you update some configuration for specific feature, make sure that you put updates into appropriate module.

### Revert only specific property from config

With [config_import module](https://www.drupal.org/project/confi) help we can update only part from full config.

For updating specific property in config (use service 'openy_upgrade_tool.param_updater'):

1) go to related to this config module

2) create new hook_update_N in openy_*.install file

3) in update add next code (this is example):

```
$config = drupal_get_path('module', 'openy_media_image') . '/config/install/views.view.images_library.yml';
$config_importer = \Drupal::service('openy_upgrade_tool.param_updater');
$config_importer->update($config, 'views.view.images_library', 'display.default.display_options.pager');
```
Where:
- $config variable contains path to config with config name
- "views.view.images_library" - config name
- "display.default.display_options.pager" - config specific property (you can set value from a nested array with variable depth)

### Revert full configs
For updating full config or several configs from directory use service 'openy_upgrade_tool.importer'.
```
$config_dir = drupal_get_path('module', 'openy_media_image') . '/config/install';
$config_importer = \Drupal::service('openy_upgrade_tool.importer');
$config_importer->setDirectory($config_dir);
$config_importer->importConfigs(['views.view.images_library']);
```
Where:
- $config_dir - path to directory with config
- "views.view.images_library" - config name


Also you can update several configs from directory:
```
$config_importer->importConfigs([
  'views.view.images_library',
  'views.view.example_view',
]);
```
