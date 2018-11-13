##### Welcome to Antifraud scanner for MindBody

Current module is making fetch of Appointments for all staff members
and doing search if some appointment was created in the past

##### Settings

Use

```php
Drupal::service('mindbody_anti_fraud.scanner')->scan();
```

php code to run this via Debug module.

Use

```bash
drush -y ev 'mindbody_antifraud_scan();'
```

to run the scanner from console or cron task.