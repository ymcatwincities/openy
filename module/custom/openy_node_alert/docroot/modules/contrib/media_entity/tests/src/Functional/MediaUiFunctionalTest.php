<?php

namespace Drupal\Tests\media_entity\Functional;

use Drupal\media_entity\Entity\Media;

/**
 * Ensures that media UI works correctly.
 *
 * @group media_entity
 */
class MediaUiFunctionalTest extends MediaEntityFunctionalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'media_entity_test_type',
  ];

  /**
   * The test media bundle.
   *
   * @var \Drupal\media_entity\MediaBundleInterface
   */
  protected $testBundle;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the media actions (add/edit/delete).
   */
  public function testMediaWithOnlyOneBundle() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    $bundle = $this->drupalCreateMediaBundle();

    // Assert that media item list is empty.
    $this->drupalGet('admin/content/media');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('No content available.');

    $this->drupalGet('media/add');
    $assert_session->statusCodeEquals(200);
    $assert_session->addressEquals('media/add/' . $bundle->id());
    $assert_session->checkboxChecked('edit-revision');

    // Tests media item add form.
    $media_name = $this->randomMachineName();
    $page->fillField('name[0][value]', $media_name);
    $revision_log_message = $this->randomString();
    $page->fillField('revision_log', $revision_log_message);
    $page->pressButton('Save and publish');
    $media_id = $this->container->get('entity.query')->get('media')->execute();
    $media_id = reset($media_id);
    /** @var \Drupal\media_entity\MediaInterface $media */
    $media = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->loadUnchanged($media_id);
    $this->assertEquals($media->getRevisionLogMessage(), $revision_log_message);
    $assert_session->titleEquals($media->label() . ' | Drupal');

    // Test if the media list contains exactly 1 media bundle.
    $this->drupalGet('admin/content/media');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains($media->label());

    // Tests media edit form.
    $media_name2 = $this->randomMachineName();
    $this->drupalGet('media/' . $media_id . '/edit');
    $assert_session->checkboxNotChecked('edit-revision');
    $media_name = $this->randomMachineName();
    $page->fillField('name[0][value]', $media_name2);
    $page->pressButton('Save and keep published');
    $assert_session->titleEquals($media_name2 . ' | Drupal');

    // Assert that the media list updates after an edit.
    $this->drupalGet('admin/content/media');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains($media_name2);

    // Test that there is no empty vertical tabs element, if the container is
    // empty (see #2750697).
    // Make the "Publisher ID" and "Created" fields hidden.
    $this->drupalGet('/admin/structure/media/manage/' . $bundle->id . '/form-display');
    $page->selectFieldOption('fields[created][parent]', 'hidden');
    $page->selectFieldOption('fields[uid][parent]', 'hidden');
    $page->pressButton('Save');
    // Assure we are testing with a user without permission to manage revisions.
    $this->drupalLogin($this->nonAdminUser);
    // Check the container is not present.
    $this->drupalGet('media/' . $media_id . '/edit');
    // An empty tab container would look like this.
    $raw_html = '<div data-drupal-selector="edit-advanced" data-vertical-tabs-panes><input class="vertical-tabs__active-tab" data-drupal-selector="edit-advanced-active-tab" type="hidden" name="advanced__active_tab" value="" />' . "\n" . '</div>';
    $assert_session->responseNotContains($raw_html);
    // Continue testing as admin.
    $this->drupalLogin($this->adminUser);

    // Enable revisions by default.
    $bundle->setNewRevision(TRUE);
    $bundle->save();
    $this->drupalGet('media/' . $media_id . '/edit');
    $assert_session->checkboxChecked('edit-revision');
    $page->fillField('name[0][value]', $media_name);
    $page->fillField('revision_log', $revision_log_message);
    $page->pressButton('Save and keep published');
    $assert_session->titleEquals($media_name . ' | Drupal');
    /** @var \Drupal\media_entity\MediaInterface $media */
    $media = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->loadUnchanged($media_id);
    $this->assertEquals($media->getRevisionLogMessage(), $revision_log_message);

    // Tests media delete form.
    $this->drupalGet('media/' . $media_id . '/edit');
    $page->clickLink('Delete');
    $assert_session->pageTextContains('This action cannot be undone');
    $page->pressButton('Delete');
    $media_id = \Drupal::entityQuery('media')->execute();
    $this->assertFalse($media_id);

    // Assert that the media list is empty after deleting the media item.
    $this->drupalGet('admin/content/media');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextNotContains($media_name);
    $assert_session->pageTextContains('No content available.');

  }

  /**
   * Tests the "media/add" and "admin/content/media" pages.
   *
   * Tests if the "media/add" page gives you a selecting option if there are
   * multiple media bundles available.
   */
  public function testMediaWithMultipleBundles() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Test access to media overview page.
    $this->drupalLogout();
    $this->drupalGet('admin/content/media');
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/content');

    // Test there is a media tab in the menu.
    $page->clickLink('Media');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('No content available.');

    // Tests and creates the first media bundle.
    $first_media_bundle = $this->drupalCreateMediaBundle(['description' => $this->randomMachineName(32)]);

    // Test and create a second media bundle.
    $second_media_bundle = $this->drupalCreateMediaBundle(['description' => $this->randomMachineName(32)]);

    // Test if media/add displays two media bundle options.
    $this->drupalGet('media/add');

    // Checks for the first media bundle.
    $assert_session->pageTextContains($first_media_bundle->label());
    $assert_session->pageTextContains($first_media_bundle->description);
    // Checks for the second media bundle.
    $assert_session->pageTextContains($second_media_bundle->label());
    $assert_session->pageTextContains($second_media_bundle->description);

    // Continue testing media bundle filter.
    $first_media_item = Media::create(['bundle' => $first_media_bundle->id()]);
    $first_media_item->save();
    $second_media_item = Media::create(['bundle' => $second_media_bundle->id()]);
    $second_media_item->save();

    // Go to media item list.
    $this->drupalGet('admin/content/media');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkExists('Add media');

    // Assert that all available media items are in the list.
    $assert_session->pageTextContains($first_media_item->label());
    $assert_session->pageTextContains($first_media_bundle->label());
    $assert_session->pageTextContains($second_media_item->label());
    $assert_session->pageTextContains($second_media_bundle->label());

    // Filter for each bundle and assert that the list has been updated.
    $this->drupalGet('admin/content/media', ['query' => ['provider' => $first_media_bundle->id()]]);
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains($first_media_item->label());
    $assert_session->pageTextNotContains($second_media_item->label());

    // Filter all and check for all items again.
    $this->drupalGet('admin/content/media', ['query' => ['provider' => 'All']]);
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains($first_media_item->label());
    $assert_session->pageTextContains($first_media_bundle->label());
    $assert_session->pageTextContains($second_media_item->label());
    $assert_session->pageTextContains($second_media_bundle->label());

  }

}
