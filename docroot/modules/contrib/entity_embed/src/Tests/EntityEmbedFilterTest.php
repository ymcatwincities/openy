<?php

/**
 * @file
 * Contains \Drupal\entity_embed\Tests\EntityEmbedFilterTest.
 */

namespace Drupal\entity_embed\Tests;

/**
 * Tests the entity_embed filter.
 *
 * @group entity_embed
 */
class EntityEmbedFilterTest extends EntityEmbedTestBase {

  /**
   * Tests the entity_embed filter.
   *
   * Ensures that entities are getting rendered when correct data attributes
   * are passed. Also tests situations when embed fails.
   */
  public function testFilter() {
    // Tests entity embed using entity ID and view mode.
    $content = '<drupal-entity data-entity-type="node" data-entity-id="' . $this->node->id() . '" data-view-mode="teaser">This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test entity embed with entity-id and view-mode';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertNoRaw('<drupal-entity data-entity-type="node" data-entity');
    $this->assertText($this->node->body->value, 'Embedded node exists in page');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appear in the output when embed is successful.');

    // Tests entity embed using entity UUID and view mode.
    $content = '<drupal-entity data-entity-type="node" data-entity-uuid="' . $this->node->uuid() . '" data-view-mode="teaser">This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test entity embed with entity-uuid and view-mode';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertNoRaw('<drupal-entity data-entity-type="node" data-entity');
    $this->assertText($this->node->body->value, 'Embedded node exists in page.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appear in the output when embed is successful.');

    // Ensure that placeholder is not replaced when embed is unsuccessful.
    $content = '<drupal-entity data-entity-type="node" data-entity-id="InvalidID" data-view-mode="teaser">This placeholder should be rendered since specified entity does not exists.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test that placeholder is retained when specified entity does not exists';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertNoRaw('<drupal-entity data-entity-type="node" data-entity');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appear in the output when embed is unsuccessful.');

    // Ensure that UUID is preferred over ID when both attributes are present.
    $sample_node = $this->drupalCreateNode();
    $content = '<drupal-entity data-entity-type="node" data-entity-id="' . $sample_node->id() . '" data-entity-uuid="' . $this->node->uuid() . '" data-view-mode="teaser">This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test that entity-uuid is preferred over entity-id when both attributes are present';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertNoRaw('<drupal-entity data-entity-type="node" data-entity');
    $this->assertText($this->node->body->value, 'Entity specifed with UUID exists in the page.');
    $this->assertNoText($sample_node->body->value, 'Entity specifed with ID does not exists in the page.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appear in the output when embed is successful.');

    // Test deprecated 'default' Entity Embed Display plugin.
    $content = '<drupal-entity data-entity-type="node" data-entity-uuid="' . $this->node->uuid() . '" data-entity-embed-display="default" data-entity-embed-settings=\'{"view_mode":"teaser"}\'>This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test entity embed with entity-embed-display and data-entity-embed-settings';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertText($this->node->body->value, 'Embedded node exists in page.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appear in the output when embed is successful.');

    // Ensure that Entity Embed Display plugin is preferred over view mode when
    // both attributes are present.
    $content = '<drupal-entity data-entity-type="node" data-entity-uuid="' . $this->node->uuid() . '" data-entity-embed-display="default" data-entity-embed-settings=\'{"view_mode":"teaser"}\' data-view-mode="some-invalid-view-mode">This placeholder should not be rendered.</drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'Test entity embed with entity-embed-display and data-entity-embed-settings';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertText($this->node->body->value, 'Embedded node exists in page with the view mode specified by entity-embed-settings.');
    $this->assertNoText(strip_tags($content), 'Placeholder does not appear in the output when embed is successful.');

    // Test that tag of container element is not replaced when it's not
    // <drupal-entity>.
    $content = '<not-drupal-entity data-entity-type="node" data-entity-id="' . $this->node->id() . '" data-view-mode="teaser">this placeholder should not be rendered.</not-drupal-entity>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'test entity embed with entity-id and view-mode';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalget('node/' . $node->id());
    $this->assertNoText($this->node->body->value, 'embedded node exists in page');
    $this->assertRaw('</not-drupal-entity>');
    $content = '<div data-entity-type="node" data-entity-id="' . $this->node->id() . '" data-view-mode="teaser">this placeholder should not be rendered.</div>';
    $settings = array();
    $settings['type'] = 'page';
    $settings['title'] = 'test entity embed with entity-id and view-mode';
    $settings['body'] = array(array('value' => $content, 'format' => 'custom_format'));
    $node = $this->drupalCreateNode($settings);
    $this->drupalget('node/' . $node->id());
    $this->assertNoText($this->node->body->value, 'embedded node exists in page');
    $this->assertRaw('<div data-entity-type="node" data-entity-id');
  }

}
