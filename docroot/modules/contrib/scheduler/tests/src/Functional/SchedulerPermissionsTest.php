<?php

namespace Drupal\Tests\scheduler\Functional;

use Drupal\Component\Utility\SafeMarkup;

/**
 * Tests the permissions of the Scheduler module.
 *
 * @group scheduler
 */
class SchedulerPermissionsTest extends SchedulerBrowserTestBase {

  /**
   * Tests that users without permission do not see the scheduler date fields.
   */
  public function testUserPermissions() {
    // Create a user who can add the content type but who does not have the
    // permission to use the scheduler functionality.
    $this->webUser = $this->drupalCreateUser([
      'access content',
      'administer nodes',
      'create ' . $this->type . ' content',
      'edit own ' . $this->type . ' content',
      'delete own ' . $this->type . ' content',
      'view own unpublished content',
    ]);
    $this->drupalLogin($this->webUser);

    // Check that neither of the fields are displayed when creating a node.
    $this->drupalGet('node/add/' . $this->type);
    $this->assertNoFieldByName('publish_on[0][value][date]', NULL, 'The Publish-on field is not shown for users who do not have permission to schedule content');
    $this->assertNoFieldByName('unpublish_on[0][value][date]', NULL, 'The Unpublish-on field is not shown for users who do not have permission to schedule content');

    // Initially run tests when publishing and unpublishing are not required.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_required', FALSE)
      ->setThirdPartySetting('scheduler', 'unpublish_required', FALSE)
      ->save();

    // Check that a new node can be saved and published.
    $title = $this->randomString(15);
    $this->drupalPostForm('node/add/' . $this->type, ['title[0][value]' => $title], t('Save and publish'));
    $this->assertText(sprintf('%s %s has been created.', $this->typeName, SafeMarkup::checkPlain($title)), 'A node can be created and published when the user does not have scheduler permissions.');

    // Check that a new node can be saved as unpublished.
    $title = $this->randomString(15);
    $this->drupalPostForm('node/add/' . $this->type, ['title[0][value]' => $title], t('Save as unpublished'));
    $this->assertText(sprintf('%s %s has been created.', $this->typeName, SafeMarkup::checkPlain($title)), 'A node can be created and saved as unpublished when the user does not have scheduler permissions.');

    // Set publishing and unpublishing to required, to make it a stronger test.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_required', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_required', TRUE)
      ->save();

    // @TODO Add tests when scheduled publishing and unpublishing are required.
    // Cannot be done until we make a decision on what 'required'  means.
    // @see https://www.drupal.org/node/2707411
    // "Conflict between 'required publishing' and not having scheduler
    // permission"
  }

}
