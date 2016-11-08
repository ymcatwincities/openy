<?php

namespace Drupal\tango_card\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Url;
use Drupal\tango_card\TangoCardAccountInterface;
use Drupal\tango_card\TangoCardWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides Tango Card order page display.
 */
class OrderPageController extends ControllerBase {

  /**
   * The Tango Card wrapper.
   *
   * @var \Drupal\tango_card\TangoCardWrapper
   */
  protected $tangoCardWrapper;

  /**
   * Date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs the OrdersPageController object.
   *
   * @param \Drupal\tango_card\TangoCardWrapper $tango_card_wrapper
   *   The Tango Card wrapper.
   * @param Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter.
   */
  public function __construct(TangoCardWrapper $tango_card_wrapper, DateFormatter $date_formatter) {
    $this->tangoCardWrapper = $tango_card_wrapper;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tango_card.tango_card_wrapper'),
      $container->get('date.formatter')
    );
  }

  /**
   * Return Tango Card order details page.
   *
   * @return array
   *   A renderable array.
   */
  public function pageView(Request $request, TangoCardAccountInterface $tango_card_account, $order_id) {
    $build = [];
    $this->tangoCardWrapper->setAccount($tango_card_account);

    try {
      $order = $this->tangoCardWrapper->getOrderInfo($order_id);
      $success = $order !== FALSE;
    }
    catch (Exception $e) {
      $success = FALSE;
    }

    if (!$success) {
      drupal_set_message($this->t('An error occurred. Please try again later or contact support.'), 'error');
      return $build;
    }

    $order = (array) $order + (array) $order->reward;

    $fields = [
      'order_id' => 'ID',
      'sku' => 'Name',
      'delivered_at' => 'Date',
      'amount' => 'Amount',
      'reward_from' => 'From email',
      'reward_subject' => 'Email subject',
      'reward_message' => 'Email message',
      'token' => 'Token',
      'number' => 'Number',
      'pin' => 'Pin',
    ];

    foreach ($fields as $field => $title) {
      $build[$field] = [
        '#type' => 'item',
        '#title' => $this->t($title),
        '#markup' => empty($order[$field]) ? '-' : $order[$field],
      ];
    }

    $reward = $this->tangoCardWrapper->getRewardInfo($order['sku']);

    $build['sku']['#markup'] = $reward->description . ' (' . $build['sku']['#markup'] . ')';
    $build['delivered_at']['#markup'] = $this->dateFormatter->format(strtotime($build['delivered_at']['#markup']));
    $build['amount']['#markup'] = '$' . number_format($build['amount']['#markup'] / 100, 2);

    return $build;
  }

}
