Environment config
============================
This module provides a service which allows you to manage different configurations based on environment.

## Usage
As example you have specific configuration for dev, staging and production, but you have to switch them accordingly.

##### Configuration for environments
`training.labels.env.yml` within module or configuration folder:
```yaml
# Active environment by default
active: 'staging'

# Configs grouped by environment.
production:
  programs:
    2: { label: "Personal Training", active: true}
    3: { label: "Internal Staff Appointments", active: false }

  session_types:
    5: { label: "Personal Training - Express - 30 Minutes", active: true }
    7: { label: "Personal Training - Full - 60 Minutes", active: true }

staging:
  programs:
    1: { label: "Sandbox Personal Training", active: true }
    2: { label: "Sandbox Court Rentals", active: true }

  session_types:
    23: { label: "Sandbox Nutrition Consultation", active: true }
    200: { label: "Sandbox 60 Min 1on1", active: true }
    264: { label: "Sandbox Personal Training Consult", active: true }
```
##### Default configuration that is used by Drupal
`training.labels.yml` within module or configuration folder:
```yaml
programs:
  2: { label: "Personal Training", active: true}
  3: { label: "Internal Staff Appointments", active: false }

session_types:
  5: { label: "Personal Training - Express - 30 Minutes", active: true }
  7: { label: "Personal Training - Full - 60 Minutes", active: true }
```

##### How do you retrieve configs in Drupal
```php
$settings = \Drupal::config('training.labels');
```

##### How to override configs
```php
# Set active configuration from training.labels.env.yml.
\Drupal::service('environment_config.handler')->setActiveConfig('training.labels', 'production');
```
In that way configuration in `training.labels.yml` will be overridden by value from `training.labels.env.yml` for appropriate environment.

## Naming convention
In order to get it works, you should use specific names for files.

Environment settings pattern: `MODULE_NAME.SETTINGS_NAME.env.yml`

Active settings storage: `MODULE_NAME.SETTINGS_NAME.yml`

## Available commands
##### Set environment specific configs
```php
Drupal::service('environment_config.handler')->setActiveConfig('SETTINGS_NAME', 'ENVIRONMENT_NAME');
```

##### Get active configuration
```php
\Drupal::service('environment_config.handler')->getActiveConfig('SETTINGS_NAME');
```

##### Get active environment indicator
```php
\Drupal::service('environment_config.handler')->getEnvironmentIndicator('SETTINGS_NAME');
```

##### Get environment config
```php
\Drupal::service('environment_config.handler')->getEnvironmentConfig('SETTINGS_NAME', 'ENVIRONMENT_NAME');
```
