<?php

namespace Drupal\tango_card\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Form handler for the Tango Card campaign edit forms.
 */
class CampaignForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $states = array(
      ':input[name="notification_enabled[value]"]' => array('checked' => TRUE),
    );

    $form['notification'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Notification'),
      '#weight' => 1,
      '#states' => array('visible' => $states),
    );

    foreach (array('from', 'subject', 'message') as $suffix) {
      $field = 'notification_' . $suffix;

      $form['notification'][$field] = $form[$field];
      $form['notification'][$field]['widget'][0]['value']['#states'] = array('required' => $states);
      unset($form[$field]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->save();

    $form_state->setRedirect('entity.tango_card_campaign.collection');
  }

}
