<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat\Tests\ExampleExtension\Context;

/**
 * Class RawExampleContext.
 *
 * @package Behat\Tests\ExampleExtension\Context
 */
class RawExampleContext implements ExampleContextInterface
{
    /**
     * Parameters of context.
     *
     * @var array
     */
    private $parameters = [];

    /**
     * {@inheritdoc}
     */
    public function setParameters(array $parameters)
    {
        if (empty($this->parameters)) {
            $this->parameters = $parameters;
        }
    }

    /**
     * @param string $name
     *   The name of parameter from behat.yml.
     *
     * @return mixed
     */
    protected function getParameter($name)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : false;
    }
}
