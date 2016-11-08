<?php

namespace Drupal\tango_card\Form;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
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
   * Constructs the CardCreateForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\tango_card\TangoCardWrapper $tango_card_wrapper
   *   The Tango Card wrapper.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $entity_query, TangoCardWrapper $tango_card_wrapper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityQuery = $entity_query;
    $this->tangoCardWrapper = $tango_card_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.query'),
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

    foreach (['fixed', 'variable'] as $prod_type) {
      $states = [':input[name="product_type"]' => ['value' => $prod_type]];
      $form['product']['product_sku_' . $prod_type] = [
        '#type' => 'textfield',
        '#title' => $this->t('Product SKU'),
        '#autocomplete_route_name' => 'tango_card.product_autocomplete',
        '#autocomplete_route_parameters' => [
          'product_type' => $prod_type,
        ],
        '#states' => ['visible' => $states, 'required' => $states],
      ];
    }

    $form['product']['product_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount'),
      '#min' => 0,
      '#step' => 1,
      '#description' => $this->t('Amounts must be given in the lowest fractional currency unit (i.e. cents, yen, etc…) in the SKUs currency. For example, a reward with denomination of 500 and currency code of USD represents $5.00 in USD, not $500.00.'),
      '#states' => ['visible' => $states, 'required' => $states],
    ];

    $link_title = $this->t('here');
    $fields = [
      'account' => [
        'title' => 'Tango Card account',
        'description' => 'The Tango Account to make your request. To see available accounts, click <a href=":url">here</a>.',
      ],
      'campaign' => [
        'title' => 'Campaign',
        'description' => 'The campaign to make your request. The campaign contains settings like email template and notification message. To see available campaigns, click <a href=":url">here</a>.',
      ],
    ];

    foreach ($fields as $field => $info) {
      $entity_type = 'tango_card_' . $field;
      $args = [
        ':url' => Url::fromRoute('entity.' . $entity_type . '.collection')->toString(),
      ];

      $form[$field] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t($info['title']),
        '#target_type' => $entity_type,
        '#required' => TRUE,
        '#description' => $this->t($info['description'], $args),
      ];

      if (!$this->entityQuery->get($entity_type)->execute()) {
        $args['@entity'] = $form[$field]['#title'];
        drupal_set_message($this->t('There is no @entity registered yet. Create a new one <a href=":url">here</a> before proceed.', $args), 'warning');
      }
    }

    $form['account']['#default_value'] = $this->tangoCardWrapper->getAccount();
    $form['campaign']['#default_value'] = $this->tangoCardWrapper->getCampaign();

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

    $sku_element = 'product_sku_fixed';
    if ($is_variable = $form_state->getValue('product_type') == 'variable') {
      $sku_element = 'product_sku_variable';
    }

    $sku = $form_state->getValue($sku_element);
    if (!$reward = $this->tangoCardWrapper->getRewardInfo($sku)) {
      $form_state->setErrorByName($sku_element, $this->t('Invalid SKU.'));
      return;
    }

    $amount_element = 'product_amount';
    $amount = $form_state->getValue($amount_element);

    if (!$is_variable) {
      $amount_element = 'product_sku_fixed';
      $amount = $reward->unit_price;
    }
    elseif ($amount < $reward->min_price || $amount > $reward->max_price) {
      $form_state->setErrorByName($amount_element, $this->t('Amount should be between %min and %max.', [
        '%min' => $reward->min_price,
        '%max' => $reward->max_price,
      ]));
    }

    $account = $this->entityTypeManager->getStorage('tango_card_account')->load($form_state->getValue('account'));
    $this->tangoCardWrapper->setAccount($account);

    if ($this->tangoCardWrapper->getAccountBalance() < $amount) {
      $url = Url::fromRoute('entity.tango_card_account.fund_form', [
        'tango_card_account' => $account->id(),
      ]);


      $form_state->setErrorByName($amount_element, $this->t('Your account does not have sufficient funds to generate this card. Access <a href=":url">here</a> to fund your account.', [
        ':url'=> $url->toString(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $campaign = $this->entityTypeManager->getStorage('tango_card_campaign')->load($form_state->getValue('campaign'));
    $this->tangoCardWrapper->setCampaign($campaign);

    $name = $form_state->getValue('recipient_name');
    $mail = $form_state->getValue('recipient_email');

    $sku = $form_state->getValue('product_sku_fixed');
    $amount = NULL;

    if ($form_state->getValue('product_type') == 'variable') {
      $sku = $form_state->getValue('product_sku_variable');
      $amount = (int) $form_state->getValue('product_amount');
    }

    try {
      $success = $this->tangoCardWrapper->placeOrder($name, $mail, $sku, $amount);
    }
    catch (Exception $e) {
      $success = FALSE;
    }

    if ($success) {
      $form_state->setRedirect('tango_card.orders', [
        'tango_card_account' => $this->tangoCardWrapper->getAccount()->id(),
      ]);

      drupal_set_message($this->t('Your order has been processed successfully.'));
    }
    else {
      drupal_set_message($this->t('An error occurred and processing did not complete. Please try again later or contact support.'), 'error');
    }
  }

}
