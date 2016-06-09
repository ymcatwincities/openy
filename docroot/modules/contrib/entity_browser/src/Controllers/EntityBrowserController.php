<?php
/**
 * @file
 * Contains \Drupal\entity_browser\Controllers\EntityBrowserController.
 */

namespace Drupal\entity_browser\Controllers;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Entity\EntityInterface;

/**
 * Returns responses for entity browser routes.
 */
class EntityBrowserController extends ControllerBase {

  /**
   * Return an Ajax dialog command for editing a referenced entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity being edited.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response with a command for opening or closing the dialog
   *   containing the edit form.
   */
  public function entityBrowserEdit(EntityInterface $entity) {
    // Build the entity edit form.
    $form_object = $this->entityManager()->getFormObject($entity->getEntityTypeId(), 'edit');
    $form_object->setEntity($entity);
    $form_state = (new FormState())
      ->setFormObject($form_object)
      ->disableRedirect();
    // Building the form also submits.
    $form = $this->formBuilder()->buildForm($form_object, $form_state);

    // Return a response, depending on whether it's successfully submitted.
    if (!$form_state->isExecuted()) {
      // Return the form as a modal dialog.
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $title = $this->t('Edit entity @entity', ['@entity' => $entity->label()]);
      $response = AjaxResponse::create()->addCommand(new OpenModalDialogCommand($title, $form, ['width' => 800]));
      return $response;
    }
    else {
      // Return command for closing the modal.
      return AjaxResponse::create()->addCommand(new CloseModalDialogCommand());
    }
  }

}
