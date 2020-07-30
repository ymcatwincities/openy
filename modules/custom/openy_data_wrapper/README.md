**Open Y Data Wrapper** module contains a pluggable distributed **Data Wrapper** service.

This service based on the **Open Y Socrates** module (_See openy_socrates/README.adoc_).

**DataWrapper** service contains methods for **Open Y Map** and **Open Y Membership Calculator** modules.

**Open Y Map**:

- getLocationPins
- getBranchPins
- getLocations

**Open Y Membership Calculator**:
- getMembershipTypes
- getSummary
- getRedirectUrl

Data provided by those methods can be different for sites, thanks to **Open Y Socrates**, it can be overridden by your custom service with higher priority.

For this, you need to create a new service in custom module and set the highest priority. Example:

```yaml
  custom_data_wrapper:
    class: Drupal\custom_data_wrapper\CustomDataWrapper
    arguments:
      - '@entity.query'
      - '@renderer'
      - '@entity_type.manager'
      - '@socrates'
      - '@cache.data'
      - '@logger.channel.openy_data_wrapper'
      - '@config.factory'
    tags:
      - { name: "openy_data_service", priority: 1000 }
```

Service class should implement **OpenyDataServiceInterface**.

In the `addDataServices` specify methods that should be overridden. Example:

```php
  /**
   * {@inheritdoc}
   */
  public function addDataServices(array $services) {
    return [
      'getSummary',
      'getMembershipTypes',
    ];
  }
```

And implement those methods in your `CustomDataWrapper` class.

After this, **Open Y Map** and **Open Y Membership Calculator** will use your custom methods instead of **DataWrapper**.
