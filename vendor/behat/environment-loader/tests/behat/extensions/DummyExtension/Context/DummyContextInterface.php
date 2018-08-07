<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat\Tests\DummyExtension\Context;

use Behat\Behat\Context\Context;

/**
 * Interface DummyContextInterface.
 *
 * @package Behat\Tests\DummyExtension\Context
 */
interface DummyContextInterface extends Context
{
    /**
     * Set parameters from behat.yml.
     *
     * @param array $parameters
     *   An array of parameters from configuration file.
     */
    public function setParameters(array $parameters);
}
