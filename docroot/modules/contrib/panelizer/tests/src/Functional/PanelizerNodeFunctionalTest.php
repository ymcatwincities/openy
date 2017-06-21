<?php

namespace Drupal\Tests\panelizer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Basic functional tests of using Panelizer with nodes.
 *
 * @group panelizer
 */
class PanelizerNodeFunctionalTest extends BrowserTestBase {

  use PanelizerTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Modules for core functionality.
    'node',
    'field',
    'field_ui',
    'user',

    // Core dependencies.
    'layout_discovery',

    // Contrib dependencies.
    'ctools',
    'panels',
    'panels_ipe',

    // This here module.
    'panelizer',
    'panelizer_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setupContentType();
    $this->loginUser1();
    $this->panelize('page', NULL, ['panelizer[custom]' => TRUE]);
  }

  /**
   * Tests the admin interface to set a default layout for a bundle.
   */
  public function testWizardUI() {
    // Enter the wizard.
    $this->drupalGet('admin/structure/panelizer/edit/node__page__default__default');
    $this->assertResponse(200);
    $this->assertText('Wizard Information');
    $this->assertField('edit-label');

    // Contexts step.
    $this->clickLink('Contexts');
    $this->assertText('@panelizer.entity_context:entity', 'The current entity context is present.');

    // Layout selection step.
    $this->clickLink('Layout');
    $this->assertSession()->buttonExists('edit-update-layout');

    // Content step. Add the Node block to the top region.
    $this->clickLink('Content');
    $this->clickLink('Add new block');
    $this->clickLink('Title');
    $edit = [
      'region' => 'content',
    ];
    $this->drupalPostForm(NULL, $edit, t('Add block'));
    $this->assertResponse(200);

    // Finish the wizard.
    $this->drupalPostForm(NULL, [], t('Update and save'));
    $this->assertResponse(200);
    // Confirm this returned to the main wizard page.
    $this->assertText('Wizard Information');
    $this->assertField('edit-label');

    // Return to the Manage Display page, which is where the Cancel button
    // currently sends you. That's a UX WTF and should be fixed...
    $this->drupalPostForm(NULL, [], t('Cancel'));
    $this->assertResponse(200);

    // Confirm the page is back to the content type settings page.
    $this->assertFieldChecked('edit-panelizer-custom');

    // Now change and save the general setting.
    $edit = [
      'panelizer[custom]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertNoFieldChecked('edit-panelizer-custom');

    // Add another block at the Content step and then save changes.
    $this->drupalGet('admin/structure/panelizer/edit/node__page__default__default/content');
    $this->assertResponse(200);
    $this->clickLink('Add new block');
    $this->clickLink('Body');
    $edit = [
      'region' => 'content',
    ];
    $this->drupalPostForm(NULL, $edit, t('Add block'));
    $this->assertResponse(200);
    $this->assertText('entity_field:node:body', 'The body block was added successfully.');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertResponse(200);
    $this->clickLink('Content');
    $this->assertText('entity_field:node:body', 'The body block was saved successfully.');

    // Check that the Manage Display tab changed now that Panelizer is set up.
    // Also, the field display table should be hidden.
    $this->assertNoRaw('<div id="field-display-overview-wrapper">');

    // Disable Panelizer for the default display mode. This should bring back
    // the field overview table at Manage Display and not display the link to
    // edit the default Panelizer layout.
    $this->unpanelize('page');
    $this->assertNoLinkByHref('admin/structure/panelizer/edit/node__page__default');
    $this->assertRaw('<div id="field-display-overview-wrapper">');
  }

  /**
   * Tests rendering a node with Panelizer default.
   */
  public function testPanelizerDefault() {
    /** @var \Drupal\panelizer\PanelizerInterface $panelizer */
    $panelizer = $this->container->get('panelizer');
    $displays = $panelizer->getDefaultPanelsDisplays('node', 'page', 'default');
    $display = $displays['default'];
    $display->addBlock([
      'id' => 'panelizer_test',
      'label' => 'Panelizer test',
      'provider' => 'block_content',
      'region' => 'content',
    ]);
    $panelizer->setDefaultPanelsDisplay('default', 'node', 'page', 'default', $display);

    // Create a node, and check that the IPE is visible on it.
    $node = $this->drupalCreateNode(['type' => 'page']);
    $out = $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);
    $this->verbose($out);
    $elements = $this->xpath('//*[@id="panels-ipe-content"]');
    if (is_array($elements)) {
      $this->assertIdentical(count($elements), 1);
    }
    else {
      $this->fail('Could not parse page content.');
    }

    // Check that the block we added is visible.
    $this->assertText('Panelizer test');
    $this->assertText('Abracadabra');
  }

}
