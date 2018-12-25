<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat\Tests\DummyExtension\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;

/**
 * Class DummyContextInitializer.
 *
 * @package Behat\Tests\DummyExtension\Context
 */
class DummyContextInitializer implements ContextInitializer
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
        if ($context instanceof DummyContextInterface) {
            $context->setParameters($this->parameters);
        }
    }
}
