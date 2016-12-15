<?php

namespace Drupal\Tests\purge_ui\Unit\Form;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Form\FormState;
use Drupal\purge_ui\Form\LoggingConfigForm;

/**
 * @coversDefaultClass \Drupal\purge_ui\Form\LoggingConfigForm
 * @group purge_ui
 */
class LoggingConfigFormTest extends UnitTestCase {

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The tested form.
   *
   * @var \Drupal\purge_ui\Form\LoggingConfigForm
   */
  protected $form;

  /**
   * The mocked logger service.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\purge\Logger\LoggerServiceInterface
   */
  protected $purgeLogger;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $defaults = [['id' => 'testchannel', 'grants' => [2,4,1]]];
    $this->purgeLogger = $this->getMock('Drupal\purge\Logger\LoggerServiceInterface');
    $this->purgeLogger->method('getChannels')->willReturn($defaults);
    $this->purgeLogger->method('hasChannel')
      ->will($this->returnCallback(function ($subject) {
        return ($subject === 'testchannel');
      }));

    // Create the container and instantiate a form.
    $this->container = new ContainerBuilder();
    $this->container->set('purge.logger', $this->purgeLogger);
    $this->container->set('string_translation', $this->getStringTranslationStub());
    $this->container->set('url_generator', $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface'));

    \Drupal::setContainer($this->container);
    $this->form = LoggingConfigForm::create($this->container);
  }

  /**
   * @covers ::buildForm
   */
  public function testBuildForm() {
    $form = $this->form->buildForm([], new FormState());
    // Test the attached dialog behavior.
    $this->assertTrue(isset($form['#attached']['library'][0]));
    $this->assertEquals($form['#attached']['library'][0], 'core/drupal.dialog.ajax');
    // Verify the text description.
    $this->assertTrue(isset($form['msg']['#markup']));
    $this->assertTrue((bool) strpos($form['msg']['#markup']->render(), 'named <i><code>purge'));
    // Verify the structure of the table and that it holds the testchannel.
    $this->assertTrue(isset($form['table']['#header']['id']));
    $this->assertEquals('Id', $form['table']['#header']['id']->render());
    $this->assertEquals(9, count($form['table']['#header']));
    $this->assertEquals('checkbox', $form['table']['testchannel'][0]['#type']);
    $this->assertFalse($form['table']['testchannel'][0]['#default_value']);
    $this->assertTrue($form['table']['testchannel'][1]['#default_value']);
    $this->assertTrue($form['table']['testchannel'][2]['#default_value']);
    $this->assertFalse($form['table']['testchannel'][3]['#default_value']);
    $this->assertTrue($form['table']['testchannel'][4]['#default_value']);
    $this->assertFalse($form['table']['testchannel'][5]['#default_value']);
    $this->assertFalse($form['table']['testchannel'][6]['#default_value']);
    $this->assertFalse($form['table']['testchannel'][7]['#default_value']);
    $this->assertEquals(3, count($form['table']));
    // Verify the action buttons.
    $this->assertEquals('submit', $form['actions']['submit']['#type']);
    $this->assertEquals('Save', $form['actions']['submit']['#value']->render());
    $this->assertEquals('primary', $form['actions']['submit']['#button_type']);
    $this->assertEquals('::setChannels', $form['actions']['submit']['#ajax']['callback']);
    $this->assertEquals('submit', $form['actions']['cancel']['#type']);
    $this->assertEquals('Cancel', $form['actions']['cancel']['#value']->render());
    $this->assertEquals('danger', $form['actions']['cancel']['#button_type']);
    $this->assertEquals('::closeDialog', $form['actions']['cancel']['#ajax']['callback']);
  }

  /**
   * @covers ::setChannels
   */
  public function testSetChannels() {
    $form = $this->form->buildForm([], new FormState());
    // Assert that empty submits only close the dialog, nothing else.
    $ajax = $this->form->setChannels($form, new FormState());
    $this->assertInstanceOf('Drupal\Core\Ajax\AjaxResponse', $ajax);
    $this->assertEquals('closeDialog', $ajax->getCommands()[0]['command']);
    $this->assertEquals(1, count($ajax->getCommands()));
    // Verify that non-existent channels don't lead to saving anything.
    $submitted = new FormState();
    $submitted->setValue('table', ['fake' => ["1"]]);
    $ajax = $this->form->setChannels($form, $submitted);
    $this->assertInstanceOf('Drupal\Core\Ajax\AjaxResponse', $ajax);
    $this->assertEquals('closeDialog', $ajax->getCommands()[0]['command']);
    $this->assertEquals(1, count($ajax->getCommands()));
    // Verify that correct data does lead to a write.
    $this->purgeLogger->expects($this->once())
      ->method('setChannel')
      ->with($this->equalTo('testchannel'), $this->equalTo([0,1]));
    $submitted = new FormState();
    $submitted->setValue('table', ['testchannel' => ["1", "1", "0", 0]]);
    $ajax = $this->form->setChannels($form, $submitted);
    $this->assertInstanceOf('Drupal\Core\Ajax\AjaxResponse', $ajax);
    $this->assertEquals('closeDialog', $ajax->getCommands()[0]['command']);
    $this->assertEquals('redirect', $ajax->getCommands()[1]['command']);
    $this->assertEquals(2, count($ajax->getCommands()));
  }

  /**
   * @covers ::submitForm
   */
  public function testSubmitForm() {
    $form = $this->form->buildForm([], new FormState());
    // Verify that the returned $has_resulted_in_changes is FALSE without data.
    $this->assertFalse($this->form->submitForm($form, new FormState()));
    // Verify that non-existent channels don't lead to saving anything.
    $submitted = new FormState();
    $submitted->setValue('table', ['fake' => ["1"]]);
    $this->assertFalse($this->form->submitForm($form, $submitted));
    // Verify that correct data does lead to a write.
    $this->purgeLogger->expects($this->once())
      ->method('setChannel')
      ->with($this->equalTo('testchannel'), $this->equalTo([0,1]));
    $submitted = new FormState();
    $submitted->setValue('table', ['testchannel' => ["1", "1", "0", 0]]);
    $this->assertTrue($this->form->submitForm($form, $submitted));
  }

}
