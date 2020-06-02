<?php

namespace Drupal\openy_mappings\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Mapping edit forms.
 *
 * @ingroup openy_mappings
 */
class MappingForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\openy_mappings\Entity\Mapping */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $messenger = \Drupal::messenger();
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $messenger->addMessage($this->t('Created the %label Mapping.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $messenger->addMessage($this->t('Saved the %label Mapping.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.mapping.canonical', ['mapping' => $entity->id()]);
  }

}
