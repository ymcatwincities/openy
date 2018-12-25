<?php

namespace Drupal\Tests\panelizer\Functional;

use Drupal\Tests\content_translation\Functional\ContentTranslationTestBase;

/**
 * Test node translation handling in Panelizer.
 *
 * @group panelizer
 */
class PanelizerNodeTranslationsTest extends ContentTranslationTestBase {

  use PanelizerTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Core dependencies.
    'content_translation',
    'field',
    'field_ui',
    'language',
    'layout_discovery',
    'node',

    // Contrib dependencies.
    'ctools',
    'ctools_block',
    'panels',
    'panels_ipe',

    // This module.
    'panelizer',
    'panelizer_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->loginUser1();
  }

  /**
   * The entity type being tested.
   *
   * @var string
   */
  protected $entityTypeId = 'node';

  /**
   * The bundle being tested.
   *
   * @var string
   */
  protected $bundle = 'page';

  /**
   * Tests the admin interface to set a default layout for a bundle.
   */
  public function _testWizardUI() {
    $this->panelize($this->bundle, NULL, ['panelizer[custom]' => TRUE]);

    // Enter the wizard.
    $this->drupalGet("admin/structure/panelizer/edit/{$this->entityTypeId}__{$this->bundle}__default__default");
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
    // @todo The index will have to change if the install profile is changed.
    $this->clickLink('Content', 1);
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
    $this->drupalGet("admin/structure/panelizer/edit/{$this->entityTypeId}__{$this->bundle}__default__default/content");
    $this->assertResponse(200);
    $this->clickLink('Add new block');
    $this->clickLink('Body');
    $edit = [
      'region' => 'content',
    ];
    $this->drupalPostForm(NULL, $edit, t('Add block'));
    $this->assertResponse(200);
    $this->assertText("entity_field:{$this->entityTypeId}:body", 'The body block was added successfully.');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertResponse(200);
    $this->clickLink('Content', 1);
    $this->assertText("entity_field:{$this->entityTypeId}:body", 'The body block was saved successfully.');

    // Check that the Manage Display tab changed now that Panelizer is set up.
    // Also, the field display table should be hidden.
    $this->assertNoRaw('<div id="field-display-overview-wrapper">');

    // Disable Panelizer for the default display mode. This should bring back
    // the field overview table at Manage Display and not display the link to
    // edit the default Panelizer layout.
    $this->unpanelize($this->bundle);
    $this->assertNoLinkByHref("admin/structure/panelizer/edit/{$this->entityTypeId}__{$this->bundle}__default");
    $this->assertRaw('<div id="field-display-overview-wrapper">');
  }

  /**
   * Tests rendering a node with Panelizer default.
   */
  public function testPanelizerDefault() {
    $this->panelize($this->bundle, NULL, ['panelizer[custom]' => TRUE]);
    /** @var \Drupal\panelizer\PanelizerInterface $panelizer */
    $panelizer = $this->container->get('panelizer');
    $displays = $panelizer->getDefaultPanelsDisplays($this->entityTypeId, $this->bundle, 'default');
    $display = $displays['default'];
    $display->addBlock([
      'id' => 'panelizer_test',
      'label' => 'Panelizer test',
      'provider' => 'block_content',
      'region' => 'content',
    ]);
    $panelizer->setDefaultPanelsDisplay('default', $this->entityTypeId, $this->bundle, 'default', $display);

    // Create a node, and check that the IPE is visible on it.
    $node = $this->drupalCreateNode([
      'type' => $this->bundle,
      'langcode' => [
        [
          'value' => 'en',
        ],
      ],
    ]);
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

    // Load the translation page.
    $this->clickLink('Translate');
    $this->assertText('English (Original language)');
    $this->assertText('Published');
    $this->assertText('Not translated');
  }

  // @todo Confirm that the different languages of a translated node are loaded properly when using a default display.
  // @todo Decide what should happen if a node is translated and has a customized display.
  // @todo Confirm loading a referenced block uses the block's correct language.
}
