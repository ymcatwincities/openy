# Information for developers

The PHPDoc comment of method should satisfy the next rules:

```php
/**
 * Method description can occupy as many rows as necessary. After description
 * must be an empty line.
 *
 * @see Context::method()
 * @see Context::additionalMethod()
 *
 * @link https://github.com/BR0kEN-/behat-drupal-propeople-context
 *
 * @example
 * Description of the example section.
 *
 * @param string $parameter1
 *   The description of a parameter1. It should be indented by two spaces. Each
 *   line of multiline description should be indented too.
 * @param string $parameter2
 *   The description of a parameter2.
 *
 * @throws \Exception
 * @throws \RuntimeException
 *   The description of exception should satisfy the similar rules as for parameters,
 *   but should be added only if it implicit and throw in another function or method.
 *
 * @return \stdClass
 *
 * @Given /^Regex of the Behat step definition$/
 */
```

- All code should satisfy [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) standard.
- For autoloading objects the [PSR-4](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md) is used.

## API

Each context are accessible from another by help of PHP overloading. If you want to get
the `FormContext` in `UserContext` then you just need to call specific method: `getFormContext`. In
this way, method like `get[ContextName]` return necessary object.

All context objects automatically added to Behat environment in `TqContextReader::readEnvironmentCallees`
method. The `RecursiveDirectoryIterator` iterates the folder with contexts and load file if it name satisfy the
`[ContextName]Context.php`.

