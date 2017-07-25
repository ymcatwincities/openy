<?php

namespace Drupal\webform\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformDialogHelper;

/**
 * Trait class for Webform Ajax dialog support.
 *
 * @todo Issue #2785047: In Outside In mode, messages should appear in the off-canvas tray, not the main page.
 * @see https://www.drupal.org/node/2785047
 */
trait WebformDialogFormTrait {

  use WebformAjaxFormTrait;

  /**
   * {@inheritdoc}
   */
  protected function isAjax() {
    return $this->isDialog();
  }

  /**
   * Is the current request for an Ajax modal/dialog.
   *
   * @return bool
   *   TRUE if the current request is for an Ajax modal/dialog.
   */
  protected function isDialog() {
    $wrapper_format = $this->getRequest()
      ->get(MainContentViewSubscriber::WRAPPER_FORMAT);
    return (in_array($wrapper_format, [
      'drupal_ajax',
      'drupal_modal',
      'drupal_dialog',
      'drupal_dialog_' . WebformDialogHelper::getOffCanvasTriggerName(),
    ])) ? TRUE : FALSE;
  }

  /**
   * Is the current request for an off canvas dialog.
   *
   * @return bool
   *   TRUE if the current request is for an off canvas dialog.
   */
  protected function isOffCanvasDialog() {
    $wrapper_format = $this->getRequest()
      ->get(MainContentViewSubscriber::WRAPPER_FORMAT);
    return (in_array($wrapper_format, [
      'drupal_dialog_' . WebformDialogHelper::getOffCanvasTriggerName(),
    ])) ? TRUE : FALSE;
  }

  /**
   * Is the current request a quick edit page.
   *
   * @return bool
   *   TRUE if the current request a quick edit page.
   */
  protected function isQuickEdit() {
    return (\Drupal::request()->query->get('destination')) ? TRUE : FALSE;
  }

  /**
   * Add modal dialog support to a form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $settings
   *   Ajax settings.
   *
   * @return array
   *   The webform with modal dialog support.
   */
  protected function buildDialogForm(array &$form, FormStateInterface $form_state, array $settings = []) {
    return $this->buildAjaxForm($form, $form_state, $settings);
  }

  /**
   * Add modal dialog support to a confirm form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The webform with modal dialog support.
   */
  protected function buildDialogConfirmForm(array &$form, FormStateInterface $form_state) {
    if (!$this->isDialog() || $this->isOffCanvasDialog()) {
      return $form;
    }

    $this->buildDialogForm($form, $form_state);

    // Replace 'Cancel' link button with a close dialog button.
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::noSubmit'],
      '#validate' => ['::noSubmit'],
      '#weight' => 100,
      '#ajax' => [
        'callback' => '::cancelAjaxForm',
        'event' => 'click',
      ],
    ];
    return $form;
  }

  /****************************************************************************/
  // Ajax submit callbacks.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function cancelAjaxForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand());
    return $response;
  }

  /**
   * Empty submit callback used to only have the submit button to use an #ajax submit callback.
   *
   * This allows modal dialog to using ::submitCallback to validate and submit
   * the form via one ajax request.
   */
  public function noSubmit(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

}
