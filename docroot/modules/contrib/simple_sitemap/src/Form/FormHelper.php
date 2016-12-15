<?php

namespace Drupal\simple_sitemap\Form;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Class FormHelper.
 *
 * @package Drupal\simple_sitemap\Form
 */
class FormHelper {
  use StringTranslationTrait;

  const PRIORITY_DEFAULT = 0.5;
  const PRIORITY_HIGHEST = 10;
  const PRIORITY_DIVIDER = 10;

  private $generator;
  private $currentUser;
  private $formState;

  public $alteringForm = TRUE;
  public $entityCategory = NULL;
  public $entityTypeId;
  public $bundleName;
  public $instanceId;

  private static $allowedFormOperations = [
    'default',
    'edit',
    'add',
    'register',
  ];

  private static $valuesToCheck = [
    'simple_sitemap_index_content',
    'simple_sitemap_priority',
    'simple_sitemap_regenerate_now',
  ];

  /**
   * Form constructor.
   *
   * @param $generator
   * @param $current_user
   */
  public function __construct(
    Simplesitemap $generator,
    AccountProxyInterface $current_user
  ) {
    $this->generator = $generator;
    $this->currentUser = $current_user;
  }

  /**
   * @param $form_state
   * @return $this
   */
  public function processForm($form_state) {
    $this->formState = $form_state;
    if (!is_null($this->formState)) {
      $this->getEntityDataFromFormEntity();
      $this->assertAlteringForm();
    }
    return $this;
  }

  /**
   * @param $entity_category
   * @return $this
   */
  public function setEntityCategory($entity_category) {
    $this->entityCategory = $entity_category;
    return $this;
  }

  /**
   * @param $entity_type_id
   * @return $this
   */
  public function setEntityTypeId($entity_type_id) {
    $this->entityTypeId = $entity_type_id;
    return $this;
  }

  /**
   * @param $bundle_name
   * @return $this
   */
  public function setBundleName($bundle_name) {
    $this->bundleName = $bundle_name;
    return $this;
  }

  /**
   * @param $instance_id
   * @return $this
   */
  public function setInstanceId($instance_id) {
    $this->instanceId = $instance_id;
    return $this;
  }

  /**
   *
   */
  private function assertAlteringForm() {

    // Do not alter the form if user lacks certain permissions.
    if (!$this->currentUser->hasPermission('administer sitemap settings')) {
      $this->alteringForm = FALSE;
    }

    // Do not alter the form if it is irrelevant to sitemap generation.
    elseif (empty($this->entityCategory)) {
      $this->alteringForm = FALSE;
    }

    // Do not alter the form if entity is not enabled in sitemap settings.
    elseif (!$this->generator->entityTypeIsEnabled($this->entityTypeId)) {
      $this->alteringForm = FALSE;
    }

    // Do not alter the form, if sitemap is disabled for the entity type of this
    // entity instance.
    elseif ($this->entityCategory == 'instance'
      && !$this->generator->bundleIsIndexed($this->entityTypeId, $this->bundleName)) {
      $this->alteringForm = FALSE;
    }
  }

  /**
   * @param $form_fragment
   */
  public function displayRegenerateNow(&$form_fragment) {
    $form_fragment['simple_sitemap_regenerate_now'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Regenerate sitemap after hitting <em>Save</em>'),
      '#description' => $this->t('This setting will regenerate the whole sitemap including the above changes.'),
      '#default_value' => FALSE,
    ];
    if ($this->generator->getSetting('cron_generate')) {
      $form_fragment['simple_sitemap_regenerate_now']['#description'] .= '</br>' . $this->t('Otherwise the sitemap will be regenerated on the next cron run.');
    }
  }

  /**
   * @param $form_fragment
   * @param bool $multiple
   * @return $this
   */
  public function displayEntitySettings(&$form_fragment, $multiple = FALSE) {
    $prefix = $multiple ? $this->entityTypeId . '_' : '';

    if ($this->entityCategory == 'instance') {
      $bundle_settings = $this->generator->getBundleSettings($this->entityTypeId, $this->bundleName);
      $settings = !is_null($this->instanceId) ? $this->generator->getEntityInstanceSettings($this->entityTypeId, $this->instanceId) : $bundle_settings;
    }
    else {
      $settings = $this->generator->getBundleSettings($this->entityTypeId, $this->bundleName);
    }
    $index = isset($settings['index']) ? $settings['index'] : 0;
    $priority = isset($settings['priority']) ? $settings['priority'] : self::PRIORITY_DEFAULT;
    $bundle_name = !empty($this->bundleName) ? $this->bundleName : $this->t('undefined');

    if (!$multiple) {
      $form_fragment[$prefix . 'simple_sitemap_index_content'] = [
        '#type' => 'radios',
        '#default_value' => $index,
        '#options' => [
          0 => $this->entityCategory == 'instance' ? $this->t('Do not index this @bundle entity', ['@bundle' => $bundle_name]) : $this->t('Do not index entities of this type'),
          1 => $this->entityCategory == 'instance' ? $this->t('Index this @bundle entity', ['@bundle' => $bundle_name]) : $this->t('Index entities of this type'),
        ],
      ];
      if ($this->entityCategory == 'instance' && isset($bundle_settings['index'])) {
        $form_fragment[$prefix . 'simple_sitemap_index_content']['#options'][$bundle_settings['index']] .= ' <em>(' . $this->t('Default') . ')</em>';
      }
    }

    if ($this->entityCategory == 'instance') {
      $priority_description = $this->t('The priority this @bundle entity will have in the eyes of search engine bots.', ['@bundle' => $bundle_name]);
    }
    else {
      $priority_description = $this->t('The priority entities of this type will have in the eyes of search engine bots.');
    }
    $form_fragment[$prefix . 'simple_sitemap_priority'] = [
      '#type' => 'select',
      '#title' => $this->t('Priority'),
      '#description' => $priority_description,
      '#default_value' => $priority,
      '#options' => $this->getPrioritySelectValues(),
    ];
    if ($this->entityCategory == 'instance' && isset($bundle_settings['priority'])) {
      $form_fragment[$prefix . 'simple_sitemap_priority']['#options'][$this->formatPriority($bundle_settings['priority'])] .= ' (' . $this->t('Default') . ')';
    }
    return $this;
  }

  /**
   * Checks if this particular form is a bundle form, or a bundle instance form
   * and gathers sitemap settings from the database.
   *
   * @return bool
   *   TRUE if this is a bundle or bundle instance form, FALSE otherwise.
   */
  private function getEntityDataFromFormEntity() {
    $form_entity = $this->getFormEntity();
    if ($form_entity !== FALSE) {
      $entity_type_id = $form_entity->getEntityTypeId();
      $sitemap_entity_types = $this->generator->getSitemapEntityTypes();
      if (isset($sitemap_entity_types[$entity_type_id])) {
        $this->entityCategory = 'instance';
      }
      else {
        foreach ($sitemap_entity_types as $sitemap_entity) {
          if ($sitemap_entity->getBundleEntityType() == $entity_type_id) {
            $this->entityCategory = 'bundle';
            break;
          }
        }
      }

      // Menu fix.
      $this->entityCategory = is_null($this->entityCategory) && $entity_type_id == 'menu' ? 'bundle' : $this->entityCategory;

      switch ($this->entityCategory) {
        case 'bundle':
          $this->entityTypeId = $this->generator->getBundleEntityTypeId($form_entity);
          $this->bundleName = $form_entity->id();
          $this->instanceId = NULL;
          break;

        case 'instance':
          $this->entityTypeId = $entity_type_id;
          $this->bundleName = $this->generator->getEntityInstanceBundleName($form_entity);
          // New menu link's id is '' instead of NULL, hence checking for empty.
          $this->instanceId = !empty($form_entity->id()) ? $form_entity->id() : NULL;
          break;

        default:
          return FALSE;
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Gets the object entity of the form if available.
   *
   * @return object|false
   *   Entity or FALSE if non-existent or if form operation is
   *   'delete'.
   */
  private function getFormEntity() {
    $form_object = $this->formState->getFormObject();
    if (!is_null($form_object)
      && method_exists($form_object, 'getEntity')
      && in_array($form_object->getOperation(), self::$allowedFormOperations)) {
      return $form_object->getEntity();
    }
    return FALSE;
  }

  /**
   * Gets new entity Id after entity creation.
   * To be used in an entity form submit.
   *
   * @return int
   *   Entity ID.
   */
  public function getFormEntityId() {
    return $this->formState->getFormObject()->getEntity()->id();
  }

  /**
   * Checks if simple_sitemap values have been changed after submitting the form.
   * To be used in an entity form submit.
   *
   * @param $form
   * @param $values
   *
   * @return bool
   *   TRUE if simple_sitemap form values have been altered by the user.
   */
  public function valuesChanged($form, $values) {
    foreach (self::$valuesToCheck as $field_name) {
      if (isset($values[$field_name]) && $values[$field_name] != $form['simple_sitemap'][$field_name]['#default_value']) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Gets the values needed to display the priority dropdown setting.
   *
   * @return array
   */
  public function getPrioritySelectValues() {
    $options = [];
    foreach (range(0, self::PRIORITY_HIGHEST) as $value) {
      $value = $this->formatPriority($value / self::PRIORITY_DIVIDER);
      $options[$value] = $value;
    }
    return $options;
  }

  /**
   * @param $priority
   * @return string
   */
  public function formatPriority($priority) {
    return number_format((float) $priority, 1, '.', '');
  }

  /**
   * @param $priority
   * @return bool
   */
  public static function isValidPriority($priority) {
    return is_numeric($priority) && $priority >= 0 && $priority <= 1;
  }
}
