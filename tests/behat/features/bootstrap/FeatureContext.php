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
   * @Given I wait AJAX
   */
  public function waitAjax()
  {
    // @TODO: workaround should be replaced.
    sleep(5);
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

}
