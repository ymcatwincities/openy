<?php

namespace Drupal\openy_digital_signage_schedule\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for OpenY Digital Signage Schedule Item edit forms.
 *
 * @ingroup openy_digital_signage_schedule
 */
class OpenYScheduleItemForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\openy_digital_signage_schedule\Entity\OpenYScheduleItem */
    $form = parent::buildForm($form, $form_state);

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
        drupal_set_message($this->t('OpenY Digital Signage Schedule Item %label has been created.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('OpenY Digital Signage Schedule Item %label has been saved.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.openy_digital_signage_sch_item.canonical', ['openy_digital_signage_sch_item' => $entity->id()]);
  }

}
