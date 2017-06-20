<?php

namespace Drupal\logger_entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LoggerEntityTypeForm.
 *
 * @package Drupal\logger_entity\Form
 */
class LoggerEntityTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $logger_entity_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $logger_entity_type->label(),
      '#description' => $this->t("Label for the Logger Entity type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $logger_entity_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\logger_entity\Entity\LoggerEntityType::load',
      ],
      '#disabled' => !$logger_entity_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $logger_entity_type = $this->entity;
    $status = $logger_entity_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Logger Entity type.', [
          '%label' => $logger_entity_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Logger Entity type.', [
          '%label' => $logger_entity_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($logger_entity_type->toUrl('collection'));
  }

}
