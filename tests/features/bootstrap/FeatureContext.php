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
   * Creates a term "Category One" in Category taxonomy.
   *
   * @Given /^I create a category term$/
   */
  public function iCreateCategoryTerm() {
    $this->getSession()->visit($this->locatePath('/admin/structure/taxonomy/manage/blog_category/add'));
    $element = $this->getSession()->getPage();
    $element->fillField('Name', 'Category One');
    $element->findButton('Save')->click();
  }

}
