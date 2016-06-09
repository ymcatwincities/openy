<?php

namespace Drupal\mindbody_cache_proxy\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for MindBody Cache edit forms.
 *
 * @ingroup mindbody_cache_proxy
 */
class MindbodyCacheForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\mindbody_cache_proxy\Entity\MindbodyCache */
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
        drupal_set_message($this->t('Created the %label MindBody Cache.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label MindBody Cache.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.mindbody_cache.canonical', ['mindbody_cache' => $entity->id()]);
  }

}
