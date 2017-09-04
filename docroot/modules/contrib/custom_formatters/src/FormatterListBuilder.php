<?php

namespace Drupal\custom_formatters;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of custom formatter entities.
 *
 * @see \Drupal\custom_formatters\Entity\Formatter
 */
class FormatterListBuilder extends ConfigEntityListBuilder {

  /**
   * Formatter type definitions array.
   *
   * @var array
   */
  protected $formatterTypes;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.custom_formatters.formatter_type')
    );
  }

  /**
   * Constructs a new FormatterListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, FormatterTypeManager $formatter_type_manager) {
    parent::__construct($entity_type, $storage);
    $this->formatterTypes = $formatter_type_manager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['type'] = $this->t('Type');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\custom_formatters\FormatterInterface $formatter */
    $formatter = $entity;

    $row['label'] = $formatter->label();
    // @TODO - Ensure definition is present, probably best dealt with with
    // dependencies.
    $row['type'] = $this->formatterTypes[$formatter->get('type')]['label'];

    return $row + parent::buildRow($formatter);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t('There are no @labels yet. Add a @add_link.', [
      '@label' => $this->entityType->getLabel(),
      '@add_link'   => Link::createFromRoute($this->entityType->getLabel(), 'custom_formatters.add_page')
        ->toString(),
    ]);

    return $build;
  }

}
