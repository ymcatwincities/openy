# Config Importer

This module aims to cover development needs with managing configurations during project development.

Importing all configs during development is not convenient and can lead to a bad behaviour with overwriting or deleting configs or content from a site.

So there is a need for importing only of specific configs via `hook_update_N()`.

## Usage

All the work happening with the `config_import.importer` service. Let's instantiate it:

```php
/* @var \Drupal\config_import\ConfigImporterService $config_importer */
$config_importer = \Drupal::service('config_import.importer');
```

By default, import and export operations will use the [sync](https://www.drupal.org/docs/8/configuration-management/changing-the-storage-location-of-the-sync-directory) directory. But, if needed, it could be changed to a path of existing directory or type of already configured configuration directories. For instance:

```php
// $config_importer->setDirectory(CONFIG_STAGING_DIRECTORY);
$config_importer->setDirectory('/var/config');
```

You may do so to import existing configs:

```php
$config_importer->importConfigs(['core.extension']);
```

And export can be achieved with a similar construction:

```php
$config_importer->exportConfigs(['core.extension']);
```

## Drush integration

Execute the next command to see the list of available commands from a group:

```shell
drush help --filter=config_import
```

Then use the following syntax to find out more information about concrete command:

```shell
drush help COMMAND_NAME
```
