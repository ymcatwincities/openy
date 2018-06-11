<?php

namespace Drupal\openy_autocomplete_path;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityAutocompleteMatcher as SystemEntityAutocompleteMatcher;

class EntityAutocompleteMatcher extends SystemEntityAutocompleteMatcher {

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

    $entityTypeManager = \Drupal::entityTypeManager();

    $handler = $this->selectionManager->getInstance($options);
    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 50);
      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {
          $entity = $entityTypeManager->getStorage($target_type)->load($entity_id);
          $entity = \Drupal::entityManager()->getTranslationFromContext($entity);;

          $status = '';
          $alias = '';

          if ($entity->getEntityType()->id() == 'node') {
            $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $entity_id);
            $status = ($entity->isPublished()) ? "Published" : "Unpublished";
          }

          $key = $label . ' (' . $entity_id . ')';
          // Strip things like starting/trailing white spaces, line breaks and tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));

          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);

          $label = $label . ' (' . $entity_id . ')';
          if ($alias || $status) {
            $label .= sprintf(' [%s]', implode(' ', [$status, $alias])) . '<hr />';
          }

          $matches[] = ['value' => $key, 'label' => $label];
        }
      }
    }

    return $matches;
  }

}
