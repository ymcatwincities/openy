<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\Tests\TqExtension\Cores;

use Drupal\TqExtension\Cores\DrupalKernelPlaceholder;

/**
 * Class DrupalKernelPlaceholder.
 *
 * @package Drupal\Tests\TqExtension\Cores
 *
 * @covers \Drupal\TqExtension\Cores\Drupal7Placeholder
 * @covers \Drupal\TqExtension\Cores\Drupal8Placeholder
 * @coversDefaultClass \Drupal\TqExtension\Cores\DrupalKernelPlaceholder
 */
class DrupalKernelPlaceholderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $input
     * @param array $arguments
     * @param string $expected
     *
     * @dataProvider stringFormatProvider
     */
    public function testT($input, array $arguments, $expected)
    {
        static::assertSame($expected, DrupalKernelPlaceholder::t($input, $arguments));
    }

    /**
     * @param string $input
     * @param array $arguments
     * @param string $expected
     *
     * @dataProvider stringFormatProvider
     */
    public function testFormatString($input, array $arguments, $expected)
    {
        static::assertSame($expected, DrupalKernelPlaceholder::formatString($input, $arguments));
    }

    /**
     * @covers ::setCurrentPath
     * @covers ::arg
     */
    public function testArg()
    {
        DrupalKernelPlaceholder::setCurrentPath('test/page/nested');

        static::assertSame(['test', 'page', 'nested'], DrupalKernelPlaceholder::arg());
    }

    /**
     * @covers ::entityLoad
     * @covers ::entityHasField
     * @covers ::setCurrentUser
     * @covers ::getCurrentUser
     * @covers ::tokenReplace
     */
    public function testTokenReplace()
    {
        $user = DrupalKernelPlaceholder::entityLoad('user', 1);

        static::assertNotEmpty($user);

        DrupalKernelPlaceholder::setCurrentUser($user);

        static::assertSame('Hi admin!', DrupalKernelPlaceholder::tokenReplace('Hi [user:name]!', [
            'user' => DrupalKernelPlaceholder::getCurrentUser(),
        ]));
    }

    public function testGetUidByName()
    {
        static::assertSame(1, DrupalKernelPlaceholder::getUidByName('admin'));
    }

    /**
     * @param string $expected
     * @param string $contentType
     *
     * @dataProvider contentTypeNameProvider
     */
    public function testGetContentTypeName($expected, $contentType)
    {
        static::assertSame($expected, DrupalKernelPlaceholder::getContentTypeName($contentType));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEntityFieldValueException()
    {
        DrupalKernelPlaceholder::entityFieldValue(new \stdClass(), 'uid');
    }

    public function contentTypeNameProvider()
    {
        return [
            ['article', 'article'],
            ['page', 'Basic page'],
        ];
    }

    public function stringFormatProvider()
    {
        return [
            ['Test', [], 'Test'],
            ['Test @argument', ['@argument' => 1], 'Test 1'],
        ];
    }
}
