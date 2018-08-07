<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat\Tests\DebugExtension;

use Behat\DebugExtension\EventSubscriber;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\StepTested;

/**
 * Class EventSubscriberTest.
 *
 * @package Behat\Tests\DebugExtension
 */
class EventSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventSubscriber
     */
    private $eventSubscriber;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->eventSubscriber = new EventSubscriber();
    }

    /**
     * @test
     */
    public function getSubscribedEvents()
    {
        $events = $this->eventSubscriber->getSubscribedEvents();

        self::assertCount(3, $events);

        foreach ([
          StepTested::AFTER,
          FeatureTested::AFTER,
          ScenarioTested::BEFORE,
        ] as $event) {
            self::assertArrayHasKey($event, $events);
        }
    }
}
