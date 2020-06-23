<?php

namespace Drupal\openy_er\Plugin\EntityReferenceSelection;

use Drupal\Core\Form\FormStateInterface;

/**
 * No dependency Entity Reference Selection plugin delta implementation.
 */
trait SelectionNoDependencyTrait {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // For the 'target_bundles' setting, a NULL value is equivalent to "allow
      // entities from any bundle to be referenced" and an empty array value is
      // equivalent to "no entities from any bundle can be referenced".
      'target_bundles' => NULL,
      'sort' => [
        'field' => '_none',
        'direction' => 'ASC',
      ],
      'target_bundles_no_dep' => NULL,
      'auto_create' => FALSE,
      'auto_create_bundle' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationFormAlter(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $entity_type_id = $configuration['target_type'];
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    $bundles = $this->entityManager->getBundleInfo($entity_type_id);

    if ($entity_type->hasKey('bundle')) {
      $bundle_options = [];
      foreach ($bundles as $bundle_name => $bundle_info) {
        $bundle_options[$bundle_name] = $bundle_info['label'];
      }
      natsort($bundle_options);

      $form['target_bundles_no_dep'] = [
        '#type' => 'checkboxes',
        '#title' => $form['target_bundles']['#title'],
        '#options' => $bundle_options,
        '#default_value' => (array) $configuration['target_bundles_no_dep'],
        '#required' => TRUE,
        '#size' => 6,
        '#multiple' => TRUE,
        '#element_validate' => [[get_class($this), 'elementValidateFilter']],
        '#ajax' => TRUE,
        '#limit_validation_errors' => [],
      ];
    }
    $form['target_bundles'] = [
      '#type' => 'value',
      '#value' => [],
    ];

    if ($entity_type->hasKey('bundle')) {
      $bundles = array_intersect_key($bundle_options, array_filter((array) $configuration['target_bundles_no_dep']));
      $form['auto_create_bundle']['#options'] = $bundles;
      $form['auto_create_bundle']['#access'] = count($bundles) > 1;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    // If no checkboxes were checked for 'target_bundles_no_dep', store NULL ("all
    // bundles are referenceable") rather than empty array ("no bundle is
    // referenceable" - typically happens when all referenceable bundles have
    // been deleted).
    if ($form_state->getValue(['settings', 'handler_settings', 'target_bundles_no_dep']) === []) {
      $form_state->setValue(['settings', 'handler_settings', 'target_bundles_no_dep'], NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    return array_filter($entities, function ($entity) {
      $target_bundles = $this->getConfiguration()['target_bundles_no_dep'];
      if (isset($target_bundles)) {
        return in_array($entity->bundle(), $target_bundles);
      }
      return TRUE;
    });
  }

  /**
   * Builds an EntityQuery to get referenceable entities.
   *
   * @param string|null $match
   *   (Optional) Text to match the label against. Defaults to NULL.
   * @param string $match_operator
   *   (Optional) The operation the matching should be done with. Defaults
   *   to "CONTAINS".
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The EntityQuery object with the basic conditions and sorting applied to
   *   it.
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $configuration = $this->getConfiguration();
    $target_type = $configuration['target_type'];
    $entity_type = $this->entityManager->getDefinition($target_type);

    $query = $this->entityManager->getStorage($target_type)->getQuery();

    // If 'target_bundles_no_dep' is NULL, all bundles are referenceable, no further
    // conditions are needed.
    if (is_array($configuration['target_bundles_no_dep'])) {
      // If 'target_bundles_no_dep' is an empty array, no bundle is referenceable,
      // force the query to never return anything and bail out early.
      if ($configuration['target_bundles_no_dep'] === []) {
        $query->condition($entity_type->getKey('id'), NULL, '=');
        return $query;
      }
      else {
        $query->condition($entity_type->getKey('bundle'), $configuration['target_bundles_no_dep'], 'IN');
      }
    }

    if (isset($match) && $label_key = $entity_type->getKey('label')) {
      $query->condition($label_key, $match, $match_operator);
    }

    // Add entity-access tag.
    $query->addTag($target_type . '_access');

    // Add the Selection handler for system_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addMetaData('entity_reference_selection_handler', $this);

    // Add the sort option.
    if ($configuration['sort']['field'] !== '_none') {
      $query->sort($configuration['sort']['field'], $configuration['sort']['direction']);
    }

    return $query;
  }

}
