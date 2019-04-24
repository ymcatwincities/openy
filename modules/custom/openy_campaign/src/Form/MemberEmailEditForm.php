<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the Member Email Edit form.
 *
 * @ingroup openy_campaign_member
 */
class MemberEmailEditForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MemberEmailEditForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_email_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $member_id = NULL) {
    /** @var \Drupal\openy_campaign\Entity\Member $member */
    $member = $this->entityTypeManager->getStorage('openy_campaign_member')->load($member_id);

    $form['#prefix'] = '<div class="container">';
    $form['#suffix'] = '</div>';

    // The block is rendered for each user separately.
    // We can't cache it.
    $form['#cache'] = ['max-age' => 0];

    $form['member'] = [
      '#type' => 'value',
      '#value' => $member,
    ];

    $form['name'] = [
      '#type' => 'item',
      '#title' => 'Name',
      '#title_display' => 'before',
      '#markup' => $member->getFullName(),
    ];

    $form['membership_id'] = [
      '#type' => 'item',
      '#title' => $this->t('Membership ID'),
      '#title_display' => 'before',
      '#markup' => $member->getMemberId(),
    ];

    $form['membership_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Check Member Email Address'),
      '#default_value' => $member->getEmail(),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $member = $form_state->getValue('member');
    $membershipEmail = $form_state->getValue('membership_email');

    $member->setEmail($membershipEmail);
    $member->save();

    // If the member has not previously registered, there will be a basic message "This member is now registered".
    drupal_set_message(t('This member is updated'), 'status', TRUE);

    $form_state->setRedirect('openy_campaign.team_member.list');
  }

}
