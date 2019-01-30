# Open Y Upgrade tool
This module add the ability to upgrade Open Y configs and log manual config changes.

### Open Y Upgrade dashboard
/admin/openy/development/upgrade-log/dashboard - On this page you can see list of manually changed configs that has conflicts with updates from Open Y and diff between current config version and config from update.

### Revert only specific property from config

With [config_import module](https://www.drupal.org/project/confi) help you can update only part from full config.

For detecting manual config update used 'openy_upgrade_tool.param_updater' service that extends ConfigParamUpdaterService from config_import module. 

For updating specific property in config:

1) go to related to this config module

2) create new hook_update_N in openy_*.install file

3) in update add next code (this is example):

```php
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
```php
$config_dir = drupal_get_path('module', 'openy_media_image') . '/config/install';
$config_importer = \Drupal::service('openy_upgrade_tool.importer');
$config_importer->setDirectory($config_dir);
$config_importer->importConfigs(['views.view.images_library']);
```
Where:
- $config_dir - path to directory with config
- "views.view.images_library" - config name


Also you can update several configs from directory:
```php
$config_importer->importConfigs([
  'views.view.images_library',
  'views.view.example_view',
]);
```

Also you can use simplified version of importConfigs.
Main difference between this functions that in simple version we
skip export of all site configs to temp directory and just copy and import
only listed config. Also here was skipped configs filter logic.

Recommend to use it in case of small number of configurations that need to be updated.
```php
$config_importer->importConfigSimple('views.view.images_library');
```

### Update conflicts resolving
In case if you have conflict in config update, there are 3 ways of solving this problem:

- **Force update** (this action will delete all manual changes).

```php
// Load Open Y config version to active storage.
$openy_upgrade_log_item = \Drupal::service('openy_upgrade_log.manager')
  ->loadByName('core.entity_form_display.node.landing_page.default');
$openy_upgrade_log_item->applyOpenyVersion();
```

- **Skip update and use existing version of config.**

```php
$openy_upgrade_log_item = \Drupal::service('openy_upgrade_log.manager')
  ->loadByName('core.entity_form_display.node.landing_page.default');
$openy_upgrade_log_item->applyCurrentActiveVersion();
```

- **Resolve conflict and import fixed config version.**
In this case you need export existing config (for example to sites/default/config/staging)
and add changes from Open Y to this file (you can use diff on dashboard to get new updates, also you do this with MANUAL MERGE feature on dashboard).
After this add next code to hook_update.
```php
use Drupal\Core\Serialization\Yaml;

// Load data from sync directory (or use here custom path).
$config_dir = \Drupal\Core\Config\FileStorageFactory::getSync();
$config_path = $config_dir . '/config/install/core.entity_form_display.node.landing_page.default.yml';
$data = Yaml::decode(file_get_contents($config_path));

\Drupal::service('openy_upgrade_log.manager')
  ->updateExistingConfig('core.entity_form_display.node.landing_page.default', $data);
```

##### Additional example.
```php
/**
 * Demo code for upgrade tool config conflicts resolving.
 */
function demo_update_8001() {
  $openy_upgrade_log_manager = \Drupal::service('openy_upgrade_log.manager');
  $config_dir = \Drupal\Core\Config\FileStorageFactory::getSync();
  $conflict_configs_list = [
    'leave current version' => [
      'user.role.anonymous',
      'user.role.authenticated',
    ],
    'force update to openy version' => [
      'views.view.social_posts',
      'core.entity_view_display.node.social_post.default',
      'core.entity_view_display.node.social_post.teaser',
      'core.entity_form_display.node.social_post.default',
    ],
    'manual merge version' => [
      'field.field.node.social_post.field_id',
      'field.field.node.social_post.field_platform',
      'field.field.node.social_post.field_posted',
      'field.field.node.social_post.field_sp_image',
    ],
  ];
  foreach ($conflict_configs_list as $action => $config_list) {
    foreach ($config_list as $config_name) {
      switch ($action) {
        case 'leave current version':
          $openy_upgrade_log_manager
            ->loadByName($config_name)
            ->applyCurrentActiveVersion();
          break;

        case 'force update to openy version':
          $openy_upgrade_log_manager
            ->loadByName($config_name)
            ->applyOpenyVersion();
          break;

        case 'manual merge version':
          $config_path = $config_dir . $config_name . '.yml';
          $data = Yaml::decode(file_get_contents($config_path));
          $openy_upgrade_log_manager
            ->updateExistingConfig($config_name, $data);
          break;
      }
    }
  }
}
```
