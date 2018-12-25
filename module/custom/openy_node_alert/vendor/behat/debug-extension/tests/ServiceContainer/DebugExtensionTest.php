<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat\Tests\DebugExtension\ServiceContainer;

use Behat\DebugExtension\ServiceContainer\DebugExtension;
use Behat\DebugExtension\EventSubscriber;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;

/**
 * Class DebugExtensionTest.
 *
 * @package Behat\Tests\DebugExtension\ServiceContainer
 */
class DebugExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DebugExtension
     */
    private $extension;
    /**
     * @var ExtensionManager
     */
    private $extensionManager;
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->extension = new DebugExtension();
        $this->container = new ContainerBuilder();
        $this->extensionManager = new ExtensionManager([$this->extension]);
    }

    /**
     * @test
     */
    public function getConfigKey()
    {
        self::assertSame('debug', $this->extension->getConfigKey());
    }

    /**
     * @test
     */
    public function initialize()
    {
        // Initialization don't do anything.
        $extensionManager = $this->extensionManager;
        $this->extension->initialize($this->extensionManager);
        self::assertSame($extensionManager, $this->extensionManager);
    }

    /**
     * @test
     * @depends initialize
     */
    public function load()
    {
        $id = EventDispatcherExtension::SUBSCRIBER_TAG . '.event.subscriber';

        try {
            $this->container->get($id);
            self::fail(sprintf('The "%s" service is available, but must not.'));
        } catch (\Exception $e) {
            self::assertTrue(
                $e instanceof ServiceNotFoundException ||
                // Handle "composer install --prefer-lowest"
                $e instanceof \InvalidArgumentException
            );
        }

        $this->extension->load($this->container, []);
        self::assertTrue($this->container->get($id) instanceof EventSubscriber);
    }

    /**
     * @test
     * @depends load
     */
    public function process()
    {
        // Processing don't do anything.
        $container = $this->container;
        $this->extension->process($container);
        self::assertSame($container, $this->container);
    }

    /**
     * @test
     */
    public function configure()
    {
        // Extension has no any configuration.
        $config = $original = new ArrayNodeDefinition('test');
        $this->extension->configure($config);
        self::assertSame($config, $original);
    }
}
