<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\Tests\TqExtension\Functional;

/**
 * Class TqContextTest.
 *
 * @package Drupal\Tests\TqExtension\Functional
 *
 * @coversDefaultClass \Drupal\TqExtension\Context\TqContext
 */
class TqContextTest extends BehatTest
{
    /**
     * @covers ::assertElementAttribute
     * @covers ::workWithElementsInRegion
     * @covers ::unsetWorkingElementScope
     */
    public function test()
    {
        $this->runFeaturesGroup('TqContext');
    }
}
