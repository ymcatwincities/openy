<?php

namespace Drupal\openy_autocomplete_path;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityAutocompleteMatcher as SystemEntityAutocompleteMatcher;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Class EntityAutocompleteMatcher.
 *
 * @package Drupal\openy_autocomplete_path
 */
class EntityAutocompleteMatcher extends SystemEntityAutocompleteMatcher {

  /**
   * The entity reference selection handler plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Alias manager interface.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Entity Manager.
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a EntityAutocompleteMatcher object.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager
   *   The entity reference selection handler plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   Alias manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   Entity manager.
   */
  public function __construct(SelectionPluginManagerInterface $selection_manager, EntityTypeManagerInterface $entityTypeManager, AliasManagerInterface $aliasManager, EntityTypeManagerInterface $entityManager) {
    parent::__construct($selection_manager);
    $this->selectionManager = $selection_manager;
    $this->entityTypeManager = $entityTypeManager;
    $this->aliasManager = $aliasManager;
    $this->entityManager = $entityManager;
  }

  /**
   * Gets matched labels based on a given search string.
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
    $matches = [];
    $options = [
      'target_type'      => $target_type,
      'handler'          => $selection_handler,
      'handler_settings' => $selection_settings,
    ];

    $handler = $this->selectionManager->getInstance($options);
    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 100);
      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {
          $entity = $this->entityTypeManager->getStorage($target_type)->load($entity_id);
          $entity = $this->entityManager->getTranslationFromContext($entity);

          $status = '';
          $alias = '';

          if ($entity->getEntityType()->id() == 'node') {
            $alias = $this->aliasManager->getAliasByPath('/node/' . $entity_id);
            $status = ($entity->isPublished()) ? t("Published") : t("Unpublished");
          }

          $key = $label . ' (' . $entity_id . ')';
          // Strip things like starting/trailing white spaces, line breaks and tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));

          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);

          $prefix = (empty($matches)) ? '' : '<hr />';
          $label = $prefix . $label;
          if ($alias || $status) {
            $label .= sprintf(' [%s]', implode(', ', array_filter([$status, $alias])));
          }

          $matches[] = ['value' => $key, 'label' => $label];
        }
      }
    }

    return $matches;
  }

}
