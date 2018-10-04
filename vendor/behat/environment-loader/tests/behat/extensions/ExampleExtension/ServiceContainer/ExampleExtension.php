<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat\Tests\ExampleExtension\ServiceContainer;

use Behat\EnvironmentLoader;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Class ExampleExtension.
 *
 * @package Behat\SoapExtension\ServiceContainer
 */
class ExampleExtension implements Extension
{
    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return 'example';
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
        $loader = new EnvironmentLoader($this, $container, $config);
        $loader->load();
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
}
