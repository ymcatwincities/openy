<?php

namespace Drupal\logger_entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Logger Entity edit forms.
 *
 * @ingroup logger_entity
 */
class LoggerEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\logger_entity\Entity\LoggerEntity */
    $form = parent::buildForm($form, $form_state);

    $form['data'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Data') ,
      '#default_value' => $this->entity->get('data')->value,
    ];

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
        drupal_set_message($this->t('Created the %label Logger Entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Logger Entity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.logger_entity.canonical', ['logger_entity' => $entity->id()]);
  }

}
