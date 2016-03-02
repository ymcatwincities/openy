<?php

/**
 * @file
 * Contains \Drupal\contact_storage\ContactFormViewBuilder.
 */

namespace Drupal\contact_storage;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a contact form view builder.
 *
 * @see \Drupal\contact\Entity\ContactForm
 */
class ContactFormViewBuilder implements EntityViewBuilderInterface, EntityHandlerInterface {

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The contact message storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $contactMessageStorage;

  /**
   * Constructs a new contact form view builder.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $contact_message_storage
   *   The contact message storage.
   */
  public function __construct(EntityFormBuilderInterface $entity_form_builder, RendererInterface $renderer, ConfigFactoryInterface $config_factory, EntityStorageInterface $contact_message_storage) {
    $this->entityFormBuilder = $entity_form_builder;
    $this->renderer = $renderer;
    $this->configFactory = $config_factory;
    $this->contactMessageStorage = $contact_message_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity.form_builder'),
      $container->get('renderer'),
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getStorage('contact_message')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $message = $this->contactMessageStorage->create([
      'contact_form' => $entity->id(),
    ]);

    $form = $this->entityFormBuilder->getForm($message);
    $form['#title'] = $entity->label();
    $form['#cache']['contexts'][] = 'user.permissions';

    $config = $this->configFactory->get('contact.settings');
    $this->renderer->addCacheableDependency($form, $config);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    $build = [];
    foreach ($entities as $key => $entity) {
      $build[$key] = $this->view($entity, $view_mode, $langcode);
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $entities = NULL) {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    throw new \LogicException();
  }

  /**
   * {@inheritdoc}
   */
  public function viewField(FieldItemListInterface $items, $display_options = []) {
    throw new \LogicException();
  }

  /**
   * {@inheritdoc}
   */
  public function viewFieldItem(FieldItemInterface $item, $display_options = []) {
    throw new \LogicException();
  }

}
