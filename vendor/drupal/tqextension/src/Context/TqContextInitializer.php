<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Context;

use Behat\Behat\Context as Behat;

class TqContextInitializer implements Behat\Initializer\ContextInitializer
{
    /**
     * Parameters of TqExtension.
     *
     * @var array
     */
    private $parameters = [];

    /**
     * @param array $parameters
     * @param string $namespace
     * @param string $path
     */
    public function __construct(array $parameters, $namespace, $path)
    {
        $parameters['namespace'] = $namespace;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeContext(Behat\Context $context)
    {
        if ($context instanceof TqContextInterface) {
            $context->setTqParameters($this->parameters);
        }
    }
}
