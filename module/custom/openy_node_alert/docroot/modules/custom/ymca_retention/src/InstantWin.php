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
    // Get the lock.
    $lock = \Drupal::lock();
    $lock_id = 'ymca_retention_instant_win:' . $chance->id();

    // Let's avoid concurrent plays on the same chance.
    if (!$lock->acquire($lock_id)) {
      // It seems we've got a concurrent play over here.
      while (!$lock->lockMayBeAvailable($lock_id)) {
        // Let's wait for the concurrent play to finish.
        $lock->wait($lock_id);
      }

      return;
    }

    $result = $this->queryFactory->get('ymca_retention_member_chance')
      ->condition('id', $chance->id())
      ->condition('played', 0)
      ->execute();

    // Making sure chance has not been played during the current request.
    if (empty($result)) {
      $this->releaseChanceLock($chance);
      return;
    }

    // Staff is not eligible to win prizes.
    if ($member->isMemberEmployee()) {
      $this->chanceLoss($chance);
      return;
    }

    // Check the excluded branches.
    $branches_settings = $this->configFactory->get('ymca_retention.branches_settings');
    $excluded_branches = $branches_settings->get('excluded_branches');
    if (in_array($member->getBranchId(), $excluded_branches)) {
      $this->chanceLoss($chance);
      return;
    }

    // Try to get the prize.
    if (!$prize = $this->getPrize()) {
      $this->chanceLoss($chance);
    }
    else {
      $this->chanceWin($member, $chance, $prize['value']);
    }
  }

  /**
   * Chance is won.
   *
   * @param Member $member
   *   Member entity.
   * @param MemberChance $chance
   *   Member chance entity.
   * @param int $value
   *   Prize value in dollars.
   */
  public function chanceWin(Member $member, MemberChance $chance, $value) {
    $chance->set('played', time());
    $chance->set('winner', 1);
    $chance->set('value', $value);
    $chance->set('message', 'Won $' . $value . ' card!');

    if ($order = $this->generateTangoCardPrize($member, $value)) {
      $chance->set('order_id', $order->order_id);
    }

    $chance->save();

    // Releasing locks.
    \Drupal::lock()->release('ymca_retention_instant_win:prize_pool');
    $this->releaseChanceLock($chance);
  }

  /**
   * Generate Tango Card Prize for a given value.
   *
   * @param Member $member
   *   Member entity.
   * @param int $value
   *   Prize value in dollars.
   *
   * @return bool|string
   *   False or order id.
   */
  public function generateTangoCardPrize(Member $member, $value) {
    $product_pool = $this->configFactory->get('ymca_retention.instant_win')->get('product_pool_keyed');

    if (!isset($product_pool[$value])) {
      return FALSE;
    }

    // Get random product.
    $sku = $product_pool[$value][array_rand($product_pool[$value])];

    // TODO: inject Tango Card wrapper service in this class.
    $tango_card_wrapper = \Drupal::service('tango_card.tango_card_wrapper');

    try {
      if (!$product = $tango_card_wrapper->getRewardInfo($sku)) {
        return FALSE;
      }

      // Request an order to Tango Card.
      $order = $tango_card_wrapper->placeOrder(
        $member->getFirstName(),
        $member->getEmail(),
        $sku,
        $product->unit_price == -1 ? $value * 100 : NULL
      );
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $order;
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
    $chance->set('message', $this->lossMessage());

    $chance->save();

    // Releasing lock.
    $this->releaseChanceLock($chance);
  }

  /**
   * Get random lost message.
   */
  public function lossMessage() {
    $messages = $this->configFactory->get('ymca_retention.instant_win')->get('loss_messages_short');
    return $messages[array_rand($messages)];
  }

  /**
   * Get the prize.
   */
  public function getPrize() {
    $settings = $this->configFactory->get('ymca_retention.instant_win');
    $percentage = $settings->get('percentage');

    if ($percentage < rand(1, 100)) {
      return FALSE;
    }

    $lock = \Drupal::lock();
    $lock_id = 'ymca_retention_instant_win:prize_pool';

    // Ensuring prize pool consistency by avoiding concurrent prizing.
    while (!$lock->acquire($lock_id)) {
      // Let's wait for the concurrent prize to finish.
      $lock->wait($lock_id);
    }

    // Check if there are any prizes available.
    if (!$prizes = $this->prizesAvailable()) {
      $lock->release($lock_id);
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

    $results = $this->queryFactory->getAggregate('ymca_retention_member_chance')
      ->condition('winner', 1)
      ->groupBy('value')
      ->aggregate('id', 'COUNT')
      ->execute();

    $used_prizes = [];
    foreach ($results as $result) {
      $used_prizes[$result['value']] = $result['id_count'];
    }

    foreach ($prize_pool as $prize) {
      $count = isset($used_prizes[$prize['value']]) ? $used_prizes[$prize['value']] : 0;

      if ($count < $prize['quantity']) {
        $available_prizes[$prize['value']] = $prize['quantity'] - $count;
      }
    }

    return $available_prizes;
  }

  /**
   * Releases chance playing lock.
   *
   * @param MemberChance $chance
   *   Member chance entity.
   */
  protected function releaseChanceLock(MemberChance $chance) {
    $lock = \Drupal::lock();
    $lock->release('ymca_retention_instant_win:' . $chance->id());
  }

}
