# OpenY upgrade tool
This module add the ability to upgrade OpenY configs and log manual config changes.


### Revert only specific property from config

With [config_import module](https://www.drupal.org/project/confi) help you can update only part from full config.

For detecting manual config update used 'openy_upgrade_tool.param_updater' service that extends ConfigParamUpdaterService from config_import module. 

For updating specific property in config:

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

