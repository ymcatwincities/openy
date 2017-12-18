<?php

namespace Drupal\openy_digital_signage_classes_schedule\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Digital Signage Classes Session edit forms.
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
class OpenYClassesSessionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\openy_digital_signage_classes_schedule\Entity\OpenYClassesSession */
    $form = parent::buildForm($form, $form_state);

    if ($this->entity->isNew()) {
      $form['source']['#value'] = 'manually';
      $form['source']['#access'] = FALSE;
      $current_user = \Drupal::currentUser();
      $user = \Drupal::entityTypeManager()->getStorage('user')
        ->load($current_user->id());
      $form['field_session_author']['widget'][0]['target_id']['#default_value'] = $user;
    }
    else {
      $form['source']['#disabled'] = TRUE;
      if ($this->entity->getSource() != 'manually') {
        $form['actions']['#access'] = FALSE;
        $form['status']['#access'] = FALSE;
      }
      if ($this->entity->original_session->entity && !empty($form['actions']['delete']['#title'])) {
        $form['actions']['delete']['#title'] = t('Restore original session');
      }
    }
    $form['field_session_author']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Digital Signage Classes Session %label has been created.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Digital Signage Classes Session %label has been saved.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.openy_ds_classes_session.collection');
  }

}
