<?php

namespace Drupal\Tests\entity_browser_entity_form\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Class for Entity browser entity form Javascript functional tests.
 *
 * @group entity_browser_entity_form
 */
class EntityFormWidgetTest extends JavascriptTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_browser_entity_form_test',
    'ctools',
    'views',
    'block',
    'node',
    'file',
    'image',
    'field_ui',
    'views_ui',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'foo', 'name' => 'Foo']);

    FieldStorageConfig::create([
      'field_name' => 'field_reference',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'node',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_reference',
      'entity_type' => 'node',
      'bundle' => 'foo',
      'label' => 'Reference',
      'settings' => [],
    ])->save();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('node.foo.default');

    $form_display->setComponent('field_reference', [
      'type' => 'entity_browser_entity_reference',
      'settings' => [
        'entity_browser' => 'entity_browser_test_entity_form',
        'field_widget_display' => 'label',
        'open' => TRUE,
      ],
    ])->save();

    $account = $this->drupalCreateUser([
      'access entity_browser_test_entity_form entity browser pages',
      'create foo content',
      'access content',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Test if save button is appears on form.
   */
  public function testEntityForm() {
    $this->drupalGet('node/add/foo');
    $this->getSession()->getPage()->clickLink('Select entities');
    $this->getSession()->switchToIFrame('entity_browser_iframe_entity_browser_test_entity_form');
    $this->assertSession()->buttonExists('Save entity');
    /** @var \Drupal\entity_browser\EntityBrowserInterface $browser */
    $browser = $this->container->get('entity_type.manager')
      ->getStorage('entity_browser')
      ->load('entity_browser_test_entity_form');
    $entity_form_widget = $browser->getWidget('9c6ee4c0-4642-4203-b4bd-ec0bad068ad3');
    // Update submit text in widget settings.
    $entity_form_widget->setConfiguration([
      'settings' => [
        'entity_type' => 'node',
        'bundle' => 'article',
        'form_mode' => 'default',
        'submit_text' => 'Save node',
      ],
      'uuid' => '9c6ee4c0-4642-4203-b4bd-ec0bad068ad3',
      'weight' => 2,
      'label' => 'entity_form',
      'id' => 'entity_form',
    ]);
    $browser->save();
    $this->drupalGet('node/add/foo');
    $this->getSession()->getPage()->clickLink('Select entities');
    $this->getSession()->switchToIFrame('entity_browser_iframe_entity_browser_test_entity_form');
    // Assert changes in widget configuration is respected.
    $this->assertSession()->buttonNotExists('Save entity');
    $this->assertSession()->buttonExists('Save node');
  }

}
