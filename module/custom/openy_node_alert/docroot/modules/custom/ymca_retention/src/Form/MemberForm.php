<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the ymca_retention entity edit forms.
 *
 * @ingroup ymca_retention_member
 */
class MemberForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.ymca_retention_member.collection');
    $entity = $this->getEntity();
    $entity->save();
  }

}
