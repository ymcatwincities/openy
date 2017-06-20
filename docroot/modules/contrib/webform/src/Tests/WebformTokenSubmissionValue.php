<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform token submission value.
 *
 * @group Webform
 */
class WebformTokenSubmissionValue extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_token_submission_value'];

  /**
   * Test webform webform token submission value.
   */
  public function testWebformTokenSubmissionValue() {
    $webform = Webform::load('test_token_submission_value');
    $this->postSubmission($webform);

    $tokens = [
      'webform_submission:values:email' => 'example@example.com',
      'webform_submission:values:emails' => '- one@example.com
- two@example.com
- three@example.com',
      'webform_submission:values:emails:0' => 'one@example.com',
      'webform_submission:values:emails:1' => 'two@example.com',
      'webform_submission:values:emails:2' => 'three@example.com',
      'webform_submission:values:emails:value:comma' => 'one@example.com, two@example.com, three@example.com',
      'webform_submission:values:emails:html' => '<div class="item-list"><ul><li><a href="mailto:one@example.com">one@example.com</a></li><li><a href="mailto:two@example.com">two@example.com</a></li><li><a href="mailto:three@example.com">three@example.com</a></li></ul></div>',
      'webform_submission:values:emails:0:html' => '<a href="mailto:one@example.com">one@example.com</a>',
      'webform_submission:values:emails:1:html' => '<a href="mailto:two@example.com">two@example.com</a>',
      'webform_submission:values:emails:2:html' => '<a href="mailto:three@example.com">three@example.com</a>',
      'webform_submission:values:emails:99:html' => '',
      'webform_submission:values:name' => 'John Smith',
      'webform_submission:values:names' => '- John Smith
- Jane Doe',
      'webform_submission:values:names:0' => 'John Smith',
      'webform_submission:values:names:1' => 'Jane Doe',
      'webform_submission:values:names:99' => '',
      'webform_submission:values:contact' => 'John Smith
10 Main Street
Springfield, Alabama. 12345
United States
john@example.com',
      'webform_submission:values:contacts' => '- John Smith
  10 Main Street
  Springfield, Alabama. 12345
  United States
  john@example.com
- Jane Doe
  10 Main Street
  Springfield, Alabama. 12345
  United States
  jane@example.com',
      'webform_submission:values:contacts:html' => '<div class="item-list"><ul><li>John Smith<br />10 Main Street<br />Springfield, Alabama. 12345<br />United States<br /><a href="mailto:john@example.com">john@example.com</a><br /></li><li>Jane Doe<br />10 Main Street<br />Springfield, Alabama. 12345<br />United States<br /><a href="mailto:jane@example.com">jane@example.com</a><br /></li></ul></div>',
      'webform_submission:values:contacts:0:html' => 'John Smith<br />10 Main Street<br />Springfield, Alabama. 12345<br />United States<br /><a href="mailto:john@example.com">john@example.com</a><br />',
      'webform_submission:values:contacts:0:name' => 'John Smith',
      'webform_submission:values:contacts:1:name' => 'Jane Doe',
    ];
    foreach ($tokens as $token => $value) {
      $this->assertRaw("<tr><th>$token</th><td>$value</td></tr>");
    }
  }

}
