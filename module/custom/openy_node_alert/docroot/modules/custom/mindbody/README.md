# MindBody API

MindBody API client implementation.

## How to install and configure

1. Install the module.
2. Go to `/admin/config/system/mindbody` and provide your credentials.

## How to use

For example, to get a list of locations use the next code:
`$locations = \Drupal::service('mindbody.client')->call('SiteService', 'GetLocations');`
