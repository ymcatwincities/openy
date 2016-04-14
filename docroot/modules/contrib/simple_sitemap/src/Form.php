<?php
/**
 * @file
 * Contains \Drupal\simple_sitemap\Form.
 */

namespace Drupal\simple_sitemap;

/**
 * Form class.
 */
class Form {

  const PRIORITY_DEFAULT = 0.5;
  const PRIORITY_HIGHEST = 10;
  const PRIORITY_DIVIDER = 10;

  public $entityType;
  public $entityTypeId;
  public $bundleName;
  public $entityId;

  private $plugins;
  private $formState;
  private $formId;

  /**
   * Form constructor.
   */
  function __construct(&$form, $form_state, $form_id) {
    $this->formId = $form_id;
    $this->formState = $form_state;

    // Get all simple_sitemap plugins.
    $manager = \Drupal::service('plugin.manager.simple_sitemap');
    $this->plugins = $manager->getDefinitions();

    // First look for a plugin declaring usage of this form, if this fails,
    // check if this is a bundle, or bundle instance form and gather the form
    // entity's sitemap settings from the database.
    if (!$this->getEntityDataFromCustomPlugin()) {
      $this->getEntityDataFromForm();
    }
  }


  /**
   * Checks if a plugin defines this form to be used for its entity settings and
   * sets the entity settings and collects those settings.
   *
   * @return bool
   *  TRUE if there is a plugin declaring usage of this form, FALSE otherwise.
   */
  private function getEntityDataFromCustomPlugin() {
    foreach($this->plugins as $plugin) {
      if (isset($plugin['form_id']) && $plugin['form_id'] === $this->formId) {
        $this->entityType = 'custom';
        $this->entityTypeId = $plugin['id'];
        $this->bundleName = $plugin['id'];
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Checks if this particular form is a bundle form, or a bundle instance form
   * and gathers sitemap settings from the database.
   *
   * @return bool
   *  TRUE if this is a bundle or bundle instance form, FALSE otherwise.
   */
  private function getEntityDataFromForm() {
    $form_entity = $this->getFormEntity();
    if ($form_entity !== FALSE) {
      $entity_type = $form_entity->getEntityType();
      if (!empty($entity_type->getBundleEntityType())) {
        $this->entityType = 'bundle_instance';
        $this->entityTypeId = $entity_type->getBundleEntityType();
        $this->bundleName = $form_entity->bundle();
        $this->entityId = $form_entity->id();
        return TRUE;
      }
      else {
        $entity_type_id = $form_entity->getEntityTypeId();
        if (isset($this->plugins[$entity_type_id])) {
          if (!isset($this->plugins[$entity_type_id]['form_id'])
            || $this->plugins[$entity_type_id]['form_id'] === $this->formId) {
            $this->entityType = 'bundle';
            $this->entityTypeId = $entity_type_id;
            $this->bundleName = $form_entity->id();
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Gets the object entity of the form if available.
   *
   * @return object $entity or FALSE if non-existent or if form operation is
   *  'delete'.
   */
  private function getFormEntity() {
    $form_object = $this->formState->getFormObject();
    if (!is_null($form_object)
      && method_exists($form_object, 'getEntity')
      && $form_object->getOperation() !== 'delete') {
      return $form_object->getEntity();
    }
    return FALSE;
  }

  /**
   * Gets new entity Id after entity creation.
   * To be used in an entity form submit.
   *
   * @return int entity ID.
   */
  public static function getNewEntityId($form_state) {
    return $form_state->getFormObject()->getEntity()->id();
  }

  /**
   * Checks if simple_sitemap values have been changed after submitting the form.
   * To be used in an entity form submit.
   *
   * @return bool
   *  TRUE if simple_sitemap form values have been altered by the user.
   */
  public static function valuesChanged($form, $form_state) {
    $values = $form_state->getValues();
    foreach (array('simple_sitemap_index_content', 'simple_sitemap_priority', 'simple_sitemap_regenerate_now') as $field_name) {
      if ($values[$field_name] != $form['simple_sitemap'][$field_name]['#default_value']) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Gets the values needed to display the priority dropdown setting.
   *
   * @return array $options
   */
  public static function getPrioritySelectValues() {
    $options = array();
    foreach(range(0, self::PRIORITY_HIGHEST) as $value) {
      $value = $value / self::PRIORITY_DIVIDER;
      $options[(string)$value] = (string)$value;
    }
    return $options;
  }
}
