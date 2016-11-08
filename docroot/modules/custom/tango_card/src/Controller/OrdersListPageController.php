<?php

namespace Drupal\tango_card\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\tango_card\TangoCardAccountInterface;
use Drupal\tango_card\TangoCardWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides Tango Card orders history page display.
 */
class OrdersListPageController extends ControllerBase {

  /**
   * Page size.
   */
  const PAGE_SIZE = 25;

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
   * Return Tango Card orders history page.
   *
   * @return array
   *   A renderable array.
   */
  public function pageView(Request $request, TangoCardAccountInterface $tango_card_account) {
    $build = [];

    $page = pager_find_page();
    $this->tangoCardWrapper->setAccount($tango_card_account);

    try {
      $results = $this->tangoCardWrapper->getOrderHistory($page * self::PAGE_SIZE, self::PAGE_SIZE);
      $success = $results !== FALSE;
    }
    catch (Exception $e) {
      $success = FALSE;
    }

    if (!$success) {
      drupal_set_message($this->t('An error occurred. Please try again later or contact support.'), 'error');
      return $build;
    }

    pager_default_initialize($results->total_count, self::PAGE_SIZE);

    $rows = [];
    foreach ($results->orders as $order) {
      $rows[] = [
        $order->order_id,
        $this->tangoCardWrapper->getRewardInfo($order->sku)->description,
        '$' . number_format($order->amount / 100, 2),
        $this->dateFormatter->format(strtotime($order->delivered_at), 'short'),
        $order->recipient->name,
        $order->recipient->email,
        new Link($this->t('see details'), Url::fromRoute('tango_card.order_info', [
          'tango_card_account' => $tango_card_account->id(),
          'order_id' => $order->order_id,
        ])),
      ];
    }

    $header = [
      $this->t('ID'),
      $this->t('Name'),
      $this->t('Amount'),
      $this->t('Date'),
      $this->t('Recipient name'),
      $this->t('Recipient email'),
      '',
    ];

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('There are no orders yet.'),
      '#rows' => $rows,
    ];

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

}
