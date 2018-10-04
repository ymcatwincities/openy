<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat\Tests\ExampleExtension\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;

/**
 * Class ExampleContextInitializer.
 *
 * @package Behat\Tests\ExampleExtension\Context
 */
class ExampleContextInitializer implements ContextInitializer
{
    /**
     * Parameters of context.
     *
     * @var array
     */
    private $parameters = [];

    /**
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof ExampleContextInterface) {
            $context->setParameters($this->parameters);
        }
    }
}
