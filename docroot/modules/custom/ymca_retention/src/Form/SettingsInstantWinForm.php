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
   * @var \Drupal\Core\Entity\Query\QueryFactory
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

    $form['statistics'] = [
      '#type' => 'details',
      '#title' => $this->t('Statistics'),
      '#open' => TRUE,
    ];
    $members_count = $this->entityQuery->getAggregate('ymca_retention_member')
      ->aggregate('id', 'COUNT')
      ->execute()[0]['id_count'];
    $chances_count = $this->entityQuery->getAggregate('ymca_retention_member_chance')
      ->aggregate('id', 'COUNT')
      ->execute()[0]['id_count'];
    $chances_played = $this->entityQuery->getAggregate('ymca_retention_member_chance')
      ->condition('played', 0, '<>')
      ->aggregate('id', 'COUNT')
      ->execute()[0]['id_count'];

    $chances_won = $this->entityQuery->getAggregate('ymca_retention_member_chance')
      ->condition('winner', 1)
      ->groupBy('value')
      ->aggregate('id', 'COUNT')
      ->execute();

    $used_prizes = [];
    $budget_spent = 0;
    foreach ($chances_won as $chance) {
      $used_prizes[$chance['value']] = $chance['id_count'];
      $budget_spent += $chance['value'] * $chance['id_count'];
    }

    $form['statistics']['members'] = [
      '#type' => 'inline_template',
      '#template' => file_get_contents(drupal_get_path('module', 'ymca_retention') . '/templates/inline/ymca-retention-statistics.html.twig'),
      '#context' => [
        'members' => [
          'count' => $members_count,
        ],
        'chances' => [
          'count' => $chances_count,
          'played' => $chances_played,
          'won' => array_sum($used_prizes),
        ],
        'budget' => [
          'spent' => $budget_spent,
        ],
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
      '#type' => 'details',
      '#title' => $this->t('Prize pool'),
      '#tree' => TRUE,
    ];

    foreach ($config->get('prize_pool') as $delta => $prize) {
      $form['prize_pool'][$delta] = ['#type' => 'container', '#tree' => TRUE];
      $form['prize_pool'][$delta]['value'] = [
        '#type' => 'value',
        '#value' => $prize['value'],
      ];

      $form['prize_pool'][$delta]['quantity'] = [
        '#type' => 'number',
        '#title' => '$' . $prize['value'],
        '#required' => TRUE,
        '#min' => 0,
        '#step' => 1,
        '#default_value' => $prize['quantity'],
        '#field_suffix' => '(' . $this->t('Used prizes: %count', [
          '%count' => isset($used_prizes[$prize['value']]) ? $used_prizes[$prize['value']] : 0,
        ]) . ')',
      ];
    }

    $form['product_pool'] = [
      '#type' => 'details',
      '#title' => $this->t('Tango Card product pool'),
      '#tree' => TRUE,
    ];

    $products = $config->get('product_pool');
    foreach (range(0, 14) as $delta) {
      $form['product_pool'][$delta] = [
        '#type' => 'textfield',
        '#title' => $this->t('Product %n', ['%n' => $delta + 1]),
        '#title_display' => 'invisible',
        '#default_value' => isset($products[$delta]) ? $products[$delta] : '',
        '#autocomplete_route_name' => 'tango_card.product_autocomplete',
        '#autocomplete_route_parameters' => [
          'product_type' => 'brand',
        ],
      ];
    }

    $fields = [
      'loss_messages_short' => $this->t('Loss messages - Short'),
      'loss_messages_long_1' => $this->t('Loss messages - Long (#1)'),
      'loss_messages_long_2' => $this->t('Loss messages - Long (#2)'),
    ];

    foreach ($fields as $field => $title) {
      $form[$field] = [
        '#type' => 'details',
        '#title' => $title,
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
      'product_pool',
      'loss_messages_short',
      'loss_messages_long_1',
      'loss_messages_long_2',
    ];

    foreach ($fields as $field) {
      $values = array_filter($form_state->getValue($field));

      if (!$values) {
        $form_state->setErrorByName($field, $this->t('You must set at least one item.'));
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
      'percentage',
      'prize_pool',
      'product_pool',
      'loss_messages_short',
      'loss_messages_long_1',
      'loss_messages_long_2',
    ];

    foreach ($fields as $field) {
      $config->set($field, $form_state->getValue($field));
    }

    $pool_keyed = [];
    $selected_brands = $config->get('product_pool');

    try {
      // TODO: inject Tango Card wrapper service in this class.
      $brands = \Drupal::service('tango_card.tango_card_wrapper')->listRewards();

      foreach ($config->get('prize_pool') as $prize) {
        $value = $prize['value'];
        $amount = $value * 100;

        $pool_keyed[$value] = [];
        foreach ($selected_brands as $brand) {
          foreach ($brands[$brand]->rewards as $reward) {
            if ($reward->unit_price == -1 || $reward->unit_price == $amount) {
              $pool_keyed[$value][] = $reward->sku;
              break;
            }
          }
        }
      }
    }
    catch (Exception $e) {
      // Do nothing.
    }

    $config->set('product_pool_keyed', $pool_keyed);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
