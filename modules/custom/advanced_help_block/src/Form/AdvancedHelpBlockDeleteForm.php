<?php

namespace Drupal\advanced_help_block\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a advanced_help_block entity.
 *
 * @ingroup advanced_help_block
 */
class AdvancedHelpBlockDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * Returns the question to ask the user.
   *
   * @return string
   *   The form question. The page title will be set to this value.
   */
  public function getQuestion() {
    return t('Are you sure you want delete this entity?');
  }

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getCancelUrl() {
    return new Url('view.advanced_help_blocks.ahb_list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. log() replaces the watchdog.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This method is the submit handler for our form.
    $entity = $this->getEntity();
    $entity->delete();

    $this->logger('advanced_help_block')->notice(
      '@type: deleted %title.', [
        '@type' => $this->entity->bundle(),
        '%title' => $this->entity->get->field_ahb_title->value,
      ]);

    // Redirect to the.
    $form_state->setRedirect('view.advanced_help_blocks.ahb_list');
  }

}
