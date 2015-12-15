<?php

/**
 * @file
 * Contains Drupal\contact_storage\Plugin\views\field\ContactForm.
 */

namespace Drupal\contact_storage\Plugin\views\field;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to provide the label of a contact form.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("contact_form")
 */
class ContactForm extends FieldPluginBase {

  /**
   * The storage controller for contact forms.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $formStorage;

  /**
   * Constructs a ContactForm Views field object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigEntityStorageInterface $form_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formStorage = $form_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity.manager')->getStorage('contact_form'));
  }

  /**
   * Render form label.
   */
  protected function renderName($form_id, $values) {
    if ($form_id !== NULL && $form_id !== '') {
      $type = $this->formStorage->load($form_id);
      return $type ? $this->sanitizeValue($type->label()) : '';
    }
    return $this->sanitizeValue($form_id);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderName($value, $values);
  }

}
