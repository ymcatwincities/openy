Welcome to OpenY MindBody Tools project.

### About

This module implements tools for developers and content managers.

### QuickStart

To start using the module, please enable it.

#### Price Update

1. Update price matrix in `docroot/modules/custom/ymca_mindbody/modules/openy_mindbody_tools/src/PriceUpdater.php`
2. Run
```sh
drush ev '\Drupal::service("openy_mindbody_tools.price_updater")->update();'
```

### Technical information

Keep in mind that price matrix is hardcoded:

```php
$prices = [
  30 => [
    1 => ['member' => 50, 'nonmember' => 70],
    4 => ['member' => 180, 'nonmember' => 260],
    8 => ['member' => 310, 'nonmember' => 470],
    12 => ['member' => 450, 'nonmember' => 690],
    20 => ['member' => 610, 'nonmember' => 1010],
  ],
  60 => [
    1 => ['member' => 75, 'nonmember' => 95],
    4 => ['member' => 280, 'nonmember' => 360],
    8 => ['member' => 480, 'nonmember' => 640],
    12 => ['member' => 700, 'nonmember' => 960],
    20 => ['member' => 1039, 'nonmember' => 1439],
  ],
];
```

### Disclaimer

Module has hard dependencies from modules:
- ymca_mindbody
