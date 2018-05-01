<?php

namespace Drupal\openy_group_schedules\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class AutocompleteController extends ControllerBase {

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $results = [];
    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $blockManager = \Drupal::service('plugin.manager.block');
      $contextRepository = \Drupal::service('context.repository');

      // Get blocks definition.
      $definitions = $blockManager->getDefinitionsForContexts($contextRepository->getAvailableContexts());
      $options = [];
      foreach ($definitions as $machine_name => $definition) {
        $options[$machine_name] = $definition['admin_label'];
      }

      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));
      // @todo: Apply logic for generating results based on typed_string and other
      // arguments passed.
      foreach ($options as $machine_name => $option) {
        $option_lower = Unicode::strtolower($option);
        if (strpos($option_lower, $typed_string)) {
          $results[] = [
            'value' => $option . ' (' . $machine_name . ')',
            'label' => $option,
          ];
        }
      }
    }

    return new JsonResponse($results);
  }

}
