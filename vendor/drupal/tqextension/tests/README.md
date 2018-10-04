# Running tests

**Note**: Drupal will be (re)installed and bootstrapped (and downloaded, if not exists) programmatically for every tests execution. This lead to an additional overhead.

## Dependencies

To run the tests you should have properly configured environment with **PHP** equal or above of `5.5`, **Selenium** standalone server `2.53` or **PhantomJS** `2` and **MySQL** `5.5` or greater.

## Installation

In a root directory of this repository do the `composer install` and choose one of testing strategies below after all dependencies will be installed.

Default credentials for **Drupal site** are: `admin/admin` and they never MUST NOT be changed since used in tests.

**MySQL** should be accessible on `127.0.0.1` with `root` as a user and **empty password**. To change username and/or password use the following environment variables:

- `DRUPAL_DB_USER`
- `DRUPAL_DB_PASS`

### Example

```shell
DRUPAL_DB_USER="test_user" DRUPAL_DB_PASS="1p4sSWD#" ./bin/phpunit
```

### Drupal 7

```shell
DRUPAL_CORE=7 ./bin/phpunit --coverage-text
```

### Drupal 8

```shell
DRUPAL_CORE=8 ./bin/phpunit --coverage-text
```
