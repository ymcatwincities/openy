# Behat Environment Loader

This tool - is a Behat library for auto loading context classes of extension to context environment.

[![Build Status](https://img.shields.io/travis/BR0kEN-/environment-loader/master.svg?style=flat)](https://travis-ci.org/BR0kEN-/environment-loader)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/BR0kEN-/environment-loader.svg?style=flat)](https://scrutinizer-ci.com/g/BR0kEN-/environment-loader/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/BR0kEN-/environment-loader.svg?style=flat)](https://scrutinizer-ci.com/g/BR0kEN-/environment-loader)
[![Total Downloads](https://poser.pugx.org/behat/environment-loader/downloads)](https://packagist.org/packages/behat/environment-loader)
[![Latest Stable Version](https://poser.pugx.org/behat/environment-loader/v/stable)](https://packagist.org/packages/behat/environment-loader)
[![License](https://poser.pugx.org/behat/environment-loader/license)](https://packagist.org/packages/behat/environment-loader)

## Usage

See examples here:

- [TqExtension](https://github.com/BR0kEN-/TqExtension/blob/master/src/ServiceContainer/TqExtension.php#L40-L41)
- [SoapExtension](https://github.com/asgorobets/SoapExtension/blob/master/src/ServiceContainer/SoapExtension.php#L40-L41)

```php
namespace Behat\ExampleExtension\ServiceContainer;

// ...

class ExampleExtension implements Extension
{
    // ...
    
    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        // Load all context classes from "Behat\ExampleExtension\Context\*" namespace.
        $loader = new EnvironmentLoader($this, $container, $config);
        // Your own environment reader can be easily added.
        // $loader->addEnvironmentReader();
        $loader->load();
    }
    
    // ...
}
```

Here is a good "[how to](tests/behat/extensions)" about extension creation and usage of this library.
