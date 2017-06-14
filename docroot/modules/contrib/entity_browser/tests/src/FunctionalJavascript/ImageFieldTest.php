<?php

namespace Drupal\Tests\entity_browser\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

/**
 * Tests the image field widget.
 *
 * @group entity_browser
 */
class ImageFieldTest extends EntityBrowserJavascriptTestBase {

  /**
   * Created file entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $image;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    FieldStorageConfig::create([
      'field_name' => 'field_image',
      'type' => 'image',
      'entity_type' => 'node',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Images',
      'settings' => [
        'file_extensions' => 'jpg',
        'title_field' => TRUE,
      ],
    ])->save();

    file_unmanaged_copy(\Drupal::root() . '/core/misc/druplicon.png', 'public://example.jpg');
    $this->image = File::create([
      'uri' => 'public://example.jpg',
    ]);
    $this->image->save();
    // Register usage for this file to avoid validation erros when referencing
    // this file on node save.
    \Drupal::service('file.usage')->add($this->image, 'entity_browser', 'test', '1');

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('node.article.default');

    $form_display->setComponent('field_image', [
      'type' => 'entity_browser_file',
      'settings' => [
        'entity_browser' => 'test_entity_browser_iframe_view',
        'open' => TRUE,
        'field_widget_edit' => FALSE,
        'field_widget_remove' => TRUE,
        'selection_mode' => EntityBrowserElement::SELECTION_MODE_APPEND,
        'view_mode' => 'default',
        'preview_image_style' => 'thumbnail',
      ],
    ])->save();

    $display_config = [
      'width' => '650',
      'height' => '500',
      'link_text' => 'Select images',
    ];
    /** @var \Drupal\entity_browser\EntityBrowserInterface $browser */
    $browser = $this->container->get('entity_type.manager')
      ->getStorage('entity_browser')
      ->load('test_entity_browser_iframe_view');
    $browser->setDisplay('iframe');
    $browser->getDisplay()->setConfiguration($display_config);
    $browser->save();

    $account = $this->drupalCreateUser([
      'access test_entity_browser_iframe_view entity browser pages',
      'create article content',
      'edit own article content',
      'access content',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Tests basic usage for an image field.
   */
  public function testImageFieldUsage() {

    $this->drupalGet('node/add/article');
    $this->assertSession()->linkExists('Select images');
    $this->getSession()->getPage()->clickLink('Select images');
    $this->getSession()->switchToIFrame('entity_browser_iframe_test_entity_browser_iframe_view');
    $this->getSession()->getPage()->checkField('entity_browser_select[file:' . $this->image->id() . ']');
    $this->getSession()->getPage()->pressButton('Select entities');
    $this->getSession()->getPage()->pressButton('Use selected');
    $this->assertSession()->pageTextContains('example.jpg');
    // Switch back to the main page.
    $this->getSession()->switchToIFrame();
    $this->waitForAjaxToFinish();
    // Check if the image thumbnail exists.
    $this->assertSession()->elementExists('xpath', '//*[@data-drupal-selector="edit-field-image-current-' . $this->image->id() . '-display"]');
    // Test if the image filename is present.
    $this->assertSession()->pageTextContains('example.jpg');
    // Test specifying Alt and Title texts and saving the node.
    $alt_text = 'Test alt text.';
    $title_text = 'Test title text.';
    $this->getSession()->getPage()->fillField('field_image[current][1][meta][alt]', $alt_text);
    $this->getSession()->getPage()->fillField('field_image[current][1][meta][title]', $title_text);
    $this->getSession()->getPage()->fillField('title[0][value]', 'Node 1');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('Article Node 1 has been created.');
    $node = Node::load(1);
    $saved_alt = $node->get('field_image')[0]->alt;
    $this->assertEquals($saved_alt, $alt_text);
    $saved_title = $node->get('field_image')[0]->title;
    $this->assertEquals($saved_title, $title_text);
    // Test the Delete functionality.
    $this->drupalGet('node/1/edit');
    $this->assertSession()->buttonExists('Remove');
    $this->getSession()->getPage()->pressButton('Remove');
    $this->waitForAjaxToFinish();
    // Image filename should not be present.
    $this->assertSession()->pageTextNotContains('example.jpg');
    $this->assertSession()->linkExists('Select entities');
  }

}
