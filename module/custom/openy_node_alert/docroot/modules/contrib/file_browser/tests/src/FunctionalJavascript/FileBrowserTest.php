<?php

namespace Drupal\Tests\file_browser\FunctionalJavascript;

use Drupal\Tests\entity_browser\FunctionalJavascript\EntityBrowserJavascriptTestBase;

/**
 * Tests the file_browser module.
 *
 * @group entity_browser
 */
class FileBrowserTest extends EntityBrowserJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'file_browser',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $userPermissions = [
    'access browse_files entity browser pages',
    'create article content',
    'access content',
    'dropzone upload files',
  ];

  /**
   * Tests that selecting files in the view works.
   */
  public function testFileBrowserView() {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('node.article.default');

    $form_display->setComponent('field_reference', [
      'type' => 'entity_browser_entity_reference',
      'settings' => [
        'entity_browser' => 'browse_files',
        'field_widget_display' => 'label',
        'open' => TRUE,
      ],
    ])->save();

    // Create a file.
    $image = $this->createFile('llama', 'jpg');

    $this->drupalGet('node/add/article');

    // Open the browser and select a file.
    $this->getSession()->switchToIFrame('entity_browser_iframe_browse_files');
    $this->getSession()->getPage()->clickLink('Files listing');
    $this->clickViewEntity('file:' . $image->id());
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->pressButton('Use selected');

    // Switch back to the main page and assert that the file was selected.
    $this->getSession()->switchToIFrame();
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextContains('llama.jpg');
  }

  /**
   * Click on entity in view to be selected.
   *
   * @param string $entityId
   *   Entity ID that will be selected. Format: "file:1".
   */
  protected function clickViewEntity($entityId) {
    $xpathViewRow = '//*[./*[contains(@class, "views-field-entity-browser-select") and .//input[@name="entity_browser_select[' . $entityId . ']"]]]';
    $this->clickXpathSelector($xpathViewRow, FALSE);
  }

}
