<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneCommentTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\comment\Tests\CommentTestBase;
use Drupal\comment\Tests\CommentTestTrait;

/**
 * Create a comment and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneCommentTest extends CommentTestBase {

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'block', 'comment', 'node', 'history', 'field_ui', 'datetime'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer content types',
    'administer comments',
    'administer comment types',
    'administer comment fields',
    'administer comment display',
    'skip comment approval',
    'post comments',
    'access comments',
    'access user profiles',
    'access content',
    'clone comment entity'
  ];

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);
  }

  public function testCommentEntityClone() {
    $subject = 'Test comment for clone';
    $body = $this->randomMachineName();
    $comment = $this->postComment($this->node, $body, $subject, TRUE);

    $this->drupalPostForm('entity_clone/comment/' . $comment->id(), [], t('Clone'));

    $comments = \Drupal::entityTypeManager()
      ->getStorage('comment')
      ->loadByProperties([
        'subject' => $subject . ' - Cloned',
        'comment_body' => $body,
      ]);
    $comments = reset($comments);
    $this->assertTrue($comments, 'Test comment cloned found in database.');
  }

}