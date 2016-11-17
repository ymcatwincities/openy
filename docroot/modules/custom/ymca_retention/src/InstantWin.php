<?php

namespace Drupal\ymca_retention;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\tango_card\TangoCardWrapper;
use Drupal\ymca_retention\Entity\Member;
use Drupal\ymca_retention\Entity\MemberChance;

/**
 * Defines Instant win service.
 */
class InstantWin {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Tango card wrapper.
   *
   * @var \Drupal\tango_card\TangoCardWrapper
   */
  protected $tangoCardWrapper;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\tango_card\TangoCardWrapper $tango_card_wrapper
   *   The tango card wrapper.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    QueryFactory $query_factory,
    TangoCardWrapper $tango_card_wrapper
  ) {
    $this->configFactory = $config_factory;
    $this->queryFactory = $query_factory;
    $this->tangoCardWrapper = $tango_card_wrapper;
  }

  /**
   * Play the chance.
   *
   * @param Member $member
   *   Member entity.
   * @param MemberChance $chance
   *   Member chance entity.
   */
  public function play($member, $chance) {
    // Staff is not eligible to win prizes.
    if ($member->isMemberEmployee()) {
      $this->chanceLost($chance);
      return;
    }

    // Try to get the prize.
    if (!$prize = $this->getPrize()) {
      $this->chanceLost($chance);
      return;
    }

    $this->chanceWon($chance, $prize['value']);
  }

  /**
   * Chance is won.
   *
   * @param MemberChance $chance
   *   Member chance entity.
   * @param int $value
   *   Prize value.
   */
  public function chanceWon($chance, $value) {
    $chance->set('played', time());
    $chance->set('winner', 1);
    $chance->set('value', $value);
    $chance->set('message', 'Won $' . $value . ' card!');
    // TODO: generate Tango card and add order_id.
    $chance->save();
  }

  /**
   * Chance is lost.
   *
   * @param MemberChance $chance
   *   Member chance entity.
   */
  public function chanceLost($chance) {
    $chance->set('played', time());
    $chance->set('winner', 0);
    $chance->set('message', $this->messageLost());
    $chance->save();
  }

  /**
   * Get random lost message.
   */
  public function messageLost() {
    $messages = [
      'No luck',
      'Keep trying',
      'It was close',
      'Another time',
    ];

    return $messages[array_rand($messages)];
  }

  /**
   * Get the prize.
   */
  public function getPrize() {
    // Check if there are any prizes available.
    if (!$prizes = $this->prizesAvailable()) {
      return FALSE;
    }

    $settings = $this->configFactory->get('ymca_retention.instant_win');
    $percentage = $settings->get('percentage');

    if ($percentage < rand(1, 100)) {
      return FALSE;
    }

    $total_quantity = array_sum($prizes);
    $number = rand(1, $total_quantity);
    foreach ($prizes as $value => $quantity) {
      $number = $number - $quantity;
      if ($number <= 0) {
        break;
      }
    }

    $prize = ['value' => $value];
    return $prize;
  }

  /**
   * Get available prizes.
   */
  public function prizesAvailable() {
    $settings = $this->configFactory->get('ymca_retention.instant_win');
    $prize_pool = $settings->get('prize_pool');
    $available_prizes = [];

    foreach ($prize_pool as $prize) {
      // TODO: change to entityQueryAggregate.
      $chances_ids = $this->queryFactory->get('ymca_retention_member_chance')
        ->condition('winner', 1)
        ->condition('value', $prize['value'])
        ->execute();
      $count = count($chances_ids);
      if ($count < $prize['quantity']) {
        $available_prizes[$prize['value']] = $prize['quantity'] - $count;
      }
    }

    return $available_prizes;
  }

}
