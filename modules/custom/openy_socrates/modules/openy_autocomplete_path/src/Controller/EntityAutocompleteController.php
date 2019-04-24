<?php

namespace Drupal\openy_autocomplete_path\Controller;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\openy_autocomplete_path\EntityAutocompleteMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\system\Controller\EntityAutocompleteController as SystemEntityAutocompleteController;

/**
 * Class EntityAutocompleteController.
 *
 * @package Drupal\openy_autocomplete_path\Controller
 */
class EntityAutocompleteController extends SystemEntityAutocompleteController {

  /**
   * The autocomplete matcher for entity references.
   */
  protected $matcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityAutocompleteMatcher $matcher, KeyValueStoreInterface $key_value) {
    parent::__construct($matcher, $key_value);
    $this->matcher = $matcher;
    $this->keyValue = $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openy_autocomplete_path.autocomplete_matcher'),
      $container->get('keyvalue')->get('entity_autocomplete')
    );
  }
}
