### How to cache membership data

```
drush ev '\Drupal::service("daxko.data_wrapper")->populateDaxkoMembershipTypes();'
```

### How to remove all membership data.

```
drush ev '\Drupal::service("daxko.data_wrapper")->deleteMembershipTypeMappings();'
```
