<?php

/**
 * @file
 * FeatureContent for OpenY project.
 */

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

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
   * Create a node of Branch CT with name "Test Branch".
   *
   * @Given /^I create a branch$/
   */
  public function iCreateBranch() {
    $this->getSession()->visit($this->locatePath('/node/add/branch'));
    $element = $this->getSession()->getPage();
    $element->fillField('Title', 'Test Branch');
    $element->fillField('Street address', 'Main road 10');
    $element->fillField('City', 'Seattle');
    $element->fillField('State', 'WA');
    $element->fillField('Zip code', '98101');
    $element->fillField('Latitude', '47.293433');
    $element->fillField('Longitude', '-122.238717');
    $element->fillField('Phone', '+1234567890');
    $element->findButton('Save and publish')->click();
  }

  /**
   * Creates a term in the respective taxonomy.
   *
   * @Given /^I create a "([^"]*)" term in the "([^"]*)" taxonomy$/
   */
  public function iCreateTaxonomyTerm($term, $taxonomy_name) {
    $taxonomy = strtolower(str_replace(' ', '_', $taxonomy_name));
    $path = '/admin/structure/taxonomy/manage/' . $taxonomy . '/add';
    $this->getSession()->visit($this->locatePath($path));
    $element = $this->getSession()->getPage();
    $element->fillField('Name', $term);
    $element->findButton('Save')->click();
  }

  /**
   * Creates a term Color taxonomy. And specify HEX value.
   *
   * @Given /^I create a color term$/
   */
  public function iCreateColorTerm() {
    $path = '/admin/structure/taxonomy/manage/color/add';
    $this->getSession()->visit($this->locatePath($path));
    $element = $this->getSession()->getPage();
    $element->fillField('Name', 'Magenta');
    $element->fillField('Color', 'cc4ecc');
    $element->findButton('Save')->click();
  }

  /**
   * Creates a menu item with specified name in the specified menu.
   *
   * @Given /^I create an item "([^"]*)" in the "([^"]*)" menu$/
   */
  public function iCreateItemInTheMenu($menu_item, $menu_name) {
    $path = '/admin/structure/menu/manage/' . $menu_name . '/add';
    $this->getSession()->visit($this->locatePath($path));
    $element = $this->getSession()->getPage();
    $element->fillField('Menu link title', $menu_item);
    $element->fillField('Link', 'http://example.com');
    $element->checkField("Show as expanded");
    $element->findButton('Save')->click();
  }

  /**
   * Quickly adding existing media to the field.
   *
   * Supported values have format like "media:1".
   *
   * @Given /^I fill media field "([^"]*)" with "([^"]*)"$/
   */
  public function iFillMediaFieldWith($field, $value) {
    $this->getSession()->getPage()->find('css',
        'input[id="' . $field . '"]')->setValue($value);
  }

  /**
   * Wait some amount of seconds.
   *
   * @param int $seconds
   *   Amount of seconds when nothing to happens.
   *
   * @Given /^(?:|I )wait (\d+) seconds$/
   */
  public function waitSeconds($seconds) {
    sleep($seconds);
  }

  /**
   * Clicks on element by css or xpath locator.
   *
   * @When I click :selector element
   * @When I click :selector in :area
   * @When I click :selector :locator_type element
   */
  public function clickElement($selector,$locator_type='css') {
    $page = $this->getSession()->getPage();
    $element = $page->find($locator_type, $selector);
    if (empty($element)) {
      throw new Exception('There is no element with selector ' . $locator_type . ': "' . $selector . '"');
    }
    $element->focus();
    $element->click();
  }

  /**
   * @param string $name
   *   An iframe name (null for switching back).
   *
   * @Given /^(?:|I )switch to an iframe "([^"]*)"$/
   * @Given /^(?:|I )switch back from an iframe$/
   */
  public function iSwitchToAnIframe($name = null)
  {
    $this->getSession()->switchToIFrame($name);
  }

}
