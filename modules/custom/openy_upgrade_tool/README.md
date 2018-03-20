# OpenY upgrade tool
This module add the ability to upgrade OpenY configs and log manual config changes.

### OpenY upgrade dashboard
- /admin/config/development/openy-upgrade-dashboard - On this page you can see list of manually changed configs that has conflicts with updates from openy and diff between current config version and config from update.
- /admin/config/logger_entity - On this page you can see list of all manually changed configs.

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

### Update conflicts resolving
In case if you have conflict in config update, there are 3 ways of solving this problem:

- **Force update** (this action will delete all manual changes).
If you want to override existing config you need to delete logger entity and run update again. Example:
```
// Delete logger_entity.
$logger_entity_storage = \Drupal::service('entity_type.manager')->getStorage('logger_entity');
$entities = $logger_entity_storage->loadByProperties([
  'type' => 'openy_config_upgrade_logs',
  'name' => 'core.entity_form_display.node.landing_page.default',
]);
$logger_entity = array_shift($entities);
$logger_entity->delete();

// Run update.
$config_dir = drupal_get_path('module', 'openy_node_landing') . '/config/install';
$config_importer = \Drupal::service('openy_upgrade_tool.importer');
$config_importer->setDirectory($config_dir);
$config_importer->importConfigs([
  'core.entity_form_display.node.landing_page.default',
]);
```

- **Skip update and use existing version of config.**
Also you can skip update, in this case you need remove field_config_path value from logger_entity, this action will delete config from dashboard. Example:
```
$logger_entity_storage = \Drupal::service('entity_type.manager')->getStorage('logger_entity');
$entities = $logger_entity_storage->loadByProperties([
  'type' => 'openy_config_upgrade_logs',
  'name' => 'core.entity_form_display.node.landing_page.default',
]);
$logger_entity = array_shift($entities);
$logger_entity->set('field_config_path', '');
$logger_entity->save();
```

- **Resolve conflict and import fixed config version.**
In this case you need export existing config (for example to sites/default/config/staging) and add changes from openy to this file (you can use diff on dashboard to get new updates).
After this **import config using services from confi module** and remove field_config_path value from logger_entity.
```
$config_importer = \Drupal::service('config_import.importer');
$config_importer->setDirectory(''sites/default/config/staging'');
$config_importer->importConfigs([
  // Fixed config version with manual changes and all updates from openy.
  'core.entity_form_display.node.landing_page.default',
]);
  
$logger_entity_storage = \Drupal::service('entity_type.manager')->getStorage('logger_entity');
$entities = $logger_entity_storage->loadByProperties([
  'type' => 'openy_config_upgrade_logs',
  'name' => 'core.entity_form_display.node.landing_page.default',
]);
$logger_entity = array_shift($entities);
$logger_entity->set('field_config_path', '');
$logger_entity->save();
```


- **Helpful code for devel.**
Delete all logger entities.
```php
$logger_entity_storage = \Drupal::service('entity_type.manager')->getStorage('logger_entity');
$entities = $logger_entity_storage->loadMultiple();
foreach ($entities as $entity){
  $entity->delete();
}
```
