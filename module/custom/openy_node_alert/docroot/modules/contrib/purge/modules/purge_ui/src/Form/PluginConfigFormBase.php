<?php

namespace Drupal\purge_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\purge_ui\Form\CloseDialogTrait;
use Drupal\purge_ui\Form\ReloadConfigFormCommand;

/**
 * Provides a base class for (dialog-driven) plugin configuration forms.
 *
 * Derived forms will be rendered by purge_ui as modal dialogs through links at
 * /admin/config/development/performance/purge/PLUGIN/ID/config/dialog. You
 * can use /admin/config/development/performance/purge/PLUGIN/config/ID as
 * testing variant that works outside modal dialogs.
 */
abstract class PluginConfigFormBase extends ConfigFormBase {
  use CloseDialogTrait;

  /**
   * The URL anchor in which the parent's opening button was located.
   *
   * @var string
   */
  protected $parent_id = '';

  /**
   * Determine if this is a AJAX dialog request or not.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   */
  public function isDialog(array &$form, FormStateInterface $form_state) {
    return $form_state->getBuildInfo()['args'][0]['dialog'];
  }

  /**
   * Retrieve the ID for the plugin being configured.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return string
   *   The unique identifier for this plugin.
   */
  public function getId(FormStateInterface $form_state) {
    return $form_state->getBuildInfo()['args'][0]['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // If we're being rendered as AJAX modal dialog, change the form.
    if ($this->isDialog($form, $form_state)) {
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $form['#prefix'] = '<div id="purgedialogform">';
      $form['#suffix'] = '</div>';
      // Adapt the button to send commands and add a cancel button.
      $form['actions']['submit']['#ajax'] = ['callback' => '::submitForm'];
      $form['actions']['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
        '#weight' => -10,
        '#ajax' => ['callback' => '::closeDialog'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->isDialog($form, $form_state)) {
      $response = new AjaxResponse();
      if ($form_state->getErrors()) {
        unset($form['#prefix'], $form['#suffix']);
        $form['status_messages'] = [
          '#type' => 'status_messages',
          '#weight' => -10,
        ];
        $response->addCommand(new HtmlCommand('#purgedialogform', $form));
      }
      else {
        $this->submitFormSuccess($form, $form_state);
        $response->addCommand(new CloseModalDialogCommand());
        $response->addCommand(new ReloadConfigFormCommand($this->parent_id));
      }
      return $response;
    }
    else {
      if (!$form_state->getErrors()) {
        $this->submitFormSuccess($form, $form_state);
      }
    }
    return parent::submitForm($form, $form_state);
  }

  /**
   * Form submission handler which ONLY gets called when no validation errors
   * occurred. Normally this would be the case, however with AJAX driven form
   * dialogs this handler is needed for standard behavior.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitFormSuccess(array &$form, FormStateInterface $form_state) {
    throw new \LogicException("::submitFormSuccess() not implemented!");
  }

}
