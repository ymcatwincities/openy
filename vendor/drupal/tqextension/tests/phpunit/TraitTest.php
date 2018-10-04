<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\Tests\TqExtension;

/**
 * Class TraitTest.
 *
 * @package Drupal\Tests\TqExtension
 */
abstract class TraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Fully-qualified namespace of trait.
     */
    const FQN = '';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|object
     */
    protected $target;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->target = $this->getMockForTrait(static::FQN);
    }

    /**
     * Invoke protected/private method.
     *
     * @param string $name
     *   Name of method.
     * @param array $arguments
     *   List of arguments.
     *
     * @return mixed
     *   Invocation result.
     */
    protected function invokeMethod($name, array $arguments = [])
    {
        $method = (new \ReflectionClass(static::FQN))->getMethod($name);

        if ($method->isPublic()) {
            throw new \BadMethodCallException(sprintf('Method "%s" is publicly visible, call it directly.', $name));
        }

        $method->setAccessible(true);

        return $method->invokeArgs($this->target, $arguments);
    }
}
