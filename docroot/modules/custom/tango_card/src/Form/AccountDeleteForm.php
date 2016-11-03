<?php

namespace Drupal\tango_card\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tango_card\TangoCardWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Tango Card account entity.
 *
 * @ingroup tango_card_account
 */
class AccountDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * The Tango Card wrapper.
   *
   * @var \Drupal\tango_card\TangoCardWrapper
   */
  protected $tangoCardWrapper;

  /**
   * Constructs the AccountDeleteForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\tango_card\TangoCardWrapper $tango_card_wrapper
   *   The Tango Card wrapper.
   */
  public function __construct(EntityManagerInterface $entity_manager, TangoCardWrapper $tango_card_wrapper) {
    parent::__construct($entity_manager);
    $this->tangoCardWrapper = $tango_card_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('tango_card.tango_card_wrapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete account %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.tango_card_account.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $this->tangoCardWrapper->setAccount($entity);

    try {
      $success = $this->tangoCardWrapper->deleteCreditCard();
    }
    catch (Exception $e) {
      $success = FALSE;
    }

    if (!$success) {
      drupal_set_message($this->t('An error occurred while unregistering your credit card. Please try again later or contact support.'), 'error');
      return;
    }

    $entity->delete();
    $form_state->setRedirect('entity.tango_card_account.collection');
  }

}
