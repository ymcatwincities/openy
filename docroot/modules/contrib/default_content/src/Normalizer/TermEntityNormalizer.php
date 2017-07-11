<?php

namespace Drupal\default_content\Normalizer;

use Drupal\hal\Normalizer\ContentEntityNormalizer;

/**
 * Defines a class for normalizing terms to ensure parent is stored.
 */
class TermEntityNormalizer extends ContentEntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\taxonomy\TermInterface';

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    if ($parents = $this->getTermStorage()->loadParents($entity->id())) {
      $entity->parent->setValue(array_keys($parents));
    }
    return parent::normalize($entity, $format, $context);
  }

  /**
   * Returns taxonomy term storage.
   *
   * Prevents circular reference when used with multiversion.
   *
   * @return \Drupal\taxonomy\TermStorageInterface
   *   The taxonomy term storage.
   */
  protected function getTermStorage() {
    if (!$this->termStorage) {
      $this->termStorage = $this->entityManager->getStorage('taxonomy_term');
    }
    return $this->termStorage;
  }

}
