<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat\Tests\DebugExtension;

use Behat\DebugExtension\Debugger;

/**
 * Class DebuggerTest.
 *
 * @package Behat\Tests\DebugExtension\ServiceContainer
 */
class DebuggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Debugger
     */
    private $debugger;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->debugger = $this->getMockForTrait(Debugger::class);
    }

    /**
     * @test
     */
    public function debug()
    {
        $this->debugger->debug(['This is a %s.'], ['test message']);

        self::assertTrue(isset($_SESSION[Debugger::class]));

        foreach ($_SESSION[Debugger::class] as $message) {
            if (strpos($message, 'This is a test message.') !== false) {
                // Stop this test when message has been found.
                return;
            }
        }

        self::fail('Message not found.');
    }

    /**
     * @test
     * @depends debug
     */
    public function printMessages()
    {
        if (empty($_SESSION[Debugger::class])) {
            self::fail('Debug messages does not exists!');
        } else {
            // We should have only one message.
            self::assertCount(1, $_SESSION[Debugger::class]);
            $this->debugger->printMessages();
            // Messages queue should not be cleared.
            self::assertTrue(empty($_SESSION[Debugger::class]));
        }
    }
}
