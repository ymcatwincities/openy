<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat\DebugExtension;

use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Message.
 *
 * @package Behat\DebugExtension
 */
class Message
{
    /**
     * @var string
     */
    private $message = '';
    /**
     * @var ConsoleOutput
     */
    private static $console;

    /**
     * Message constructor.
     *
     * @link http://symfony.com/doc/current/components/console/introduction.html#coloring-the-output
     *
     * @param string $type
     *   Could be "comment", "info", "question" or "error".
     * @param int $indent
     *   Number of spaces.
     * @param string[] $strings
     *   Paragraphs.
     * @param string[] $placeholders
     *   Any replacement argument for "sprintf()".
     * @param bool $output
     *   Print message or not.
     */
    public function __construct($type, $indent, array $strings, array $placeholders = [], $output = true)
    {
        $indent = implode(' ', array_fill_keys(range(0, $indent), '')) . "<$type>";
        $this->message = vsprintf($indent . implode("\n</$type>$indent", $strings) . "</$type>", $placeholders);

        if (null === self::$console) {
            self::$console = new ConsoleOutput();
        }

        if ($output) {
            $this->output();
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->message;
    }

    /**
     * Print message to command line.
     */
    public function output()
    {
        self::$console->writeln($this->message);
    }
}
