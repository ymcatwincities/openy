<?php

namespace Drupal\paragraphs\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\FieldUI;

/**
 * Form controller for paragraph type forms.
 */
class ParagraphsTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $paragraphs_type = $this->entity;

    $form['#title'] = (t('Edit %title paragraph type', array(
      '%title' => $paragraphs_type->label(),
    )));

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $paragraphs_type->label(),
      '#description' => $this->t("Label for the Paragraphs type."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $paragraphs_type->id(),
      '#machine_name' => array(
        'exists' => 'paragraphs_type_load',
      ),
      '#disabled' => !$paragraphs_type->isNew(),
    );

    // You will need additional form elements for your custom properties.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $paragraphs_type = $this->entity;
    $status = $paragraphs_type->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label Paragraphs type.', array(
        '%label' => $paragraphs_type->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label Paragraphs type was not saved.', array(
        '%label' => $paragraphs_type->label(),
      )));
    }
    if (($status == SAVED_NEW && \Drupal::moduleHandler()->moduleExists('field_ui'))
      && $route_info = FieldUI::getOverviewRouteInfo('paragraph', $paragraphs_type->id())) {
      $form_state->setRedirectUrl($route_info);
    }
    else {
      $form_state->setRedirect('entity.paragraphs_type.collection');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $form = parent::actions($form, $form_state);

    // We want to display the button only on add page.
    if ($this->entity->isNew() && \Drupal::moduleHandler()->moduleExists('field_ui')) {
      $form['submit']['#value'] = $this->t('Save and manage fields');
    }

    return $form;
  }

}
