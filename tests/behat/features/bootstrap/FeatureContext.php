<?php
/**
 * @file
 * Feature context.
 */
// Contexts.
use Drupal\TqExtension\Context\RawTqContext;
// Helpers.
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\Selenium2Driver;

/**
 * Class FeatureContext.
 */
class FeatureContext extends RawTqContext {

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

  /**
   * @param int $seconds
   *   Amount of seconds when nothing to happens.
   *
   * @Given /^(?:|I )wait (\d+) seconds$/
   */
  public function waitSeconds($seconds)
  {
    sleep($seconds);
  }

  /**
   * @When I wait for :cssSelector
   * @param $cssSelector
   * @throws \Exception
   */
  public function iWaitFor($cssSelector)
  {
    $this->spin(function($context) use ($cssSelector) {
      /** @var $context FeatureContext */
      return !is_null($context->getSession()->getPage()->find('css', $cssSelector));
    });
  }

  /**
   * @Then /^I click on "([^"]*)"$/
   */
  public function iClickOn($element)
  {
    $page = $this->getSession()->getPage();
    $findName = $page->find("css", $element);
    if (!$findName) {
      throw new Exception($element . " could not be found");
    } else {
      $findName->click();
    }
  }

  /**
   * Based on Behat's own example
   * @see http://docs.behat.org/en/v2.5/cookbook/using_spin_functions.html#adding-a-timeout
   * @param $lambda
   * @param int $wait
   * @throws \Exception
   */
  public function spin($lambda, $wait = 60)
  {
    $time = time();
    $stopTime = $time + $wait;
    while (time() < $stopTime)
    {
      try {
        if ($lambda($this)) {
          return;
        }
      } catch (\Exception $e) {
        // do nothing
      }

      usleep(250000);
    }

    throw new \Exception("Spin function timed out after {$wait} seconds");
  }

  /**
   * Set basic auth.
   * Example: https://gist.github.com/jhedstrom/5bc5192d6dacbf8cc459
   *
   * @BeforeScenario
   */
  public function before($event) {
    $driver = $this->getSession()->getDriver();
    if ($driver instanceof Selenium2Driver) {
      return;
    }
    $this->getSession()->setBasicAuth('admin','propeople');
  }

}
