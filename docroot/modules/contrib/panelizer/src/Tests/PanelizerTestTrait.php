<?php

namespace Drupal\panelizer\Tests;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Contains helper methods for writing functional tests of Panelizer.
 */
trait PanelizerTestTrait {

  /**
   * Panelizes a node type's default view display.
   *
   * @param string $node_type
   *   The node type ID.
   * @param string $display
   *   (optional) The view display to panelize.
   * @param array $values
   *   (optional) Additional form values.
   */
  protected function panelize($node_type, $display = NULL, array $values = []) {
    $this->drupalGet("admin/structure/types/manage/{$node_type}/display/{$display}");
    $this->assertResponse(200);

    $edit = [
      'panelizer[enable]' => TRUE,
    ] + $values;
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);

    EntityFormDisplay::load('node.' . $node_type . '.default')
      ->setComponent('panelizer', [
        'type' => 'panelizer',
      ])
      ->save();
  }

  /**
   * Unpanelizes a node type's default view display.
   *
   * Panelizer is disabled for the display, but its configuration is retained.
   *
   * @param string $node_type
   *   The node type ID.
   * @param string $display
   *   (optional) The view display to unpanelize.
   * @param array $values
   *   (optional) Additional form values.
   */
  protected function unpanelize($node_type, $display = NULL, array $values = []) {
    $this->drupalGet("admin/structure/types/manage/{$node_type}/display/{$display}");
    $this->assertResponse(200);

    $edit = [
      'panelizer[enable]' => FALSE,
    ] + $values;
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);

    EntityFormDisplay::load('node.' . $node_type . '.default')
      ->removeComponent('panelizer')
      ->save();
  }

  protected function addPanelizerDefault($node_type, $display = 'default') {
    $label = $this->getRandomGenerator()->word(16);
    $id = strtolower($label);
    $default_id = "node__{$node_type}__{$display}__{$id}";
    $options = [
      'query' => [
        'js' => 'nojs',
      ],
    ];

    $this->drupalGet("admin/structure/types/manage/{$node_type}/display");
    $this->assertResponse(200);
    $this->clickLink('Add a new Panelizer default display');

    // Step 1: Enter the default's label and ID.
    $edit = [
      'id' => $id,
      'label' => $label,
    ];
    $this->drupalPostForm(NULL, $edit, t('Next'));
    $this->assertResponse(200);

    // Step 2: Define contexts.
    $this->assertUrl("admin/structure/panelizer/add/{$default_id}/contexts", $options);
    $this->drupalPostForm(NULL, [], t('Next'));
    $this->assertResponse(200);

    // Step 3: Select layout.
    $this->assertUrl("admin/structure/panelizer/add/{$default_id}/layout", $options);
    $this->drupalPostForm(NULL, [], t('Next'));
    $this->assertResponse(200);

    // Step 4: Select content.
    $this->assertUrl("admin/structure/panelizer/add/{$default_id}/content", $options);
    $this->drupalPostForm(NULL, [], t('Finish'));
    $this->assertResponse(200);

    return $id;
  }

  /**
   * Deletes a Panelizer default.
   *
   * @param string $node_type
   *   The node type ID.
   * @param string $display
   *   (optional) The view display ID.
   * @param string $id
   *   (optional) The default ID.
   */
  protected function deletePanelizerDefault($node_type, $display = 'default', $id = 'default') {
    $this->drupalGet("admin/structure/panelizer/delete/node__{$node_type}__{$display}__{$id}");
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, [], t('Confirm'));
    $this->assertResponse(200);
  }

  /**
   * Asserts that a Panelizer default exists.
   *
   * @param string $node_type
   *   The node type ID.
   * @param string $display
   *   (optional) The view display ID.
   * @param string $id
   *   (optional) The default ID.
   */
  protected function assertDefaultExists($node_type, $display = 'default', $id = 'default') {
    $settings = EntityViewDisplay::load("node.{$node_type}.{$display}")
      ->getThirdPartySettings('panelizer');

    $display_exists = isset($settings['displays'][$id]);
    $this->assertTrue($display_exists);
  }

  /**
   * Asserts that a Panelizer default does not exist.
   *
   * @param string $node_type
   *   The node type ID.
   * @param string $display
   *   The view display ID.
   * @param string $id
   *   The default ID.
   */
  protected function assertDefaultNotExists($node_type, $display = 'default', $id = 'default') {
    $settings = EntityViewDisplay::load("node.{$node_type}.{$display}")
      ->getThirdPartySettings('panelizer');

    $display_exists = isset($settings['displays'][$id]);
    $this->assertFalse($display_exists);
  }

}
