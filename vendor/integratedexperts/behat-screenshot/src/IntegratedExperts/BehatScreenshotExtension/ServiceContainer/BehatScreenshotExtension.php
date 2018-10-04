<?php

/**
 * @file
 * Behat screenshot extension.
 */

namespace IntegratedExperts\BehatScreenshotExtension\ServiceContainer;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class ScreenshotExtension
 */
class BehatScreenshotExtension implements ExtensionInterface
{

    /**
     * Extension configuration ID.
     */
    const MOD_ID = 'integratedexperts_screenshot';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return self::MOD_ID;
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
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder->children()
            ->scalarNode('dir')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('fail')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('fail_prefix')->defaultValue('failed_')->end()
            ->scalarNode('purge')->isRequired()->cannotBeEmpty()->end();
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $definition = new Definition('IntegratedExperts\BehatScreenshotExtension\Context\Initializer\ScreenshotContextInitializer', [
            $config['dir'],
            $config['fail'],
            $config['fail_prefix'],
            $config['purge'],
        ]);
        $definition->addTag(ContextExtension::INITIALIZER_TAG, ['priority' => 0]);
        $container->setDefinition('integratedexperts_screenshot.screenshot_context_initializer', $definition);
    }
}
