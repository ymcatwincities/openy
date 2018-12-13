# Open Y Membership Calculator

Open Y Membership Calculator block implementation.

### How to cache membership data

```
drush ev '\Drupal::service("openy_calc.data_wrapper")->populateDaxkoMembershipTypes();'
```

### How to remove all membership data.

```
drush ev '\Drupal::service("openy_calc.data_wrapper")->deleteMembershipTypeMappings();'
```
