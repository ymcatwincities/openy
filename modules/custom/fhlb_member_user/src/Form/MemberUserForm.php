<?php

namespace Drupal\fhlb_member_user\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Member user edit forms.
 *
 * @ingroup fhlb_member_user
 */
class MemberUserForm extends ContentEntityForm {

  /**
   * The Current Drupal User.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, AccountProxyInterface $currentUser) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @TODO: For FHLB-248 - we will need to sync the member user/institute.
   * @TODO: FHLB-248 - save the institute ID somewhere to save in submit.
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\fhlb_member_user\Entity\MemberUser $entity */
    $entity = $form_state->getFormObject()->getEntity();
    $member_admin_roles = $entity->getAdminMemberRoles();

    /** @var \Drupal\fhlb_member_institution\Entity\MemberInstitution $institution */
    if ($institution = $entity->field_fhlb_mem_institution->entity) {
      $view_builder = $this->entityTypeManager->getViewBuilder($institution->getEntityTypeId());
      $institution_view = $view_builder->view($institution);
      $form['institution'] = [
        '#markup' => render($institution_view),
        '#weight' => -50,
      ];
    }

    $user_roles = $this->currentUser->getRoles();
    // @TODO: Omitting site admins for now.
    if (in_array('administrator', $user_roles)) {
      return $form;
    }

    // FHLB Admins will only add member admin/third party members.
    if (in_array('fhlb_admin', $user_roles)) {
      $form['field_fhlb_mem_roles']['widget']['#type'] = 'radios';
      foreach ($form['field_fhlb_mem_roles']['widget']['#options'] as $name => $option) {
        if (!in_array($name, $member_admin_roles)) {
          unset($form['field_fhlb_mem_roles']['widget']['#options'][$name]);
        }
      }

      // We changed from checkboxes to radios and the format is different.
      if (empty($form['field_fhlb_mem_roles']['widget']['#default_value'])) {
        $form['field_fhlb_mem_roles']['widget']['#default_value'] = $member_admin_roles[0];
      }
      else {
        $form['field_fhlb_mem_roles']['widget']['#default_value'] = $form['field_fhlb_mem_roles']['widget']['#default_value'][0];
      }

    }
    // Member Admins.
    else {

      // Pull this information from the current member admin.
      $user = $this->entityManager->getStorage('user')->load($this->currentUser->id());

      /** @var \Drupal\fhlb_member_user\Entity\MemberUser $member */
      if ($member = $user->field_fhlb_member_user->entity) {
        $form['cust_id']['widget'][0]['value']['#default_value'] = $member->cust_id->value;
      }

      // Remove Admin-only roles.
      foreach ($form['field_fhlb_mem_roles']['widget']['#options'] as $name => $option) {
        if (in_array($name, $member_admin_roles)) {
          unset($form['field_fhlb_mem_roles']['widget']['#options'][$name]);
        }
      }

      // Disable inherited fields.
      $form['cust_id']['#disabled'] = TRUE;

    }

    // General rules: Existing member users cannot have the following edited.
    if (!$entity->isNew()) {
      $form['cust_id']['#disabled'] = TRUE;
      $form['field_fhlb_member_username']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\fhlb_member_user\Entity\MemberUser $entity */
    $entity = parent::validateForm($form, $form_state);

    if (empty($form_state->getErrors())) {
      // Creating.
      if ($entity->isNew()) {
        // @TODO: Replace this once API is available.
        $this->apiCreateMemberUser($form, $form_state);

        // Member user is good, now create a drupal user.
        $this->generateDrupalUser($form, $form_state);
      }
      // Updating.
      else {
        // @TODO: Replace this once API is available.
        if (!$this->apiUpdateMemberUser()) {
          $form_state->setError($form, 'message from api');
        }
      }
    }
  }

  /**
   * Placeholder until we have an API.
   *
   * @TODO: Replace with API Calls/Validation
   */
  protected function apiCreateMemberUser(array $form, FormStateInterface $form_state) {
    $form_state->setValue('sub', user_password());
  }

  /**
   * Placeholder until we have an API.
   *
   * @TODO: Replace with API Calls/Validation
   */
  protected function apiUpdateMemberUser() {
    return TRUE;
  }

  /**
   * Generates a new drupal user for the member user.
   *
   * @TODO: Once the API exists, the username/pass will need to change.
   */
  protected function generateDrupalUser(array $form, FormStateInterface $form_state) {

    // Do nothing if previous errors.
    if ($form_state->getErrors()) {
      return;
    }

    /** @var \Drupal\fhlb_member_user\Entity\MemberUser $member_user */
    $member_user = $this->buildEntity($form, $form_state);

    /** @var User $user */
    $user = $this->entityTypeManager->getStorage('user')->create([
      'name' => $member_user->email->value,
      'mail' => $member_user->email->value,
      'pass' => $member_user->email->value,
      'status' => 1,
    ]);

    // Member admins may also be users, and need email uniqueness.
    if ($member_user->field_fhlb_mem_roles->target_id == 'member_admin') {

      // Adds '+admin' to email.
      $email_parts = explode('@', $member_user->email->value);
      $admin_email = $email_parts[0] . '+admin@' . $email_parts[1];
      $user->setEmail($admin_email);

      $user->addRole('member_admin');
    }
    elseif ($member_user->field_fhlb_mem_roles->target_id == 'third_party_user') {
      $user->addRole('third_party_user');
    }
    else {
      $user->addRole('member_user');
    }

    // Validate the object.
    $errors = $user->validate();

    // Save and store uid to link to member user in save().
    if ($errors->count() == 0) {
      try {
        $user->save();
      }
      catch (EntityStorageException $e) {
        $form_state->setError($form, $this->t('An error occurred creating a new member user. Please try again or contact an administrator.'));
      }
      $form_state->setTemporaryValue('fhlb_new_uid', $user->id());
    }
    else {
      foreach ($errors as $error) {
        $form_state->setErrorByName($error->getPropertyPath(), $error->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    /** @var \Drupal\fhlb_member_user\Entity\MemberUser $entity */
    $entity = $this->entity;

    switch ($status) {
      case SAVED_NEW:

        // Load the new drupal user and link to the new member user.
        $new_uid = $form_state->getTemporaryValue('fhlb_new_uid');
        $user = $this->entityTypeManager->getStorage('user')->load($new_uid);
        $user->set('field_fhlb_member_user', $entity->id());
        $user->save();

        drupal_set_message($this->t('Created the %label Member user.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Member user.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.member_user.collection');
  }

}
