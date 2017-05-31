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
    $form['time_date_slot'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Time settings'),
      '#weight' => 2,
    ];
    $form['time_date_slot']['time_slot'] = $form['time_slot'];
    $form['time_date_slot']['show_date'] = $form['show_date'];
    $form['time_date_slot']['date'] = $form['date'];
    $form['time_date_slot']['date']['#states'] = [
      'visible' => [
        ':input[name="show_date[value]"]' => ['checked' => FALSE],
      ],
    ];

    unset($form['time_slot']);
    unset($form['show_date']);
    unset($form['date']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $show_date = $form_state->getValue(['show_date', 'value']);
    if ($show_date) {
      $form_state->setValue('date', [
        [
          'value' => NULL,
          'end_value' => NULL,
        ],
      ]);
    }
    parent::validateForm($form, $form_state);
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
    $form_state->setRedirect('entity.openy_digital_signage_sch_item.collection');
  }

}
