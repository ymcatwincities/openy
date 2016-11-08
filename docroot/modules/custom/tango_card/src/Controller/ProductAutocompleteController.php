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
   * @return string
   *   A json object containing the matched requests.
   */
  public function handleAutocomplete(Request $request, $product_type) {
    $results = [];

    if (!in_array($product_type, ['all', 'fixed', 'variable'])) {
      return new JsonResponse($results);
    }

    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtoupper(array_pop($typed_string));

      try {
        $rewards = $this->tangoCardWrapper->listRewardsKeyed();
        $success = $rewards !== FALSE;
      }
      catch (Exception $e) {
        $success = FALSE;
      }

      if (!$success) {
        return new JsonResponse($results);
      }

      switch ($product_type) {
        case 'all':
          foreach ($rewards as $reward) {
            $this->addReward($reward);
          }
          break;

        case 'fixed':
          foreach ($rewards as $reward) {
            if ($reward->unit_price != -1) {
              $this->addReward($reward);
            }
          }
          break;

        case 'variable':
          foreach ($rewards as $reward) {
            if ($reward->unit_price == -1) {
              $this->addReward($reward);
            }
          }
          break;
      }

      $matches = preg_grep('/' . $typed_string . '/', $this->rewards);
      foreach ($matches as $value => $label) {
        $results[] = ['value' => $value, 'label' => $label];
      }
    }

    return new JsonResponse($results);
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
