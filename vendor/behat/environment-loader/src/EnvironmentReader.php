<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat;

// Interface for environments.
use Behat\Testwork\Environment\Environment;
// Interface for environments with contexts.
use Behat\Behat\Context\Environment\ContextEnvironment;
// Interface for uninitialized environments with contexts.
use Behat\Behat\Context\Environment\UninitializedContextEnvironment;
// Interface for environment readers.
use Behat\Testwork\Environment\Reader\EnvironmentReader as EnvironmentReaderInterface;

/**
 * Class EnvironmentReader.
 *
 * @package Behat
 */
final class EnvironmentReader implements EnvironmentReaderInterface
{
    /**
     * Path to extension sources.
     *
     * @var string
     */
    private $path = '';
    /**
     * Namespace of the extension.
     *
     * @var string
     */
    private $namespace = '';

    /**
     * EnvironmentReader constructor.
     *
     * @param string $path
     * @param string $namespace
     */
    public function __construct($path, $namespace)
    {
        $this->path = $path;
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEnvironment(Environment $environment)
    {
        return $environment instanceof ContextEnvironment;
    }

    /**
     * {@inheritdoc}
     */
    public function readEnvironmentCallees(Environment $environment)
    {
        if ($environment instanceof UninitializedContextEnvironment) {
            // Read all extension contexts.
            foreach (new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::SKIP_DOTS)
            ) as $path => $object) {
                $basename = basename($path);

                // Allow names which not starts from "Raw" and ends by "Context.php".
                if (strrpos($basename, 'Context.php') !== false && strpos($basename, 'Raw') !== 0) {
                    $class = strtr($path, [$this->path => $this->namespace, '.php' => '', '/' => '\\']);

                    if (!$environment->hasContextClass($class)) {
                        $environment->registerContextClass($class);
                    }
                }
            }
        }

        // Just return an empty array and allow Behat to scan context classes for callees.
        return [];
    }
}
