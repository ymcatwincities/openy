<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for updating webform submission using tokenized URL.
 *
 * @group Webform
 */
class WebformSubmissionTokenUpdateTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_token_update'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Test updating webform submission using tokenized URL.
   */
  public function testTokenUpdateTest() {
    // Post test submission.
    $this->drupalLogin($this->rootUser);
    $webform_token_update = Webform::load('test_token_update');
    $sid = $this->postSubmissionTest($webform_token_update);
    $webform_submission = WebformSubmission::load($sid);

    // Check token update access allowed.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet($webform_submission->getTokenUrl());
    $this->assertResponse(200);
    $this->assertRaw('Submission information');
    $this->assertFieldByName('textfield', $webform_submission->getData('textfield'));

    // Check token update access denied.
    $webform_token_update->setSetting('token_update', FALSE)->save();
    $this->drupalLogin($this->normalUser);
    $this->drupalGet($webform_submission->getTokenUrl());
    $this->assertResponse(200);
    $this->assertNoRaw('Submission information');
    $this->assertNoFieldByName('textfield', $webform_submission->getData('textfield'));
  }

}
