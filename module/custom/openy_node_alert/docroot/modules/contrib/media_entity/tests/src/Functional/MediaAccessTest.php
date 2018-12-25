<?php

namespace Drupal\Tests\media_entity\Functional;

use Drupal\media_entity\Entity\Media;
use Drupal\user\Entity\Role;

/**
 * Basic access tests for Media Entity.
 *
 * @group media_entity
 */
class MediaAccessTest extends MediaEntityFunctionalTestBase {

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
    $this->testBundle = $this->drupalCreateMediaBundle();
  }

  /**
   * Test some access control functionality.
   */
  public function testMediaAccess() {

    $assert_session = $this->assertSession();

    // Create media.
    $media = Media::create([
      'bundle' => $this->testBundle->id(),
      'name' => 'Unnamed',
    ]);
    $media->save();
    $user_media = Media::create([
      'bundle' => $this->testBundle->id(),
      'name' => 'Unnamed',
      'uid' => $this->nonAdminUser->id(),
    ]);
    $user_media->save();

    // We are logged-in as admin, so test 'administer media' permission.
    $this->drupalGet('media/' . $user_media->id());
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('media/' . $user_media->id() . '/edit');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('media/' . $user_media->id() . '/delete');
    $assert_session->statusCodeEquals(200);

    $this->drupalLogin($this->nonAdminUser);
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load('authenticated');

    // Test 'view media' permission.
    $this->drupalGet('media/' . $media->id());
    $assert_session->statusCodeEquals(403);
    $this->grantPermissions($role, ['view media']);
    $this->drupalGet('media/' . $media->id());
    $assert_session->statusCodeEquals(200);

    // Test 'create media' permission.
    $this->drupalGet('media/add/' . $this->testBundle->id());
    $assert_session->statusCodeEquals(403);
    $this->grantPermissions($role, ['create media']);
    $this->drupalGet('media/add/' . $this->testBundle->id());
    $assert_session->statusCodeEquals(200);

    // Test 'update media' and 'delete media' permissions.
    $this->drupalGet('media/' . $user_media->id() . '/edit');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('media/' . $user_media->id() . '/delete');
    $assert_session->statusCodeEquals(403);
    $this->grantPermissions($role, ['update media']);
    $this->grantPermissions($role, ['delete media']);
    $this->drupalGet('media/' . $user_media->id() . '/edit');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('media/' . $user_media->id() . '/delete');
    $assert_session->statusCodeEquals(200);

    // Test 'update any media' and 'delete any media' permissions.
    $this->drupalGet('media/' . $media->id() . '/edit');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('media/' . $media->id() . '/delete');
    $assert_session->statusCodeEquals(403);
    $this->grantPermissions($role, ['update any media']);
    $this->grantPermissions($role, ['delete any media']);
    $this->drupalGet('media/' . $media->id() . '/edit');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('media/' . $media->id() . '/delete');
    $assert_session->statusCodeEquals(200);

  }

}
