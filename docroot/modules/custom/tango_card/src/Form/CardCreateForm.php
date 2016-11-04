<?php

namespace Drupal\tango_card\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\tango_card\TangoCardWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Tango Card creation form.
 */
class CardCreateForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Tango Card wrapper.
   *
   * @var \Drupal\tango_card\TangoCardWrapper
   */
  protected $tangoCardWrapper;

  /**
   * Constructs the CardCreateForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\tango_card\TangoCardWrapper $tango_card_wrapper
   *   The Tango Card wrapper.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TangoCardWrapper $tango_card_wrapper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tangoCardWrapper = $tango_card_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('tango_card.tango_card_wrapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tango_card_create';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['recipient'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Recipient'),
    ];

    $form['recipient']['recipient_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];

    $form['recipient']['recipient_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    $form['product'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Product'),
    ];

    $form['product']['product_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Price type'),
      '#default_value' => 'fixed',
      '#required' => TRUE,
      '#options' => [
        'fixed' => $this->t('Fixed'),
        'variable' => $this->t('Variable'),
      ],
    ];

    $states = [
      ':input[name="product_type"]' => ['value' => 'fixed'],
    ];

    $form['product']['product_sku_fixed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product SKU'),
      '#autocomplete_route_name' => 'tango_card.product_autocomplete',
      '#autocomplete_route_parameters' => [
        'product_type' => 'fixed',
      ],
      '#states' => ['visible' => $states, 'required' => $states],
    ];

    $states[':input[name="product_type"]']['value'] = 'variable';
    $form['product']['product_sku_variable'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product SKU'),
      '#autocomplete_route_name' => 'tango_card.product_autocomplete',
      '#autocomplete_route_parameters' => [
        'product_type' => 'variable',
      ],
      '#states' => ['visible' => $states, 'required' => $states],
    ];

    $form['product']['product_value'] = [
      '#type' => 'number',
      '#title' => $this->t('Product value'),
      '#min' => 0,
      '#step' => 0.01,
      '#field_prefix' => '$',
      '#description' => $this->t('Enter amount in dollars (USD).'),
      '#states' => ['visible' => $states, 'required' => $states],
    ];

    $form['account'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Account'),
      '#target_type' => 'tango_card_account',
      '#default_value' => $this->tangoCardWrapper->getAccount(),
      '#required' => TRUE,
    ];

    $form['campaign'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Campaign'),
      '#target_type' => 'tango_card_campaign',
      '#default_value' => $this->tangoCardWrapper->getCampaign(),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // TODO: Validate amount and sku.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = $this->entityTypeManager->getStorage('tango_card_account')->load($form_state->getValue('account'));
    $campaign = $this->entityTypeManager->getStorage('tango_card_campaign')->load($form_state->getValue('campaign'));

    $this->tangoCardWrapper->setAccount($account);
    $this->tangoCardWrapper->setCampaign($campaign);

    $name = $form_state->getValue('recipient_name');
    $mail = $form_state->getValue('recipient_email');

    $sku = $form_state->getValue('product_sku_fixed');
    $amount = NULL;

    if ($form_state->getValue('product_type') == 'variable') {
      $sku = $form_state->getValue('product_sku_variable');
      $amount = (integer) ($form_state->getValue('product_value') * 100);
    }

    try {
      $success = $this->tangoCardWrapper->placeOrder($name, $mail, $sku, $amount);
    }
    catch (Exception $e) {
      $success = FALSE;
    }

    if ($success) {
      $form_state->setRedirect('tango_card.orders', ['tango_card_account' => $account->id()]);

      drupal_set_message($this->t('Your order has been processed successfully.'));
    }
    else {
      drupal_set_message($this->t('An error occurred and processing did not complete. Please try again later or contact support.'), 'error');
    }
  }

}
