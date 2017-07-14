<?php

namespace Drupal\openy_digital_signage_classes_schedule\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting Digital Signage Classes Session Item entities.
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
class OpenYClassesSessionDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\openy_digital_signage_classes_schedule\Entity\OpenYClassesSession */
    $entity = $this->getEntity();
    // If entity is overridden, mark original entity as not overridden.
    if ($entity->isOverridden()) {
      $original = $this->entityManager->getStorage('openy_ds_classes_session')
        ->load($entity->get('original_session')->target_id);
      $original->set('overridden', FALSE);
      $original->save();
    }

    parent::submitForm($form, $form_state);
  }

}
