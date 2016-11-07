<?php

namespace Drupal\tango_card\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\tango_card\TangoCardWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for tango_card_campaign entity.
 *
 * @ingroup tango_card_account
 */
class AccountListBuilder extends EntityListBuilder {

  /**
   * The Tango Card wrapper.
   *
   * @var \Drupal\tango_card\TangoCardWrapper
   */
  protected $tangoCardWrapper;

  /**
   * Construct AccountListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\tango_card\TangoCardWrapper $tango_card_wrapper
   *   The Tango Card wrapper.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, TangoCardWrapper $tango_card_wrapper) {
    parent::__construct($entity_type, $storage);
    $this->tangoCardWrapper = $tango_card_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('tango_card.tango_card_wrapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'id' => $this->t('ID'),
      'remote_id' => $this->t('Remote ID'),
      'customer' => $this->t('Customer'),
      'mail' => $this->t('Email'),
      'balance' => $this->t('Balance'),
      'cc_number' => $this->t('CC Number'),
    ];
    $header += parent::buildHeader();
    $header['orders'] = '';

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $this->tangoCardWrapper->setAccount($entity);

    $balance = $this->tangoCardWrapper->getAccountBalance();
    if ($balance === FALSE) {
      return [];
    }

    $row = [
      'id' => $entity->id(),
      'remote_id' => $entity->label(),
      'customer' => $entity->customer->value,
      'mail' => $entity->mail->value,
      'balance' => '$' . number_format($balance / 100, 2),
      'cc_number' => '************' . $entity->cc_number->value,
    ];

    $row += parent::buildRow($entity);
    unset($row['operations']['data']['#links']['clone']);

    $row['orders'] = new Link($this->t('see orders'), Url::fromRoute('tango_card.orders', ['tango_card_account' => $entity->id()]));

    return $row;
  }

}
