<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat\DebugExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;

/**
 * Class DebugExtension.
 *
 * @package Behat\DebugExtension\ServiceContainer
 */
class DebugExtension implements Extension
{
    const TAG = 'debug';

    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return static::TAG;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $this->definition($container, 'EventSubscriber', EventDispatcherExtension::SUBSCRIBER_TAG);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
    }

    /**
     * Define a new service in DI container.
     *
     * @param ContainerBuilder $container
     *   DI container.
     * @param string $class
     *   Class in namespace of extension.
     * @param string $tag
     *   Tag of the definition.
     * @param array $arguments
     *   Dependency arguments.
     *
     * @return Definition
     *   Tagged definition.
     */
    private function definition(ContainerBuilder $container, $class, $tag, array $arguments = [])
    {
        $definition = new Definition(strtr(__NAMESPACE__, ['ServiceContainer' => $class]), $arguments);

        return $container
            ->setDefinition($tag . '.' . self::id($class), $definition)
            ->addTag($tag);
    }

    /**
     * Transform name of class to ID of service.
     *
     * @example
     * Behat\DebugExtension\Debugger => behat.debug.extension.debugger
     *
     * @param string $className
     *   Name of class to transform.
     *
     * @return string
     *   Transformed string.
     */
    private static function id($className)
    {
        // 1. Remove all backslashes from a string.
        // 2. Split string to array by a capital letter.
        // 3. Transform an array to string. Elements are separated by dot.
        // 4. Remove first dot from a left side.
        // 5. Transform string to a lowercase.
        return strtolower(ltrim(implode('.', preg_split('/(?=[A-Z])/', str_replace('\\', '', $className))), '.'));
    }
}
