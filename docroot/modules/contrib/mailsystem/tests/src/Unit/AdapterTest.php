<?php
/**
 * @file
 * Contains \Drupal\Tests\mailsystem\Unit\AdapterTest.
 */

namespace Drupal\Tests\mailsystem\Unit;

use Drupal\Core\Mail\MailInterface;
use Drupal\mailsystem\Adapter;
use Drupal\Tests\UnitTestCase;

/**
 * Test the adapter class from mailsystem which is used as the mail plugin.
 *
 * @group mailsystem
 */
class AdapterTest extends UnitTestCase {

  /**
   * The Adapter we need to test.
   *
   * @var \Drupal\mailsystem\Adapter
   */
  protected $adapter;

  /**
   * Sender plugin instance.
   *
   * @var \Drupal\mailsystem_test\Plugin\Mail\Test
   */
  protected $sender;

  /**
   * Formatter plugin instance.
   *
   * @var \Drupal\mailsystem_test\Plugin\Mail\Test
   */
  protected $formatter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->formatter = new Test();
    $this->sender = new Test();
    $this->adapter = new Adapter($this->formatter, $this->sender);
  }

  /**
   * Returns an empty message array to test with.
   *
   * @return array
   *   Associative array which holds an empty message to test with.
   */
  protected function getEmptyMessage() {
    return array(
      'subject' => 'test',
      'message' => 'message',
      'headers' => array(),
    );
  }

  /**
   * Test the right call to the formatting.
   */
  public function testFormatting() {
    $message = $this->adapter->format($this->getEmptyMessage());

    $this->assertEquals(Test::TEST_SUBJECT, $message['subject'], 'Subject match');
    $this->assertEquals(Test::TEST_BODY, $message['body'], 'Body match');
    $this->assertEquals(array(Test::TEST_HEADER_NAME => Test::TEST_HEADER_VALUE), $message['headers'], 'Header match');
  }

  /**
   * Test for successful and failed sending of a message through the Adapter.
   */
  public function testSending() {
    $message = $this->getEmptyMessage();

    $this->assertFalse($this->adapter->mail($message), 'Sending message failed as expected');

    $message['subject'] = Test::SEND_SUCCESS_SUBJECT;
    $this->assertTrue($this->adapter->mail($message), 'Sending message successful as expected');
  }
}

/**
 * Provides a test plugin to send emails.
 */
class Test implements MailInterface {
  const TEST_SUBJECT = 'Subject';
  const TEST_BODY = 'Vivamus varius commodo leo at eleifend. Nunc vestibulum dolor eget turpis pulvinar volutpat.';
  const TEST_HEADER_NAME = 'X-System';
  const TEST_HEADER_VALUE = 'D8 PHP Unit test';
  const SEND_SUCCESS_SUBJECT = 'Failed';

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    return array(
      'subject' => self::TEST_SUBJECT,
      'body' => self::TEST_BODY,
      'headers' => array(self::TEST_HEADER_NAME => self::TEST_HEADER_VALUE),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    return ($message['subject'] == self::SEND_SUCCESS_SUBJECT);
  }

}
