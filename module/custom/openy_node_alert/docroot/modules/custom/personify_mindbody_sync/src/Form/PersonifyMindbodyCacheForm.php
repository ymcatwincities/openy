<?php

namespace Drupal\personify_mindbody_sync\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Personify MindBody Cache edit forms.
 *
 * @ingroup personify_mindbody_sync
 */
class PersonifyMindbodyCacheForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\personify_mindbody_sync\Entity\PersonifyMindbodyCache */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Personify MindBody Cache.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Personify MindBody Cache.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.personify_mindbody_cache.canonical', ['personify_mindbody_cache' => $entity->id()]);
  }

}
