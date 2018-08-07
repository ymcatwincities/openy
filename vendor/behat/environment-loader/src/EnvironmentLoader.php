<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat;

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

/**
 * Class EnvironmentLoader.
 *
 * @package Behat
 */
final class EnvironmentLoader
{
    /**
     * DI container of the extension.
     *
     * @var ContainerBuilder
     */
    private $container;
    /**
     * Path of the extension.
     *
     * @var string
     */
    private $path = '';
    /**
     * Namespace of the extension.
     *
     * @var string
     */
    private $namespace = '';
    /**
     * Configuration key of the extension in a lowercase.
     *
     * @var string
     */
    private $configKey = '';
    /**
     * Formula: <EXTENSION_NAMESPACE>\<EXTENSION_SUB_NAMESPACE>\<EXTENSION_CONFIG_KEY>.
     *
     * @var string
     */
    private $classPath = '';
    /**
     * Definitions for extending container.
     *
     * @var Definition[]
     */
    private $definitions = [];

    /**
     * EnvironmentLoader constructor.
     *
     * @param Extension $extension
     * @param ContainerBuilder $container
     * @param array $config
     */
    public function __construct(Extension $extension, ContainerBuilder $container, array $config = [])
    {
        $reflection = new \ReflectionClass($extension);
        // Remove the "ServiceContainer" from the namespace of the extension object.
        $this->namespace = rtrim(str_replace('ServiceContainer', '', $reflection->getNamespaceName()), '\\');
        // Remove the name of file and "ServiceContainer" from the path to extension.
        $this->path = implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $reflection->getFileName()), 0, -2));
        $this->container = $container;
        // To not care about string format.
        $this->configKey = strtolower($extension->getConfigKey());
        // Formula: <EXTENSION_NAMESPACE>\<EXTENSION_SUB_NAMESPACE>\<EXTENSION_CONFIG_KEY>.
        $this->classPath = implode('\\', [
            $this->namespace,
            // Placeholder for sub-namespace of the extension.
            '%s',
            // - Replace all dots, underscores and dashes by dot in the config key of extension.
            // - Divide the result array by a dot.
            // - Start every string in the result array from a capital letter.
            // - Convert an array to string without separators.
            implode(array_map('ucfirst', explode('.', str_replace(['.', '_', '-'], '.', $this->configKey)))),
        ]);

        /** @see EnvironmentReader::__construct() */
        $this->extendContainer(EnvironmentExtension::READER_TAG, new Definition(
            sprintf('%s\EnvironmentReader', __NAMESPACE__),
            [$this->path, $this->namespace]
        ), 'behat');

        $this->addDefinition(
            'Context',
            'Initializer',
            ContextInitializer::class,
            ContextExtension::INITIALIZER_TAG,
            [$config, $this->namespace, $this->path]
        );
    }

    /**
     * Implement extension's own environment reader.
     *
     * @param array $arguments
     */
    public function addEnvironmentReader(array $arguments = [])
    {
        // Full namespace: <EXTENSION_NAMESPACE>\Environment\<EXTENSION_CONFIG_KEY>EnvironmentReader.
        // For example we have registered extension at namespace: "Behat\TqExtension". Class, which
        // implements extension interface, located at "Behat\TqExtension\ServiceContainer\TqExtension"
        // and its method, "getConfigKey()", returns the "tq" string. In this case the full namespace
        // of the reader object will be: "Behat\TqExtension\Environment\TqEnvironmentReader" and its
        // constructor will have a set of arguments that were passed to this method.
        $this->addDefinition(
            'Environment',
            'Reader',
            EnvironmentExtensionReader::class,
            EnvironmentExtension::READER_TAG,
            array_merge([$this->namespace, $this->path], $arguments)
        );
    }

    /**
     * Extend DI container by dependencies.
     */
    public function load()
    {
        foreach ($this->definitions as $tag => $definition) {
            $this->extendContainer($tag, $definition);
        }
    }

    /**
     * Add dependency definition for DI container.
     *
     * @param string $subNamespace
     * @param string $objectType
     * @param string $interface
     * @param string $tag
     * @param array $arguments
     */
    private function addDefinition($subNamespace, $objectType, $interface, $tag, array $arguments = [])
    {
        $class = sprintf($this->classPath, $subNamespace) . $subNamespace . $objectType;

        if (!class_exists($class)) {
            throw new \RuntimeException(sprintf('Class "%s" does not exists!', $class));
        }

        if (!in_array($interface, class_implements($class))) {
            throw new \RuntimeException(sprintf('Class "%s" must implement the "%s" interface!', $class, $interface));
        }

        $this->definitions[$tag] = new Definition($class, $arguments);
    }

    /**
     * Add dependency to DI container.
     *
     * @param string $tag
     * @param Definition $definition
     * @param string $identifier
     */
    private function extendContainer($tag, Definition $definition, $identifier = '')
    {
        if ('' !== $identifier) {
            $identifier .= '.';
        }

        $this->container
          ->setDefinition("$this->configKey.$identifier$tag", $definition)
          ->addTag($tag);
    }
}
