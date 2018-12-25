<?php

/**
 * @file
 * Contains \Drupal\page_manager\Tests\PageManagerTranslationIntegrationTest.
 */

namespace Drupal\page_manager\Tests;

use Drupal\content_translation\Tests\ContentTranslationTestBase;
use Drupal\page_manager\Entity\PageVariant;

/**
 * Tests that overriding the entity page does not affect content translation.
 *
 * @group page_manager
 */
class PageManagerTranslationIntegrationTest extends ContentTranslationTestBase {

  use PageTestHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'page_manager', 'node', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'node';

  /**
   * {@inheritdoc}
   */
  protected $bundle = 'article';

  /**
   * {@inheritdoc}
   */
  protected function setupBundle() {
    parent::setupBundle();
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), ['administer pages', 'administer pages']);
  }

  /**
   * Tests that overriding the node page does not prevent translation.
   */
  public function testNode() {
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');

    $node = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);
    $this->assertText($node->label());
    $this->clickLink('Translate');
    $this->assertResponse(200);

    // Create a new variant.
    $http_status_variant = PageVariant::create([
      'variant' => 'http_status_code',
      'label' => 'HTTP status code',
      'id' => 'http_status_code',
      'page' => 'node_view',
    ]);
    $http_status_variant->getVariantPlugin()->setConfiguration(['status_code' => 200]);
    $http_status_variant->save();
    $this->triggerRouterRebuild();

    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);
    $this->clickLink('Translate');
    $this->assertResponse(200);
  }

}
