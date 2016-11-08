<?php

namespace Drupal\tango_card\Controller;

use Drupal\Core\Controller\ControllerBase;
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
      return [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [
            $this->t('The request could not be done. Make sure Tango Card credentials are properly registered on <a href=":url">settings page</a>.', [
              ':url' => Url::fromRoute('tango_card.settings')->toString(),
            ]),
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
      $rewards = (array) $brand->rewards;
      $reward = array_shift($rewards);

      $img = ['#theme' => 'image', '#uri' => $brand->image_url];
      $row = [
        'logo' => render($img),
        'name' => $brand->description,
        'available' => $reward->available ? $yes : $no,
        'type' => $types['fixed'],
        'currency_code' => $reward->currency_code,
      ];

      if ($reward->unit_price == -1) {
        $row['type'] = $types['variable'];
        $row['prices'] = $this->t('@min to @max', [
          '@min' => $reward->min_price,
          '@max' => $reward->max_price,
        ]);
      }
      else {
        if ($reward->currency_code != 'USD') {
          $row['currency_code'] = $this->t('USD (delivered in @code)', [
            '@code' => $reward->currency_code,
          ]);
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
