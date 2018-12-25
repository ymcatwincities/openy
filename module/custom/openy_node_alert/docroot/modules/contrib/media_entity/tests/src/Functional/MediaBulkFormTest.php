<?php

namespace Drupal\Tests\media_entity\Functional;

use Drupal\media_entity\Entity\Media;
use Drupal\views\Views;

/**
 * Tests a media bulk form.
 *
 * @group media_entity
 */
class MediaBulkFormTest extends MediaEntityFunctionalTestBase {

  /**
   * Modules to be enabled.
   *
   * @var array
   */
  public static $modules = ['media_entity_test_views'];

  /**
   * The test media bundle.
   *
   * @var \Drupal\media_entity\MediaBundleInterface
   */
  protected $testBundle;

  /**
   * The test media entities.
   *
   * @var \Drupal\media_entity\MediaInterface[]
   */
  protected $mediaEntities;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->testBundle = $this->drupalCreateMediaBundle();

    // Create some test media entities.
    $this->mediaEntities = [];
    for ($i = 1; $i <= 5; $i++) {
      $media = Media::create([
        'bundle' => $this->testBundle->id(),
      ]);
      $media->save();
      $this->mediaEntities[] = $media;
    }

  }

  /**
   * Tests the media bulk form.
   */
  public function testBulkForm() {

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Check that all created entities are present in the test view.
    $view = Views::getView('test_media_entity_bulk_form');
    $view->execute();
    $this->assertEquals($view->total_rows, 5);

    // Check the operations are accessible to the logged in user.
    $this->drupalGet('test-media-entity-bulk-form');
    // Current available actions: Delete, Save, Publish, Unpublish.
    $available_actions = [
      'media_delete_action',
      'media_publish_action',
      'media_save_action',
      'media_unpublish_action',
    ];
    foreach ($available_actions as $action_name) {
      $assert_session->optionExists('action', $action_name);
    }

    // Test unpublishing in bulk.
    $page->checkField('media_bulk_form[0]');
    $page->checkField('media_bulk_form[1]');
    $page->checkField('media_bulk_form[2]');
    $page->selectFieldOption('action', 'media_unpublish_action');
    $page->pressButton('Apply to selected items');
    $assert_session->pageTextContains('Unpublish media was applied to 3 items');
    for ($i = 1; $i <= 3; $i++) {
      $this->assertFalse($this->storage->loadUnchanged($i)->isPublished(), 'The unpublish action failed in some of the media entities.');
    }

    // Test publishing in bulk.
    $page->checkField('media_bulk_form[0]');
    $page->checkField('media_bulk_form[1]');
    $page->selectFieldOption('action', 'media_publish_action');
    $page->pressButton('Apply to selected items');
    $assert_session->pageTextContains('Publish media was applied to 2 items');
    for ($i = 1; $i <= 2; $i++) {
      $this->assertTrue($this->storage->loadUnchanged($i)->isPublished(), 'The publish action failed in some of the media entities.');
    }

    // Test deletion in bulk.
    $page->checkField('media_bulk_form[0]');
    $page->checkField('media_bulk_form[1]');
    $page->selectFieldOption('action', 'media_delete_action');
    $page->pressButton('Apply to selected items');
    $assert_session->pageTextContains('Are you sure you want to delete these items?');
    $page->pressButton('Delete');
    $assert_session->pageTextContains('Deleted 2 media entities.');
    for ($i = 1; $i <= 2; $i++) {
      $this->assertNull($this->storage->loadUnchanged($i), 'Could not delete some of the media entities.');
    }

  }

}
