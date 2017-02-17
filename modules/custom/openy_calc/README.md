# Openy Calc

Openy Membership Calculator implementation.

### How to cache membership data

```
drush ev '\Drupal::service("openy_calc.data_wrapper")->populateDaxkoMembershipTypes();'
```

### How to remove all membership data.

```
drush ev '\Drupal::service("openy_calc.data_wrapper")->deleteMembershipTypeMappings();'
```
