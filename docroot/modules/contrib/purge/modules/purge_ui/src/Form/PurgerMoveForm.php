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
 * Move purger {id} in the purger execution order - 'up' or 'down'.
 */
class PurgerMoveForm extends ConfirmFormBase {
  use CloseDialogTrait;

  /**
   * Unique instance ID for the purger.
   *
   * @var string
   */
  protected $id;

  /**
   * The plugin definition for the purger.
   *
   * @var array
   */
  protected $definition;

  /**
   * Either 'up' or 'down' are valid directions to move execution order in.
   *
   * @var string
   */
  protected $direction;

  /**
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected $purgePurgers;

  /**
   * Constructs a PurgerMoveForm object.
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
    return 'purge_ui.purger_move_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->definition = $form_state->getBuildInfo()['args'][0]['definition'];
    $this->direction = $form_state->getBuildInfo()['args'][0]['direction'];
    $this->id = $form_state->getBuildInfo()['args'][0]['id'];
    $form = parent::buildForm($form, $form_state);

    // This is rendered as a modal dialog, so we need to set some extras.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    // Update the buttons and bind callbacks.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->getConfirmText(),
      '#ajax' => ['callback' => '::movePurger'],
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
    return $this->t('Yes!');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $label = ['@label' => $this->purgePurgers->getLabels()[$this->id]];
    if ($this->direction === 'up') {
      return $this->t('Do you want to move @label up in the execution order?', $label);
    }
    elseif ($this->direction === 'down') {
      return $this->t('Do you want to move @label down in the execution order?', $label);
    }
    return '';
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
   * Move the purger.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function movePurger(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    if (isset($this->purgePurgers->getPluginsEnabled()[$this->id])) {
      $response->addCommand(new ReloadConfigFormCommand('edit-purgers'));
      if ($this->direction === 'up') {
        $this->purgePurgers->movePurgerUp($this->id);
      }
      elseif ($this->direction === 'down') {
        $this->purgePurgers->movePurgerDown($this->id);
      }
    }
    return $response;
  }

}
