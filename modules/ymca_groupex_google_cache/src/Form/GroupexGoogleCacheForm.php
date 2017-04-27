<?php

namespace Drupal\ymca_groupex_google_cache\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Groupex Google Cache edit forms.
 *
 * @ingroup ymca_groupex_google_cache
 */
class GroupexGoogleCacheForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\ymca_groupex_google_cache\Entity\GroupexGoogleCache */
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
        drupal_set_message($this->t('Created the %label Groupex Google Cache.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Groupex Google Cache.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.groupex_google_cache.canonical', ['groupex_google_cache' => $entity->id()]);
  }

}
