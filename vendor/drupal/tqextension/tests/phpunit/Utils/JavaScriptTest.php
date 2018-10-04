<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\Tests\TqExtension\Utils;

use Drupal\TqExtension\Utils\JavaScript;
use Drupal\Tests\TqExtension\TraitTest;

/**
 * Class LogicalAssertionTest.
 *
 * @package Drupal\Tests\TqExtension\Utils
 *
 * @property JavaScript $target
 *
 * @coversDefaultClass \Drupal\TqExtension\Utils\JavaScript
 */
class JavaScriptTest extends TraitTest
{
    const FQN = JavaScript::class;

    /**
     * @covers ::getJavaScriptFileContents
     * @dataProvider validFileNames
     *
     * @param string $filename
     */
    public function testGetJavaScriptFileContents($filename)
    {
        static::assertNotEmpty($this->invokeMethod('getJavaScriptFileContents', [$filename]));
    }

    /**
     * @covers ::getJavaScriptFileContents
     * @dataProvider invalidFileNames
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /File ".*" does not exists!/
     *
     * @param string $filename
     */
    public function testGetJavaScriptFileContentsInvalid($filename)
    {
        $this->invokeMethod('getJavaScriptFileContents', [$filename]);
    }

    public function testJavaScriptFilesCount()
    {
        $files = glob(TQEXTENSION_ROOT . '/src/JavaScript/*.js');

        if (false === $files) {
            static::fail('glob() exited with an error.');
        }

        static::assertCount(count($this->validFileNames()), $files);
    }

    public function validFileNames()
    {
        return [
            ['CatchErrors'],
            ['TrackXHREvents'],
        ];
    }

    public function invalidFileNames()
    {
        return [
            ['.DS_Store'],
            ['dummy_file'],
        ];
    }
}
