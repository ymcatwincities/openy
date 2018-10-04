<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat\Tests\ExampleExtension\Context;

use Behat\Behat\Context\Context;

/**
 * Interface ExampleContextInterface.
 *
 * @package Behat\Tests\ExampleExtension\Context
 */
interface ExampleContextInterface extends Context
{
    /**
     * Set parameters from behat.yml.
     *
     * @param array $parameters
     *   An array of parameters from configuration file.
     */
    public function setParameters(array $parameters);
}
