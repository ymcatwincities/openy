<?php

namespace Drupal\blazy\Dejavu;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Base class for entity reference formatters without field details.
 */
abstract class BlazyEntityBase extends EntityReferenceFormatterBase {

  /**
   * Returns media contents.
   */
  public function buildElements(array &$build, $entities, $langcode) {
    foreach ($entities as $delta => $entity) {
      // Protect ourselves from recursive rendering.
      static $depth = 0;
      $depth++;
      if ($depth > 20) {
        $this->loggerFactory->get('entity')->error('Recursive rendering detected when rendering entity @entity_type @entity_id. Aborting rendering.', ['@entity_type' => $entity->getEntityTypeId(), '@entity_id' => $entity->id()]);
        return $build;
      }

      $build['settings']['delta'] = $delta;
      if ($entity->id()) {
        $this->buildElement($build, $entity, $langcode);

        // Add the entity to cache dependencies so to clear when it is updated.
        $this->manager()->getRenderer()->addCacheableDependency($build['items'][$delta], $entity);
      }
      else {
        $this->referencedEntities = NULL;
        // This is an "auto_create" item.
        $build['items'][$delta] = ['#markup' => $entity->label()];
      }

      $depth = 0;
    }

    // Supports Blazy formatter multi-breakpoint images if available.
    if (empty($build['settings']['vanilla'])) {
      $this->formatter->isBlazy($build['settings'], $build['items'][0]);
    }
  }

  /**
   * Returns item contents.
   */
  public function buildElement(array &$build, $entity, $langcode) {
    $view_mode = empty($build['settings']['view_mode']) ? 'full' : $build['settings']['view_mode'];
    $delta = $build['settings']['delta'];

    $build['items'][$delta] = $this->manager()->getEntityTypeManager()->getViewBuilder($entity->getEntityTypeId())->view($entity, $view_mode, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element    = [];
    $definition = $this->getScopedFormElements();

    $definition['_views'] = isset($form['field_api_classes']);

    $this->admin()->buildSettingsForm($element, $definition);
    return $element;
  }

  /**
   * Defines the scope for the form elements.
   */
  public function getScopedFormElements() {
    $field       = $this->fieldDefinition;
    $entity_type = $field->getTargetEntityTypeId();
    $target_type = $this->getFieldSetting('target_type');
    $views_ui    = $this->getFieldSetting('handler') == 'default';
    $bundles     = $views_ui ? [] : $this->getFieldSetting('handler_settings')['target_bundles'];

    return [
      'current_view_mode' => $this->viewMode,
      'entity_type'       => $entity_type,
      'field_type'        => $field->getType(),
      'field_name'        => $field->getName(),
      'plugin_id'         => $this->getPluginId(),
      'settings'          => $this->getSettings(),
      'target_bundles'    => $bundles,
      'target_type'       => $target_type,
      'view_mode'         => $this->viewMode,
    ];
  }

}
