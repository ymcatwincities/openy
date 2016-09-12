# DB Size

Helps to grasp database size.

## How to get the size of the table

```
\Drupal::service('dbsize.manager')->getTablesSize(['node']);
```

## How to get the size of the entity

```
\Drupal::service('dbsize.manager')->getEntitySize('groupex_form_cache');
dsm(round(\Drupal::service('dbsize.manager')->getEntitySize('groupex_form_cache') / 1024 / 1024, 2) . ' M');
```
