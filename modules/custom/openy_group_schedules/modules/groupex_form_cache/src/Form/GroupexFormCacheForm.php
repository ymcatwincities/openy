<?php

namespace Drupal\groupex_form_cache\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for GroupEx Pro Form Cache edit forms.
 *
 * @ingroup groupex_form_cache
 */
class GroupexFormCacheForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\groupex_form_cache\Entity\GroupexFormCache */
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
        $messenger->addMessage($this->t('Created the %label GroupEx Pro Form Cache.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $messenger->addMessage($this->t('Saved the %label GroupEx Pro Form Cache.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.groupex_form_cache.canonical', ['groupex_form_cache' => $entity->id()]);
  }

}
