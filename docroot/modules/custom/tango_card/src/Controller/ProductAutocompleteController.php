<?php

namespace Drupal\tango_card\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Drupal\tango_card\TangoCardWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides Tango Card product autocomplete.
 */
class ProductAutocompleteController extends ControllerBase {

  /**
   * The Tango Card wrapper.
   *
   * @var \Drupal\tango_card\TangoCardWrapper
   */
  protected $tangoCardWrapper;

  /**
   * Rewards list.
   *
   * @var array
   */
  protected $rewards = [];

  /**
   * Constructs the ProductAutocompleteController object.
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
   * Handles Tango Card rewards autocomplete.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $product_type
   *   Filter by product type. Can be 'fixed', 'variable', 'brand' or 'all'.
   *
   * @return string
   *   A json object containing the matched requests.
   */
  public function handleAutocomplete(Request $request, $product_type) {
    $results = [];

    $types = ['all', 'fixed', 'variable', 'brand'];
    if (!in_array($product_type, $types) || !($input = $request->query->get('q'))) {
      return new JsonResponse($results);
    }

    $typed_string = Tags::explode($input);
    $typed_string = Unicode::strtoupper(array_pop($typed_string));

    if ($product_type == 'brand') {
      $this->populateBrandRewards();
    }
    else {
      $this->populateSkuRewards($product_type);
    }

    $matches = preg_grep('/' . $typed_string . '/', $this->rewards);
    foreach ($matches as $value => $label) {
      $results[] = ['value' => $value, 'label' => $label];
    }

    return new JsonResponse($results);
  }

  /**
   * Populates rewards list, keying by SKU.
   *
   * @param string $product_type
   *   Filter by product type. Can be 'fixed', 'variable' or 'all'.
   */
  protected function populateSkuRewards($product_type) {
    try {
      if (!$rewards = $this->tangoCardWrapper->listRewardsKeyed()) {
        return;
      }
    }
    catch (Exception $e) {
      return;
    }

    if ($product_type == 'all') {
      foreach ($rewards as $reward) {
        $this->addReward($reward);
      }
    }
    else {
      $i = $product_type == 'fixed' ? 1 : -1;

      foreach ($rewards as $reward) {
        if ($i * $reward->unit_price > 0) {
          $this->addReward($reward);
        }
      }
    }
  }

  /**
   * Populates rewards list, keying by brand.
   */
  protected function populateBrandRewards() {
    try {
      if (!$rewards = $this->tangoCardWrapper->listRewards()) {
        return;
      }
    }
    catch (Exception $e) {
      return;
    }

    foreach ($rewards as $key => $reward) {
      $reward->sku = $key;
      $this->addReward($reward);
    }
  }

  /**
   * Adds a reward into list.
   *
   * @param object $reward
   *   The reward object obtained from Tango Card.
   */
  protected function addReward($reward) {
    $this->rewards[$reward->sku] = Unicode::strtoupper($reward->description);
  }

}
