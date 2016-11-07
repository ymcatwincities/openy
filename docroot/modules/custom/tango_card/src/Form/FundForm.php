<?php

namespace Drupal\tango_card\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\tango_card\AccountInterface;
use Drupal\tango_card\TangoCardWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Tango Card settings form.
 */
class FundForm extends FormBase {

  /**
   * Form current step.
   *
   * @var int
   */
  protected $step = 1;

  /**
   * The Tango Card wrapper.
   *
   * @var \Drupal\tango_card\TangoCardWrapper
   */
  protected $tangoCardWrapper;

  /**
   * Constructs the BalanceForm object.
   *
   * @param \Drupal\tango_card\TangoCardWrapper $tango_card_wrapper
   *   The Tango Card wrapper.
   */
  public function __construct(TangoCardWrapper $tango_card_wrapper) {
    $this->tangoCardWrapper = $tango_card_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tango_card.tango_card_wrapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tango_card_balance';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $tango_card_account = NULL) {
    $this->tangoCardWrapper->setAccount($tango_card_account);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Fund'),
    ];

    if ($this->step == 2) {
      $args = [
        '@amount' => number_format($form_state->getValue('amount'), 2),
      ];

      $form['message'] = [
        '#markup' => $this->t('Are you sure you want to fund $@amount?', $args),
      ];

      return $form;
    }

    $form['balance'] = [
      '#title' => $this->t('Current balance'),
      '#type' => 'item',
      '#markup' => '$' . number_format($this->tangoCardWrapper->getAccountBalance() / 100, 2),
    ];

    $form['fund'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fund account'),
    ];

    $form['fund']['amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount'),
      '#min' => 0.01,
      '#max' => 5000,
      '#step' => 0.01,
      '#field_prefix' => '$',
      '#required' => TRUE,
      '#description' => $this->t('Enter amount in dollars (USD).'),
    ];

    $form['fund']['cc_cvv'] = [
      '#type' => 'creditfield_cardcode',
      '#title' => $this->t('Credit card CVV Code'),
      '#maxlength' => 4,
      '#size' => 8,
      '#description' => $this->t('Your 3 or 4 digit security code on the back of your card.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->step == 1) {
      $form_state->setRebuild();
      $form_state->setStorage([
        'amount' => $form_state->getValue('amount') * 100,
        'cc_cvv' => $form_state->getValue('cc_cvv'),
        'account' => $this->tangoCardWrapper->getAccount(),
      ]);

      $this->step++;
    }
    else {
      try {
        $values = $form_state->getStorage();
        $this->tangoCardWrapper->setAccount($values['account']);
        $success = $this->tangoCardWrapper->fundAccount($values['amount'], $values['cc_cvv']);
      }
      catch (Exception $e) {
        $success = FALSE;
      }

      if (!$success) {
        drupal_set_message($this->t('An error occurred and processing did not complete. Please try again later or contact support.'), 'error');
        return;
      }

      $form_state->setRedirect('entity.tango_card_account.collection');

      drupal_set_message($this->t('Your account has been funded successfully.'));
    }
  }

}
