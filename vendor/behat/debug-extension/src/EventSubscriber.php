<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Behat\DebugExtension;

// Event subscriber.
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
// Feature events.
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
// Scenario events.
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
// Step events.
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;

/**
 * Class EventSubscriber.
 *
 * @package Behat\DebugExtension
 */
class EventSubscriber implements EventSubscriberInterface
{
    use Debugger;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events = [];

        foreach ([
            StepTested::AFTER,
            FeatureTested::AFTER,
            ScenarioTested::BEFORE,
        ] as $event) {
            $events[$event] = ['output', 100];
        }

        return $events;
    }

    /**
     * Print debug messages.
     *
     * @param AfterFeatureTested|BeforeScenarioTested|AfterStepTested $event
     */
    public function output($event)
    {
        if (method_exists($event, 'getFeature') && $event->getFeature()->hasTag('debug')) {
            self::printMessages();
        }
    }
}
