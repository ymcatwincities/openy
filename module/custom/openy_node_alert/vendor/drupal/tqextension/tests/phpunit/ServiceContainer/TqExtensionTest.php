<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\Tests\TqExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\ExtensionManager;
use Drupal\Driver\DrupalDriver;
use Drupal\TqExtension\ServiceContainer\TqExtension;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class TqExtensionTest.
 *
 * @package Drupal\Tests\TqExtension\ServiceContainer
 */
class TqExtensionTest extends \PHPUnit_Framework_TestCase
{
    const KEY = 'tq';

    /**
     * @var TqExtension
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
    protected function setUp()
    {
        $this->extension = new TqExtension();
        $this->container = new ContainerBuilder();
        $this->extensionManager = new ExtensionManager([$this->extension]);

        // @see TqExtension::load()
        $this->container->setDefinition('drupal.driver.drupal', new Definition(
            DrupalDriver::class,
            [DRUPAL_BASE, DRUPAL_HOST]
        ));
    }

    /**
     * @test
     */
    public function getConfigKey()
    {
        self::assertSame(self::KEY, $this->extension->getConfigKey());
    }

    /**
     * @test
     */
    public function initialize()
    {
        $this->extension->initialize($this->extensionManager);
        // After initialization of extension we must have the "debug" initialized also.
        self::assertTrue(null !== $this->extensionManager->getExtension('debug'));
    }

    /**
     * @test
     * @depends initialize
     */
    public function load()
    {
        $this->extension->load($this->container, []);

        foreach (['behat.environment.reader', 'context.initializer'] as $id) {
            self::assertTrue(null !== $this->container->hasDefinition(self::KEY . '.' . $id));
        }
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
     * @todo
     */
    public function configure()
    {
        $tree = new TreeBuilder();
        $config = $tree->root(self::KEY);

        $this->extension->configure($config);
    }
}
