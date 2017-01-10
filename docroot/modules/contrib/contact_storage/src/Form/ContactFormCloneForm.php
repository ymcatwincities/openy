<?php

namespace Drupal\contact_storage\Form;

use Drupal\contact\ContactFormEditForm;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\field\Entity\FieldConfig;
use Egulias\EmailValidator\EmailValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for cloning a contact form.
 */
class ContactFormCloneForm extends ContactFormEditForm {

  /**
   * Entity Field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('email.validator'),
      $container->get('path.validator'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Constructs a new ContactFormCloneForm object.
   *
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   Email validator.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   Entity field manager.
   */
  public function __construct(EmailValidator $email_validator, PathValidatorInterface $path_validator, EntityFieldManagerInterface $field_manager) {
    parent::__construct($email_validator, $path_validator);
    $this->fieldManager = $field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Add #process and #after_build callbacks.
    $form['#process'][] = '::processForm';
    $form['#after_build'][] = '::afterBuild';

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => '',
      '#description' => $this->t("Example: 'website feedback' or 'product information'."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => '',
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => array(
        'exists' => '\Drupal\contact\Entity\ContactForm::load',
      ),
    );
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
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\contact\ContactFormInterface $contact_form */
    $contact_form = $this->entity;
    // Get the original ID.
    $original_id = $contact_form->getOriginalId();
    $new_id = $contact_form->id();

    // Create the new form.
    $contact_form = $contact_form->createDuplicate();
    $contact_form->set('id', $new_id);
    $contact_form->save();

    // Clone configurable fields.
    foreach ($this->fieldManager->getFieldDefinitions('contact_message', $original_id) as $field) {
      if ($field instanceof BaseFieldDefinition) {
        continue;
      }
      if ($this->moduleHandler->moduleExists('field')) {
        if ($config = $field->getConfig($original_id)) {
          $new_config = FieldConfig::create([
            'bundle' => $contact_form->id(),
            'uuid' => NULL,
          ] + $config->toArray());
          $new_config->save();
        }
      }
    }

    // Clone the entity form display.
    $display = EntityFormDisplay::load('contact_message.' . $original_id . '.default');
    EntityFormDisplay::create([
      'bundle' => $contact_form->id(),
      'uuid' => NULL,
    ] + $display->toArray())->save();

    // Clone the entity view display.
    $display = EntityViewDisplay::load('contact_message.' . $original_id . '.default');
    EntityViewDisplay::create([
      'bundle' => $contact_form->id(),
      'uuid' => NULL,
    ] + $display->toArray())->save();

    // Redirect and show messge.
    $form_state->setRedirect('entity.contact_form.edit_form', ['contact_form' => $contact_form->id()]);
    $edit_link = $this->entity->link($this->t('Edit'));
    drupal_set_message($this->t('Contact form %label has been added.', array('%label' => $contact_form->label())));
    $this->logger('contact')->notice('Contact form %label has been added.', array('%label' => $contact_form->label(), 'link' => $edit_link));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Clone');
    return $actions;
  }

}
