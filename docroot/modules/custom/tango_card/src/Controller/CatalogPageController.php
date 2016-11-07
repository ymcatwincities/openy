<?php

namespace Drupal\tango_card\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\tango_card\TangoCardWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Tango Card catalog page display.
 */
class CatalogPageController extends ControllerBase {

  /**
   * The Tango Card wrapper.
   *
   * @var \Drupal\tango_card\TangoCardWrapper
   */
  protected $tangoCardWrapper;

  /**
   * Constructs the CatalogPageController object.
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
   * Return Tango Card catalog page.
   *
   * @return array
   *   A renderable array.
   */
  public function pageView() {
    try {
      $brands = $this->tangoCardWrapper->listRewards();
      $success = $brands !== FALSE;
    }
    catch (Exception $e) {
      $success = FALSE;
    }

    if (!$success) {
      $link = new Link($this->t('settings page'), Url::fromRoute('tango_card.settings'));
      $args = ['!link' => $link->toString()];

      return [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [
            $this->t('The request could not be done. Make sure Tango Card credentials are properly registered on !link.', $args),
          ],
        ],
      ];
    }

    $header = [
      'logo' => $this->t('Logo'),
      'name' => $this->t('Name'),
      'available' => $this->t('Available'),
      'type' => $this->t('Price type'),
      'currency_code' => $this->t('Currency code'),
      'prices' => $this->t('Price options'),
    ];

    $types = [
      'fixed' => $this->t('Fixed'),
      'variable' => $this->t('Variable'),
    ];

    $yes = $this->t('Yes');
    $no = $this->t('No');

    $rows = [];
    foreach ($brands as $brand) {
      $img = ['#theme' => 'image', '#uri' => $brand->image_url];
      $row = [render($img), $brand->description];

      $rewards = (array) $brand->rewards;
      $reward = array_shift($rewards);

      $row['available'] = $reward->available ? $yes : $no;
      $row['type'] = $types['fixed'];
      $row['currency_code'] = $reward->currency_code;

      if ($reward->unit_price == -1) {
        $args = ['!min' => $reward->min_price, '!max' => $reward->max_price];

        $row['prices'] = $this->t('!min to !max', $args);
        $row['type'] = $types['variable'];
      }
      else {
        if ($reward->currency_code != 'USD') {
          $args = ['!code' => $reward->currency_code];
          $row['currency_code'] = $this->t('USD (delivered in !code)', $args);
        }

        $prices = [$reward->unit_price];
        foreach ($rewards as $reward) {
          $prices[] = $reward->unit_price;
        }

        $row['prices'] = implode(', ', $prices);
      }

      $rows[] = $row;
    }

    $build = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('Your catalog is empty.'),
      '#rows' => $rows,
    ];

    return $build;
  }

}
