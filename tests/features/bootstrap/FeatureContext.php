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
use Behat\Mink\Exception\ResponseTextException;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * Storage Engine, a stdClass object to store values by key.
   *
   * @var \stdClass
   */
  private $storageEngine;

  /**
   * Valid $node_key values for test validation.
   *
   * @var array
   */
  private $nodeKeys;

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
    $this->storageEngine = new stdClass();
    $this->nodeKeys = [
      'reference_fill',
      'system_url',
      'alias_url',
      'edit_url',
      'path',
    ];
  }

  /**
   * Throw exception if invalid $storage_key is provided.
   *
   * @param string $storage_key
   *   A storage key string.
   *
   * @throws \Exception
   */
  public function validateStorageEngineKey($storage_key) {
    if (!property_exists($this->storageEngine, $storage_key)) {
      $msg = 'Invalid $storage_key value "' . $storage_key . '" used.';
      throw new \Exception($msg);
    }
  }

  /**
   * Throw exception if invalid $node_key is provided.
   *
   * @param string $node_key
   *   A node key string.
   *
   * @throws \Exception
   */
  public function validateNodeKey($node_key) {
    if (!in_array($node_key, $this->nodeKeys)) {
      $msg = 'Invalid $node_key value used.';
      throw new \Exception($msg);
    }
  }

  /**
   * Get stored Node values based on node key and storage key.
   *
   * @param string $node_key
   *   A node key string.
   * @param string $storage_key
   *   A storage key string.
   *
   * @return string
   *   URL based on the Node key and storage key.
   *
   * @throws \Exception
   */
  public function getNodeValueFromStorageEngine($node_key, $storage_key) {
    $this->validateNodeKey($node_key);
    $this->validateStorageEngineKey($storage_key);
    $value = NULL;
    /* @var \Drupal\node\Entity\Node $node */
    $node = $this->storageEngine->{$storage_key};
    switch ($node_key) {
      case 'reference_fill':
        $value = $node->getTitle() . ' (' . $node->id() . ')';
        break;

      case 'system_url':
        $value = $node->url();
        break;

      case 'alias_url':
        $value = \Drupal::service('path.alias_manager')
          ->getAliasByPath($node->url());
        if (empty($value)) {
          return $node->url();
        }
        break;

      case 'path':
        $value = $node->url();
        break;

      case 'edit_url':
        $value = $node->url('edit-form');
        break;
    }

    if (is_null($value)) {
      $msg = 'Invalid path returned from getNodeValueFromStorageEngine()';
      throw new \Exception($msg);
    }

    return $value;
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
    $element->findButton('Save')->click();
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
   * Store the latest edited node in $storageEngine at the storage key given.
   *
   * @Given /^I store the Node as "(?P<storage_key>[^"]*)"$/
   */
  public function iStoreTheNodeAs($storage_key) {
    $node = node_get_recent(1);
    // Reset the array of node since it has only one object.
    $this->storageEngine->{$storage_key} = reset($node);
    $this->validateStorageEngineKey($storage_key);
  }

  /**
   * Fill in the field provided with the Node key value from stored key Node.
   *
   * @Given /^I fill in "(?P<field>[^"]*)" with stored Node "([^"]*)" from "(?P<storage_key>[^"]*)"$/
   */
  public function iFillInWithStoredNodeFrom($field, $node_key, $storage_key) {

    $value = $this->getNodeValueFromStorageEngine($node_key, $storage_key);

    if (!empty($field) && !empty($value)) {
      $this->getSession()->getPage()->fillField($field, $value);
    }
    else {
      $msg = 'Unable to fill ' . $field . ' from stored node data in "' . $storage_key . '"';
      throw new \Exception($msg);
    }
  }

  /**
   * Opens page based on the stored Node and node key value.
   *
   * @Given /^I go to stored Node "([^"]*)" from "(?P<storage_key>[^"]*)"$/
   */
  public function iGoToStoredNodeFrom($node_key, $storage_key) {
    $path = $this->getNodeValueFromStorageEngine($node_key, $storage_key);
    $this->visitPath($path);
  }

  /**
   * Validate the field provided has the Node key value from stored key Node.
   *
   * @Given /^The "(?P<field>[^"]*)" field should contain stored Node "([^"]*)" from "(?P<storage_key>[^"]*)"$/
   */
  public function theFieldShouldContainStoredNodeFrom($field, $node_key, $storage_key) {
    $path = $this->getNodeValueFromStorageEngine($node_key, $storage_key);
    $this->assertSession()
      ->fieldValueEquals(str_replace('\\"', '"', $field), str_replace('\\"', '"', $path));
  }

  /**
   * Clicks on element by css or xpath locator.
   *
   * @Given I click :selector element
   * @Given I click :selector in :area
   * @Given I click :selector :locator_type element
   */
  public function clickElement($selector, $locator_type = 'css') {
    $element = $this->getSession()->getPage()->find($locator_type, $selector);
    if (empty($element)) {
      $msg = 'There is no element with selector ' . $locator_type . ': "' . $selector . '"';
      throw new Exception($msg);
    }
    try {
      $element->focus();
    } catch (Exception $e) {
      // No focus on some drivers ie mink.
    }
    $element->click();
  }

  /**
   * @Given /^The current URL is "(?P<url>[^"]*)"$/
   */
  public function theCurrentURLIs($url) {
    $current_url = $this->getSession()->getCurrentUrl();
    if (!$current_url == $url) {
      $msg = 'URL "' . $url . '" does not match the current URL "' . $current_url . '"';
      throw new \Exception($msg);
    }
  }

  /**
   * @Given Element :element has text :text
   */
  public function elementHasText($element, $text) {
    $element_obj = $this->getSession()->getPage()->find('css', $element);

    // Element not found.
    if (is_null($element_obj)) {
      $msg = 'Element ' . $element . ' not found.';
      throw new \Exception($msg);
    }

    // Find the text within the region
    $element_text = $element_obj->getText();
    if (strpos($element_text, $text) === FALSE) {
      throw new \Exception(sprintf("The text '%s' was not found in the element '%s' on the page %s", $text, $element, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * @Then I should see text :text in XML
   */
  public function iShouldSeeTextMatchingInXml($text)
  {
    $xml = $this->getSession()->getDriver()->getContent();
    $message = sprintf('The text %s was not found anywhere in the XML.', $text);

    if (strpos($xml, $text) === FALSE) {
      throw new ResponseTextException($message, $this->getSession()->getDriver());
    }
  }

  /**
   * @Given I fill in :arg1 with node path of :arg2
   */
  public function iFillInWithNodePathOf($field, $title)
  {

    $value = $this->getNodeIdByTitle($title);

    if (!empty($field) && !empty($value)) {
      $this->getSession()->getPage()->fillField($field, '/node/' . $value);
    }
    else {
      $msg = 'Unable to fill ' . $field . ' with node path of "' . $title . '"';
      throw new \Exception($msg);
    }
  }

  /**
   * @Then the :arg1 field should contain node path of :arg2
   */
  public function theFieldShouldContainNodePathOf($field, $title)
  {
    $path = '/node/' . $this->getNodeIdByTitle($title);
    $this->assertSession()
      ->fieldValueEquals(str_replace('\\"', '"', $field), str_replace('\\"', '"', $path));
  }

  /**
   * Get node id by its title.
   */
  protected function getNodeIdByTitle($title) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('title', $title);
    $nids = $query->execute();
    return reset($nids);
  }

  /**
   * @Given /^I select this "([^"]*)" from "([^"]*)"$/
   */
  public function iSelectThisFrom($time, $field) {
    $value = date('n/d/Y', strtotime($time));
    // Mimic \Behat\MinkExtension\Context\MinkContext::fillField.
    $field = $this->fixStepArgument($field);
    $value = $this->fixStepArgument($value);
    $this->getSession()->getPage()->fillField($field, $value);
  }

  /**
   * @Given /^I print page$/
   */
  public function iPrintPage() {
    print $this->getSession()->getPage()->getHtml();
  }

  /**
   * Returns fixed step argument (with \\" replaced back to ")
   *
   * @param string $argument
   *
   * @return string
   */
  protected function fixStepArgument($argument) {
    return str_replace('\\"', '"', $argument);
  }

}
