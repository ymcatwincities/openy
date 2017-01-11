<?php

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;
use Drupal\purge_ui\Form\QueueEmptyForm;
use Drupal\Core\Form\FormState;

/**
 * Tests \Drupal\purge_ui\Form\QueueEmptyForm.
 *
 * @group purge_ui
 */
class QueueEmptyFormTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * The route that renders the form.
   *
   * @var string
   */
  protected $route = 'purge_ui.queue_empty_form';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_ui', 'purge_queuer_test'];

  /**
   * @var \Drupal\purge\Plugin\Purge\Queuer\QueuerInterface
   */
  protected $queuer;

  /**
   * Setup the test.
   */
  public function setUp() {
    parent::setUp();
    $this->initializeQueuersService();
    $this->queuer = $this->purgeQueuers->get('a');
    $this->admin_user = $this->drupalCreateUser(['administer site configuration']);
  }

  /**
   * Tests permissions, the form controller and general form returning.
   */
  public function testAccess() {
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(200);
    $this->assertTitle(t("Are you sure you want to empty the queue? | Drupal"));
    $this->assertText(t("This action cannot be undone."));
    $this->assertText(t('Yes, throw everything away!'));
  }

  /**
   * Tests that the "No" cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\QueuerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testNo() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertRaw(t('No'));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route)->toString(), [], ['op' => t('No')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual(2, count($json));
  }

  /**
   * Tests that the confirm button clears the queue.
   *
   * @see \Drupal\purge_ui\Form\QueuerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testConfirm() {
    // Add seven objects to the queue and assert that these get deleted.
    $this->initializeInvalidationFactoryService();
    $this->initializeQueueService('file');
    for ($i = 1; $i <= 7; $i++) {
      $tags[] = $this->purgeInvalidationFactory->get('tag', "$i");
    }
    $this->purgeQueue->add($this->queuer, $tags);
    // Assert that - after reloading/committing the queue - we still have these.
    $this->purgeQueue->reload();
    $this->assertEqual(7, $this->purgeQueue->numberOfItems());
    // Call the confirm form and assert the AJAX responses.
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route)->toString(), [], ['op' => t('Yes, throw everything away!')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual(2, count($json));
    // Directly call ::emptyQueue() on a form object and assert the empty queue.
    $this->assertEqual(7, $this->purgeQueue->numberOfItems());
    $form = [];
    $form_instance = new QueueEmptyForm($this->purgeQueue);
    $form_instance->emptyQueue($form, new FormState());
    // $this->assertEqual(0, $this->purgeQueue->numberOfItems());
    // $this->purgeQueue->reload();
    $this->assertEqual(0, $this->purgeQueue->numberOfItems());
  }

}
