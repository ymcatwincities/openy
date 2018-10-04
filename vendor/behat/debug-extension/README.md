# Behat Debug Extension

Print any information you'd like to a command line during the test suite execution.

[![Build Status](https://img.shields.io/travis/BR0kEN-/behat-debug-extension/master.svg?style=flat)](https://travis-ci.org/BR0kEN-/behat-debug-extension)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/BR0kEN-/behat-debug-extension.svg?style=flat)](https://scrutinizer-ci.com/g/BR0kEN-/behat-debug-extension/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/BR0kEN-/behat-debug-extension.svg?style=flat)](https://scrutinizer-ci.com/g/BR0kEN-/behat-debug-extension)
[![Total Downloads](https://poser.pugx.org/behat/debug-extension/downloads)](https://packagist.org/packages/behat/debug-extension)
[![Latest Stable Version](https://poser.pugx.org/behat/debug-extension/v/stable)](https://packagist.org/packages/behat/debug-extension)
[![License](https://poser.pugx.org/behat/debug-extension/license)](https://packagist.org/packages/behat/debug-extension)

## Usage

Add `@debug` tag to your feature definition:

```gherkin
@debug
Feature: Test

  Scenario: Test
  # ...
```

Add extension to your configuration file:

```yml
default:
  extensions:
    Behat\DebugExtension: ~
```

Extend your object with a trait:

```php
use Behat\DebugExtension\Debugger;

class Example
{
    use Debugger;
}
```

Use the `debug` method wherever you like:

```php
public function method()
{
    // ...
    self::debug([
        'Function arguments: %s',
        'Second line',
    ], [
        var_export(func_get_args(), true),
    ]);
    // ...
}
```

As you can see the `debug` method processed by `sprintf()` function, so second argument for a method is an array of placeholders. 

### Messages

Also, with this extension, you able to print styled messages to a command line.

```php
new \Behat\DebugExtension\Message('comment', 2, [
    'This is a first line of a message that will be printed to a command line.',
    'Read documentation for this class to know how to use it.',
]);
```

### Programmatic usage

```shell
export BEHAT_DEBUG=true
```

This environment variable tells that messages should be printed in any way.
