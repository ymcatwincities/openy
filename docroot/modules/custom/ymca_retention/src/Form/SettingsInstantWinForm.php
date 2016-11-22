<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form for managing Instant Win settings.
 */
class SettingsInstantWinForm extends ConfigFormBase {

  /**
   * The entity query factory.
   *
   * @var Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Constructs the SettingsInstantWinForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query factory.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_retention_instant_win_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ymca_retention.instant_win'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ymca_retention.instant_win');

    $form['prize_sku'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prize SKU'),
      '#required' => TRUE,
      '#default_value' => $config->get('prize_sku'),
      '#autocomplete_route_name' => 'tango_card.product_autocomplete',
      '#autocomplete_route_parameters' => [
        'product_type' => 'variable',
      ],
    ];

    $form['percentage'] = [
      '#type' => 'number',
      '#title' => $this->t('Probability to win'),
      '#required' => TRUE,
      '#field_suffix' => '%',
      '#default_value' => $config->get('percentage'),
      '#min' => 0,
      '#max' => 100,
      '#step' => 1,
    ];

    $form['prize_pool'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Prize pool'),
      '#tree' => TRUE,
    ];

    $results = $this->entityQuery->getAggregate('ymca_retention_member_chance')
      ->condition('winner', 1)
      ->groupBy('value')
      ->aggregate('id', 'COUNT')
      ->execute();

    $used_prizes = [];
    foreach ($results as $result) {
      $used_prizes[$result['value']] = $result['id_count'];
    }

    foreach ($config->get('prize_pool') as $delta => $prize) {
      $form['prize_pool'][$delta] = ['#type' => 'container', '#tree' => TRUE];
      $form['prize_pool'][$delta]['value'] = [
        '#type' => 'value',
        '#value' => $prize['value'],
      ];

      $form['prize_pool'][$delta]['quantity'] = [
        '#type' => 'number',
        '#title' => '$ ' . $prize['value'],
        '#required' => TRUE,
        '#min' => 0,
        '#step' => 1,
        '#default_value' => $prize['quantity'],
        '#field_suffix' => '(' . $this->t('Used prizes: %count', [
          '%count' => isset($used_prizes[$prize['value']]) ? $used_prizes[$prize['value']] : 0,
        ]) . ')',
      ];
    }

    $fields = [
      'loss_messages_short' => 'Loss messages - Short',
      'loss_messages_long_1' => 'Loss messages - Long (#1)',
      'loss_messages_long_2' => 'Loss messages - Long (#2)',
    ];

    foreach ($fields as $field => $title) {
      $form[$field] = [
        '#type' => 'fieldset',
        '#title' => $this->t($title),
        '#tree' => TRUE,
      ];

      $messages = $config->get($field);
      foreach (range(0, 5) as $delta) {
        $form[$field][$delta] = [
          '#type' => 'textfield',
          '#title' => $this->t('Message %n', ['%n' => $delta + 1]),
          '#title_display' => 'invisible',
          '#default_value' => isset($messages[$delta]) ? $messages[$delta] : '',
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $fields = [
      'loss_messages_short',
      'loss_messages_long_1',
      'loss_messages_long_2',
    ];

    foreach ($fields as $field) {
      $values = array_filter($form_state->getValue($field));

      if (!$values) {
        $form_state->setErrorByName($field, $this->t('You must set at least one message.'));
        continue;
      }

      $form_state->setValue($field, array_values($values));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ymca_retention.instant_win');
    $fields = [
      'prize_sku',
      'percentage',
      'prize_pool',
      'loss_messages_short',
      'loss_messages_long_1',
      'loss_messages_long_2',
    ];

    foreach ($fields as $field) {
      $config->set($field, $form_state->getValue($field));
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
