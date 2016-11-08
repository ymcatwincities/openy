<?php

namespace Drupal\tango_card\Form;

use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tango_card\TangoCardWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Tango Card account edit forms.
 */
class AccountForm extends ContentEntityForm {

  /**
   * The entity query factory.
   *
   * @var Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

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
   * @param Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   * @param \Drupal\tango_card\TangoCardWrapper $tango_card_wrapper
   *   The Tango Card wrapper.
   */
  public function __construct(EntityManagerInterface $entity_manager, QueryFactory $entity_query, TangoCardWrapper $tango_card_wrapper) {
    parent::__construct($entity_manager);
    $this->entityQuery = $entity_query;
    $this->tangoCardWrapper = $tango_card_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity.query'),
      $container->get('tango_card.tango_card_wrapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    try {
      $success = !empty($this->tangoCardWrapper->listRewards(TRUE));
    }
    catch (Exception $e) {
      $success = FALSE;
    }

    if (!$success) {
      return [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [
            $this->t('In order to create an account, make sure Tango Card credentials are properly registered on <a href=":url">settings page</a>.', [
              ':url' => Url::fromRoute('tango_card.settings')->toString(),
            ]),
          ],
        ],
      ];
    }

    $form = parent::buildForm($form, $form_state);

    if (!$this->getEntity()->cc_token->value) {
      $form['cc'] = [
        '#title' => $this->t('Credit card information'),
        '#type' => 'fieldset',
      ];

      $form['cc']['cc_number'] = [
        '#type' => 'creditfield_cardnumber',
        '#title' => $this->t('Number'),
        '#maxlength' => 16,
        '#required' => TRUE,
      ];

      $form['cc']['cc_date'] = [
        '#type' => 'creditfield_expiration',
        '#title' => $this->t('Expiration Date'),
        '#required' => TRUE,
      ];

      $form['cc']['cc_cvv'] = [
        '#type' => 'creditfield_cardcode',
        '#title' => $this->t('CVV Code'),
        '#maxlength' => 4,
        '#description' => $this->t('Your 3 or 4 digit security code on the back of your card.'),
        '#required' => TRUE,
      ];

      $form['billing'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Billing information'),
        '#tree' => TRUE,
      ];

      $fields = [
        'f_name' => 'First name',
        'l_name' => 'Last name',
        'address' => 'Address',
        'city' => 'City',
        'state' => 'State',
        'zip' => 'Zip code',
        'country' => 'Country',
      ];

      foreach ($fields as $field => $title) {
        $form['billing'][$field] = [
          '#type' => 'textfield',
          '#title' => $this->t($title),
          '#required' => TRUE,
        ];
      }

      $form['billing']['state']['#maxlength'] = 40;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    foreach (['customer', 'remote_id'] as $field) {
      $value = reset($form_state->getValue($field));
      $value = $value['value'];

      if (!UrlHelper::isValid($value)) {
        $form_state->setErrorByName($field, $this->t('Invalid %name. Must NOT contain characters invalid in a URI.', [
          '%name' => $form[$field]['widget'][0]['value']['#title'],
        ]));
      }
    }

    if ($this->getEntity()->isNew() && $this->entityQuery->get('tango_card_account')->condition('remote_id', $value)->execute()) {
      $form_state->setErrorByName($field, $this->t('The entered Account ID already exists.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();

    try {
      $response = $this->tangoCardWrapper->getTangoCard()->getAccountInfo($entity->customer->value, $entity->remote_id->value);

      if (!empty($response->success)) {
        $entity->set('mail', $response->account->email);
      }
      elseif (!$this->tangoCardWrapper->createAccount($entity->customer->value, $entity->remote_id->value, $entity->mail->value)) {
        drupal_set_message($this->t('An error occurred while creating your account. Please try again later or contact support.'), 'error');
        return;
      }

      $this->tangoCardWrapper->setAccount($entity);

      $billing_info = $form_state->getValue('billing') + ['email' => $entity->mail->value];
      $cc_info = [
        'number' => $form_state->getValue('cc_number'),
        'cvv' => $form_state->getValue('cc_cvv'),
        'date' => $form_state->getValue('cc_date'),
      ];

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
