<?php

namespace Drupal\openy_digital_signage_classes_schedule\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Digital Signage Classes Session override form.
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
class OpenYClassesSessionOverrideForm extends ContentEntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\openy_digital_signage_classes_schedule\Entity\OpenYClassesSession
   */
  protected $overriddenEntity;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\openy_digital_signage_classes_schedule\Entity\OpenYClassesSession */
    $form = parent::buildForm($form, $form_state);
    $form['source']['#value'] = 'manually';
    $form['source']['#access'] = FALSE;
    $current_user = \Drupal::currentUser();
    $user = \Drupal::entityTypeManager()->getStorage('user')
      ->load($current_user->id());
    $form['field_session_author']['widget'][0]['target_id']['#default_value'] = $user;
    $form['field_session_author']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove button and internal Form API values from submitted values.
    $form_state->cleanValues();
    $this->overriddenEntity = $this->buildEntity($form, $form_state);
    $this->overriddenEntity->enforceIsNew();
    $uuid = \Drupal::service('uuid');
    $this->overriddenEntity->set('id', NULL);
    $this->overriddenEntity->set('uuid', $uuid->generate());
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Mark entity as overridden and save as it is without any changes.
    $this->entity->set('overridden', TRUE);
    $this->entity->save();
    $id = $this->entity->id();

    // Set new entity as entity by default.
    $this->entity = $this->overriddenEntity;
    // Set reference to original entity.
    $this->entity->set('original_session', $id);
    $this->entity->setSource('manually');


    $status = parent::save($form, $form_state);

    switch ($status) {
      default:
      case SAVED_NEW:
        drupal_set_message($this->t('Digital Signage Classes Session %label has been overridden.', [
          '%label' => $this->entity->label(),
        ]));
        break;
    }
    $form_state->setRedirect('entity.openy_ds_classes_session.collection');
  }

}
