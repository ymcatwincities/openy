<?php

namespace Drupal\ymca_cdn_product\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Camp du Nord Personify Product edit forms.
 *
 * @ingroup ymca_cdn_product
 */
class CdnPrsProductForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\ymca_cdn_product\Entity\CdnPrsProduct */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

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
        drupal_set_message($this->t('Created the %label Camp du Nord Personify Product.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Camp du Nord Personify Product.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.cdn_prs_product.canonical', ['cdn_prs_product' => $entity->id()]);
  }

}
