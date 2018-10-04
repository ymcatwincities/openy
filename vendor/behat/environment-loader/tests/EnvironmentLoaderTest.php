<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat\Tests;

// Environment loader tools.
use Behat\EnvironmentLoader;
use Behat\EnvironmentReader;
// Example Behat extension for testing.
use Behat\Tests\ExampleExtension\ServiceContainer\ExampleExtension;
// Behat configuration for testing.
use Behat\Testwork\Suite\GenericSuite;
// Behat extension interface.
use Behat\Testwork\ServiceContainer\Extension;
// Tools for dependency injection.
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
// Behat context extension and tools.
use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Behat\Context\Initializer\ContextInitializer;
// Behat environment extension and tools.
use Behat\Testwork\Environment\ServiceContainer\EnvironmentExtension;
use Behat\Testwork\Environment\Reader\EnvironmentReader as EnvironmentExtensionReader;
// Interface for uninitialized environments with contexts.
use Behat\Behat\Context\Environment\UninitializedContextEnvironment;

/**
 * Class EnvironmentLoaderTest.
 *
 * @package Behat\Tests
 */
class EnvironmentLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EnvironmentLoader
     */
    private $loader;
    /**
     * @var ExampleExtension
     */
    private $extension;
    /**
     * @var ContainerBuilder
     */
    private $container;
    /**
     * Properties and their values of the "EnvironmentLoader" instance.
     *
     * @var array
     */
    private $properties = [];

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->extension = new ExampleExtension();
        $this->container = new ContainerBuilder();
        $this->loader = new EnvironmentLoader($this->extension, $this->container);
    }

    /**
     * @test
     */
    public function loaderConstructor()
    {
        $this->readLoaderProperties();

        foreach ([
          'path' => self::resolvePath(sprintf('%s/behat/extensions/ExampleExtension', __DIR__)),
          'namespace' => sprintf('%s\ExampleExtension', __NAMESPACE__),
          'container' => $this->container,
          'configKey' => $this->extension->getConfigKey(),
          'classPath' => sprintf('%s\%s\Example', $this->properties['namespace'], '%s'),
        ] as $property => $value) {
            static::assertEquals($value, $this->properties[$property]);
        }

        // Reader was set in constructor.
        static::assertTrue($this->isContainerHasDefinition('behat.' . EnvironmentExtension::READER_TAG));
        // Initializer must be defined.
        static::assertTrue($this->isEnvironmentHasDefinition(ContextExtension::INITIALIZER_TAG));
        // And must not be added to DI container.
        static::assertFalse($this->isContainerHasDefinition(ContextExtension::INITIALIZER_TAG));
    }

    /**
     * @test
     *
     * @depends loaderConstructor
     */
    public function addEnvironmentReader()
    {
        $assert = true;

        try {
            // If we add custom environment reader to extension then exception won't thrown.
            $this->loader->addEnvironmentReader();
        } catch (\RuntimeException $e) {
            $assert = false;
        }

        static::assertTrue($this->isEnvironmentHasDefinition(EnvironmentExtension::READER_TAG) === $assert);
    }

    /**
     * @test
     *
     * @depends loaderConstructor
     */
    public function load()
    {
        $this->loader->load();
        $this->readLoaderProperties();

        // Ensure that all definitions added to DI container.
        foreach (array_keys($this->properties['definitions']) as $definition) {
            static::assertTrue($this->isContainerHasDefinition($definition));
        }
    }

    /**
     * @test
     *
     * @depends load
     */
    public function readerConstructor()
    {
        $this->readLoaderProperties();
        // Read all contexts of our ExampleExtension.
        $reader = new EnvironmentReader($this->properties['path'], $this->properties['namespace']);
        // Imagine that we have uninitialized environment with a suite for testing (without configuration).
        $environment = new UninitializedContextEnvironment(new GenericSuite('phpunit', []));

        static::assertTrue($reader->supportsEnvironment($environment));
        // Callees mustn't be returned because they are will be read from environment classes.
        static::assertTrue($reader->readEnvironmentCallees($environment) === []);
        // ExampleExtension has only one context.
        static::assertCount(1, $environment->getContextClasses());
    }

    /**
     * @test
     */
    public function runBehatTests()
    {
        $code = 0;

        static::assertTrue(chdir('tests/behat'));
        system(self::resolvePath('../../vendor/bin/behat --no-colors'), $code);
        static::assertTrue(0 === $code);
    }

    /**
     * Get actual values for properties of the loader.
     */
    private function readLoaderProperties()
    {
        // Read all properties of the loader.
        foreach ((new \ReflectionClass($this->loader))->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
            $this->properties[$property->name] = static::getObjectAttribute($this->loader, $property->name);
        }
    }

    /**
     * @param string $definition
     *
     * @return bool
     */
    private function isContainerHasDefinition($definition)
    {
        return $this->container->has(sprintf('%s.%s', $this->properties['configKey'], $definition));
    }

    /**
     * @param string $definition
     *
     * @return bool
     */
    private function isEnvironmentHasDefinition($definition)
    {
        $definitions = static::getObjectAttribute($this->loader, 'definitions');

        return isset($definitions[$definition]);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private static function resolvePath($path)
    {
        return '/' === DIRECTORY_SEPARATOR ? $path : str_replace('/', '\\', $path);
    }
}
