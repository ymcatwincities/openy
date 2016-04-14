<?php

/**
 * @file
 * Contains Drupal\page_manager_ui\Form\PageVariantFormBase.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base form for editing and adding a page variant.
 */
abstract class PageVariantFormBase extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\page_manager\PageVariantInterface
   */
  protected $entity;

  /**
   * The variant plugin for this page variant entity.
   *
   * @var \Drupal\Core\Display\VariantInterface
   */
  protected $variantPlugin;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Construct a new PageFormBase.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query factory.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * Returns the text to use for the submit button.
   *
   * @return string
   *   The submit button text.
   */
  abstract protected function submitText();

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('The label for this variant.'),
      '#default_value' => $this->entity->label() ?: (string) $this->getVariantPlugin()->adminLabel(),
      '#maxlength' => '255',
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#disabled' => !$this->entity->isNew(),
      '#default_value' => !$this->entity->isNew() ? $this->entity->id() : '',
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
    ];

    // Allow the variant to add to the form.
    $form['variant_settings'] = $this->getVariantPlugin()->buildConfigurationForm([], $form_state);
    $form['variant_settings']['#tree'] = TRUE;

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Add/Edit',
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Determines if the page variant entity already exists.
   *
   * @param string $id
   *   The page variant entity ID.
   *
   * @return bool
   *   TRUE if the entity exists, FALSE otherwise.
   */
  public function exists($id) {
    return (bool) $this->entityQuery->get('page_variant')
      ->condition('id', $id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    if ($status) {
      drupal_set_message($this->t('Saved the %label variant.', [
        '%label' => $this->entity->label(),
      ]));
    }
    else {
      drupal_set_message($this->t('The %label variant was not saved.', [
        '%label' => $this->entity->label(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Allow the variant to validate the form.
    $variant_plugin_values = (new FormState())->setValues($form_state->getValue('variant_settings'));
    $this->getVariantPlugin()->validateConfigurationForm($form, $variant_plugin_values);
    // Update the original form values.
    $form_state->setValue('variant_settings', $variant_plugin_values->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Allow the variant to submit the form.
    $variant_plugin_values = (new FormState())->setValues($form_state->getValue('variant_settings'));
    $this->getVariantPlugin()->submitConfigurationForm($form, $variant_plugin_values);
    // Update the original form values.
    $form_state->setValue('variant_settings', $variant_plugin_values->getValues());

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the variant plugin for this page variant entity.
   *
   * @return \Drupal\Core\Display\VariantInterface
   */
  protected function getVariantPlugin() {
    if (!$this->variantPlugin) {
      $this->variantPlugin = $this->entity->getVariantPlugin();
    }
    return $this->variantPlugin;
  }

}
