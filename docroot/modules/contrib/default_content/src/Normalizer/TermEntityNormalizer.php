<?php

namespace Drupal\default_content\Normalizer;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hal\Normalizer\ContentEntityNormalizer;
use Drupal\rest\LinkManager\LinkManagerInterface;

/**
 * Defines a class for normalizing terms to ensure parent is stored.
 */
class TermEntityNormalizer extends ContentEntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\taxonomy\TermInterface';

  /**
   * Term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(LinkManagerInterface $link_manager, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($link_manager, $entity_manager, $module_handler);
    $this->termStorage = $entity_manager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    if ($parents = $this->termStorage->loadParents($entity->id())) {
      $entity->parent->setValue(array_keys($parents));
    }
    return parent::normalize($entity, $format, $context);
  }

}
