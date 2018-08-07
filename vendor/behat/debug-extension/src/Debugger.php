<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat\DebugExtension;

/**
 * Trait Debugger.
 *
 * @package Behat\DebugExtension
 */
trait Debugger
{
    /**
     * Store message for debugging.
     *
     * @param string[] $strings
     *   The lines to print. One item per line. Every item will be processed by sprintf().
     * @param string[] $placeholders
     *   Placeholder values for sprintf().
     */
    public static function debug(array $strings, array $placeholders = [])
    {
        // Initialize messages storage.
        if (!isset($_SESSION[__TRAIT__])) {
            $_SESSION[__TRAIT__] = [];
        }

        // Mark debug message.
        array_unshift($strings, '<question>DEBUG:</question>');

        $_SESSION[__TRAIT__][] = new Message('comment', 4, $strings, $placeholders, (bool) getenv('BEHAT_DEBUG'));
    }

    /**
     * Output messages to a command line.
     *
     * @param bool $clearQueue
     *   Is message queue should be cleared after output?
     */
    public static function printMessages($clearQueue = true)
    {
        if (!empty($_SESSION[__TRAIT__])) {
            /** @var Message $message */
            foreach ($_SESSION[__TRAIT__] as $message) {
                $message->output();
            }
        }

        if ($clearQueue) {
            static::clearQueue();
        }
    }

    /**
     * Clear messages queue.
     */
    public static function clearQueue()
    {
        unset($_SESSION[__TRAIT__]);
    }
}
