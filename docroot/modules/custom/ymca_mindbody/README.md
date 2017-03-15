# YMCA MindBody

Ymca mindbody integration.

## Booking

In order to prevent booking event during testing and development `is_production` flag was implemented.
By default is always `FALSE`. In order to swith production mode on the production server please use next command:

`drush cset ymca_mindbody.settings is_production 1`

Otherwise all bookings will be created for `API test` trainer and `69696969` user.

# Personify Mindbody Failed Orders Notifier

To run the process use the next code:

  * With PHP:
  `\Drupal::service("ymca_mindbody.failed_orders_notifier")->run();`
  
  * With Drush:
  `drush ev '\Drupal::service("ymca_mindbody.failed_orders_notifier")->run();'`
