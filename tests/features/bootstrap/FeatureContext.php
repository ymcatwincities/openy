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
        $value = \Drupal::service('path.alias_manager')->getAliasByPath($node->url());
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
    $this->assertSession()->fieldValueEquals(str_replace('\\"', '"', $field), str_replace('\\"', '"', $path));
  }

}
