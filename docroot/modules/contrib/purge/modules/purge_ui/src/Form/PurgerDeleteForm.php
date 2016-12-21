<?php

namespace Drupal\purge_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;
use Drupal\purge_ui\Form\CloseDialogTrait;
use Drupal\purge_ui\Form\ReloadConfigFormCommand;

/**
 * Delete the {id} purger instance.
 */
class PurgerDeleteForm extends ConfirmFormBase {
  use CloseDialogTrait;

  /**
   * Unique instance ID for the purger being deleted.
   *
   * @var string
   */
  protected $id;

  /**
   * The plugin definition for the purger being deleted.
   *
   * @var array
   */
  protected $definition;

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected $purgePurgers;

  /**
   * Constructs a DeletePurgerForm object.
   *
   * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purge_purgers
   *   The purgers service.
   *
   * @return void
   */
  public function __construct(PurgersServiceInterface $purge_purgers) {
    $this->purgePurgers = $purge_purgers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('purge.purgers'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'purge_ui.purger_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->id = $form_state->getBuildInfo()['args'][0]['id'];
    $this->definition = $form_state->getBuildInfo()['args'][0]['definition'];
    $form = parent::buildForm($form, $form_state);

    // This is rendered as a modal dialog, so we need to set some extras.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    // Update the buttons and bind callbacks.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->getConfirmText(),
      '#ajax' => ['callback' => '::deletePurger'],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('No'),
      '#weight' => -10,
      '#ajax' => ['callback' => '::closeDialog'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes, delete this purger!');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if (isset($this->purgePurgers->getLabels()[$this->id])) {
      return $this->t('Are you sure you want to delete @label?', ['@label' => $this->purgePurgers->getLabels()[$this->id]]);
    }
    return $this->t('Are you sure you want to delete this purger?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Delete the purger.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function deletePurger(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    if (isset($this->purgePurgers->getPluginsEnabled()[$this->id])) {
      $response->addCommand(new ReloadConfigFormCommand('edit-purgers'));
      $enabled = $this->purgePurgers->getPluginsEnabled();
      unset($enabled[$this->id]);
      $this->purgePurgers->setPluginsEnabled($enabled);
    }
    return $response;
  }

}
