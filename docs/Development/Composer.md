# Composer
Please always make sure `composer.lock` file is updated after any changes in `composer.json` file.
You can use `composer update` command to update any package, in this case composer will take care about updating of `composer.lock` file.

```
composer update drupal/metatag
```

Also you can use `composer update --lock` command to force updating of `composer.lock` file according to dependencies in `composer.json`.

Please check official composer documentation for details: https://getcomposer.org/doc/01-basic-usage.md