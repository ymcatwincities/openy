<?php

namespace Drupal\purge_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Shares the closeDialog AJAX form callback.
 *
 * @see \Drupal\purge_ui\Form\PurgerAddForm
 * @see \Drupal\purge_ui\Form\DeletePurgerForm
 * @see \Drupal\purge_ui\Form\PurgerConfigFormBase
 */
trait CloseDialogTrait {

  /**
   * Respond a CloseModalDialogCommand to close the modal dialog.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function closeDialog(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

}
