<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat\Tests\DummyExtension\Context;

/**
 * Class DummyContext.
 *
 * @package Behat\Tests\DummyExtension\Context
 */
class DummyContext extends RawDummyContext
{
    /**
     * @Then dummy step
     */
    public function dummyStep()
    {
    }
}
