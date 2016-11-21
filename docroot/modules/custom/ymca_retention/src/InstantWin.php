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
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    QueryFactory $query_factory
  ) {
    $this->configFactory = $config_factory;
    $this->queryFactory = $query_factory;
  }

  /**
   * Play the chance.
   *
   * @param Member $member
   *   Member entity.
   * @param MemberChance $chance
   *   Member chance entity.
   */
  public function play(Member $member, MemberChance $chance) {
    // Staff is not eligible to win prizes.
    if ($member->isMemberEmployee()) {
      $this->chanceLoss($chance);
      return;
    }

    // Get the lock.
    $lock = \Drupal::lock();
    while (!$lock->acquire('ymca_retention_instant_win')) {
      $lock->wait('ymca_retention_instant_win');
    }

    // Try to get the prize.
    if (!$prize = $this->getPrize()) {
      $this->chanceLoss($chance);
    }
    else {
      $this->chanceWin($member, $chance, $prize['value']);
    }

    // Release the lock.
    $lock->release('ymca_retention_instant_win');
  }

  /**
   * Chance is won.
   *
   * @param Member $member
   *   Member entity.
   * @param MemberChance $chance
   *   Member chance entity.
   * @param int $value
   *   Prize value.
   */
  public function chanceWin(Member $member, MemberChance $chance, $value) {
    $chance->set('played', time());
    $chance->set('winner', 1);
    $chance->set('value', $value);
    $chance->set('message', 'Won $' . $value . ' card!');

    $settings = $this->configFactory->get('ymca_retention.instant_win');

    // TODO: inject Tango Card wrapper service in this class.
    $tango_card_wrapper = \Drupal::service('tango_card.tango_card_wrapper');

    try {
      $order = $tango_card_wrapper->placeOrder(
        $member->getFullName(),
        $member->getEmail(),
        $settings->get('prize_sku'),
        $value * 100
      );

      if ($order) {
        $chance->set('order_id', $order->order_id);
      }
    }
    catch (Exception $e) {
      // Do nothing.
    }

    $chance->save();
  }

  /**
   * Chance is lost.
   *
   * @param MemberChance $chance
   *   Member chance entity.
   */
  public function chanceLoss(MemberChance $chance) {
    $chance->set('played', time());
    $chance->set('winner', 0);
    $chance->set('message', $this->messageLoss());
    $chance->save();
  }

  /**
   * Get random lost message.
   */
  public function messageLoss() {
    $settings = $this->configFactory->get('ymca_retention.instant_win');

    $messages = [];
    foreach (['messages_loss', 'messages_loss_ext'] as $key) {
      if (!$values = $settings->get($key)) {
        continue;
      }

      $messages[] = $values[array_rand($values)];
    }

    return implode(' — ', $messages);
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

    return ['value' => $value];
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
