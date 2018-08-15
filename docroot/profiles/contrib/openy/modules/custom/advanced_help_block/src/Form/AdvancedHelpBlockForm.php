<?php

namespace Drupal\advanced_help_block\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form controller for the advanced_help_block entity edit forms.
 *
 * @ingroup advanced_help_block
 */
class AdvancedHelpBlockForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    $entity = $this->entity;
    if ($entity instanceof EntityChangedInterface) {
      $entity->changed = $entity->getChangedTime();
    }

    if ($status == SAVED_UPDATED) {
      drupal_set_message(
        $this->t(
          'The Advanced Help Block %feed has been updated.', [
            '%feed' => $entity->toLink()
              ->toString()
          ]
        )
      );
    }
    else {
      drupal_set_message(
        $this->t(
          'The Advanced Help Block %feed has been added.', [
            '%feed' => $entity->toLink()
              ->toString()
          ]
        )
      );
    }

    $form_state->setRedirectUrl(new Url('view.advanced_help_blocks.ahb_list'));
    return $status;
  }
}
