# Address

Provides functionality for storing, validating and displaying international postal addresses.
The Drupal 8 heir to the addressfield module, powered by the [commerceguys/addressing](https://github.com/commerceguys/addressing) library.

## Installation
Since the module requires external libraries, Composer or Ludwig must be used.

### Composer
If your site is [managed via Composer](https://www.drupal.org/node/2718229), use Composer to
download the module, which will also download the required libraries:
   ```sh
   composer require "drupal/address ~1.0"
   ```
~1.0 downloads the latest release, use 1.x-dev to get the -dev release instead.
Use ```composer update drupal/address --with-dependencies``` to update to a new release.

### Ludwig
Otherwise, download and install [Ludwig](https://www.drupal.org/project/ludwig) which will allow you
to download the libraries separately:
1) Download Address into your modules folder.
2) Use one of Ludwig's methods to download libraries:

    a) Run the ```ludwig:download``` Drupal Console command or the ```ludwig-download``` Drush command.

    b) Go to ```/admin/reports/packages``` and download each library manually, then place them under address/lib as specified.

3) Enable Address.

Note that when using Ludwig, updating the module will require re-downloading the libraries.
Composer is recommended whenever possible.
