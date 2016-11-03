<?php

namespace Drupal\tango_card\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tango_card\TangoCardWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Tango Card account edit forms.
 */
class AccountForm extends ContentEntityForm {

  /**
   * The Tango Card wrapper.
   *
   * @var \Drupal\tango_card\TangoCardWrapper
   */
  protected $tangoCardWrapper;

  /**
   * Constructs the SettingsForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\tango_card\TangoCardWrapper $tango_card_wrapper
   *   The Tango Card wrapper.
   */
  public function __construct(EntityManagerInterface $entity_manager, TangoCardWrapper $tango_card_wrapper) {
    parent::__construct($entity_manager);
    $this->tangoCardWrapper = $tango_card_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('tango_card.tango_card_wrapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    if (!$this->getEntity()->cc_token->value) {
      $form['cc'] = array(
        '#title' => $this->t('Credit card information'),
        '#type' => 'fieldset',
      );

      $form['cc']['cc_number'] = array(
        '#type' => 'creditfield_cardnumber',
        '#title' => $this->t('Number'),
        '#maxlength' => 16,
        '#required' => TRUE,
      );

      $form['cc']['cc_date'] = array(
        '#type' => 'creditfield_expiration',
        '#title' => $this->t('Expiration Date'),
        '#required' => TRUE,
      );

      $form['cc']['cc_cvv'] = array(
        '#type' => 'creditfield_cardcode',
        '#title' => $this->t('CVV Code'),
        '#maxlength' => 4,
        '#description' => 'Your 3 or 4 digit security code on the back of your card.',
        '#required' => TRUE,
      );

      $form['billing'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Billing information'),
        '#tree' => TRUE,
      );

      $fields = array(
        'f_name' => 'First name',
        'l_name' => 'Last name',
        'address' => 'Address',
        'city' => 'City',
        'state' => 'State',
        'zip' => 'Zip code',
        'country' => 'Country',
      );

      // TODO: improve address field.
      foreach ($fields as $field => $title) {
        $form['billing'][$field] = array(
          '#type' => 'textfield',
          '#title' => $this->t($title),
          '#required' => TRUE,
        );
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $account_id = reset($form_state->getValue('remote_id'));
    $account_id = $account_id['value'];

    if (!UrlHelper::isValid($account_id)) {
      $form_state->setErrorByName('remote_id', $this->t('Invalid account ID. Must NOT contain characters invalid in a URI.'));
    }

    if ($this->getEntity()->isNew() && $this->tangoCardWrapper->getRemoteAccount($account_id)) {
      $form_state->setErrorByName('remote_id', $this->t('This account already exists.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();

    try {
      if (!$this->tangoCardWrapper->setRemoteAccount($entity->remote_id->value, $entity->mail->value)) {
        drupal_set_message($this->t('An error occurred while creating your account. Please try again later or contact support.'), 'error');
        return;
      }

      $this->tangoCardWrapper->setAccount($entity);

      $billing_info = $form_state->getValue('billing') + array('email' => $entity->mail->value);
      $cc_info = array(
        'number' => $form_state->getValue('cc_number'),
        'cvv' => $form_state->getValue('cc_cvv'),
        'date' => $form_state->getValue('cc_date'),
      );

      if (!$cc_token = $this->tangoCardWrapper->registerCreditCard($cc_info, $billing_info)) {
        drupal_set_message($this->t('An error occurred while processing your credit card. Please try again later or contact support.'), 'error');
        return;
      }
    }
    catch (Exception $e) {
      drupal_set_message($this->t('An error occurred and processing did not complete. Please try again later or contact support.'), 'error');
      return;
    }

    $entity->set('cc_token', $cc_token);
    $entity->set('cc_number', substr($cc_info['number'], -4));
    $entity->save();

    $form_state->setRedirect('entity.tango_card_account.collection');
  }

}
