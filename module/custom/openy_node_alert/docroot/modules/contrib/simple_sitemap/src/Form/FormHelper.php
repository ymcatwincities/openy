<?php

namespace Drupal\simple_sitemap\Form;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Form\FormState;

/**
 * Class FormHelper
 * @package Drupal\simple_sitemap\Form
 */
class FormHelper {
  use StringTranslationTrait;

  const PRIORITY_DEFAULT = 0.5;
  const PRIORITY_HIGHEST = 10;
  const PRIORITY_DIVIDER = 10;

  /**
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  protected $generator;

  /**
   * @var \Drupal\simple_sitemap\EntityHelper
   */
  protected $entityHelper;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Form\FormState
   */
  protected $formState;

  /**
   * @var bool
   */
  protected $alteringForm = TRUE;

  /**
   * @var string|null
   */
  protected $entityCategory = NULL;

  /**
   * @var string
   */
  protected $entityTypeId;

  /**
   * @var string
   */
  protected $bundleName;

  /**
   * @var string
   */
  protected $instanceId;

  protected static $allowedFormOperations = [
    'default',
    'edit',
    'add',
    'register',
  ];

  protected static $valuesToCheck = [
    'simple_sitemap_index_content',
    'simple_sitemap_priority',
    'simple_sitemap_regenerate_now',
  ];

  /**
   * FormHelper constructor.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   * @param \Drupal\simple_sitemap\EntityHelper $entityHelper
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   */
  public function __construct(
    Simplesitemap $generator,
    EntityHelper $entityHelper,
    AccountProxyInterface $current_user
  ) {
    $this->generator = $generator;
    $this->entityHelper = $entityHelper;
    $this->currentUser = $current_user;
  }

  /**
   * @param \Drupal\Core\Form\FormState $form_state
   * @return $this
   */
  public function processForm(FormState $form_state) {
    $this->formState = $form_state;
    $this->getEntityDataFromFormEntity();
    $this->assertAlteringForm();
    return $this;
  }

  /**
   * @return bool
   */
  public function alteringForm() {
    return $this->alteringForm;
  }

  /**
   * @param string $entity_category
   * @return $this
   */
  public function setEntityCategory($entity_category) {
    $this->entityCategory = $entity_category;
    return $this;
  }

  /**
   * @return null|string
   */
  public function getEntityCategory() {
    return $this->entityCategory;
  }

  /**
 * @param string $entity_type_id
 * @return $this
 */
  public function setEntityTypeId($entity_type_id) {
    $this->entityTypeId = $entity_type_id;
    return $this;
  }

  /**
   * @return string
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * @param string $bundle_name
   * @return $this
   */
  public function setBundleName($bundle_name) {
    $this->bundleName = $bundle_name;
    return $this;
  }

  /**
   * @return string
   */
  public function getBundleName() {
    return $this->bundleName;
  }

  /**
   * @param string $instance_id
   * @return $this
   */
  public function setInstanceId($instance_id) {
    $this->instanceId = $instance_id;
    return $this;
  }

  /**
   * @return string
   */
  public function getInstanceId() {
    return $this->instanceId;
  }

  /**
   *
   */
  protected function assertAlteringForm() {

    // Do not alter the form if user lacks certain permissions.
    if (!$this->currentUser->hasPermission('administer sitemap settings')) {
      $this->alteringForm = FALSE;
    }

    // Do not alter the form if it is irrelevant to sitemap generation.
    elseif (empty($this->getEntityCategory())) {
      $this->alteringForm = FALSE;
    }

    // Do not alter the form if entity is not enabled in sitemap settings.
    elseif (!$this->generator->entityTypeIsEnabled($this->getEntityTypeId())) {
      $this->alteringForm = FALSE;
    }

    // Do not alter the form, if sitemap is disabled for the entity type of this
    // entity instance.
    elseif ($this->getEntityCategory() == 'instance'
      && !$this->generator->bundleIsIndexed($this->getEntityTypeId(), $this->getBundleName())) {
      $this->alteringForm = FALSE;
    }
  }

  /**
   * @param array $form_fragment
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
   * @param array $form_fragment
   * @param bool $multiple
   * @return $this
   */
  public function displayEntitySettings(&$form_fragment, $multiple = FALSE) {
    $prefix = $multiple ? $this->getEntityTypeId() . '_' : '';

    if ($this->getEntityCategory() == 'instance') {
      $bundle_settings = $this->generator->getBundleSettings($this->getEntityTypeId(), $this->getBundleName());
      $settings = NULL !== $this->getInstanceId() ? $this->generator->getEntityInstanceSettings($this->getEntityTypeId(), $this->getInstanceId()) : $bundle_settings;
    }
    else {
      $settings = $this->generator->getBundleSettings($this->getEntityTypeId(), $this->getBundleName());
    }
    $index = isset($settings['index']) ? $settings['index'] : 0;
    $priority = isset($settings['priority']) ? $settings['priority'] : self::PRIORITY_DEFAULT;
    $bundle_name = !empty($this->getBundleName()) ? $this->getBundleName() : $this->t('undefined');

    if (!$multiple) {
      $form_fragment[$prefix . 'simple_sitemap_index_content'] = [
        '#type' => 'radios',
        '#default_value' => $index,
        '#options' => [
          0 => $this->getEntityCategory() == 'instance' ? $this->t('Do not index this @bundle entity', ['@bundle' => $bundle_name]) : $this->t('Do not index entities of this type'),
          1 => $this->getEntityCategory() == 'instance' ? $this->t('Index this @bundle entity', ['@bundle' => $bundle_name]) : $this->t('Index entities of this type'),
        ],
      ];
      if ($this->getEntityCategory() == 'instance' && isset($bundle_settings['index'])) {
        $form_fragment[$prefix . 'simple_sitemap_index_content']['#options'][$bundle_settings['index']] .= ' <em>(' . $this->t('Default') . ')</em>';
      }
    }

    if ($this->getEntityCategory() == 'instance') {
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
    if ($this->getEntityCategory() == 'instance' && isset($bundle_settings['priority'])) {
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
  protected function getEntityDataFromFormEntity() {
    $form_entity = $this->getFormEntity();
    if ($form_entity !== FALSE) {
      $entity_type_id = $form_entity->getEntityTypeId();
      $sitemap_entity_types = $this->entityHelper->getSitemapEntityTypes();
      if (isset($sitemap_entity_types[$entity_type_id])) {
        $this->setEntityCategory('instance');
      }
      else {
        foreach ($sitemap_entity_types as $sitemap_entity) {
          if ($sitemap_entity->getBundleEntityType() == $entity_type_id) {
            $this->setEntityCategory('bundle');
            break;
          }
        }
      }

      // Menu fix.
      $this->setEntityCategory(NULL === $this->getEntityCategory() && $entity_type_id == 'menu' ? 'bundle' : $this->getEntityCategory());

      switch ($this->getEntityCategory()) {
        case 'bundle':
          $this->setEntityTypeId($this->entityHelper->getBundleEntityTypeId($form_entity));
          $this->setBundleName($form_entity->id());
          $this->setInstanceId(NULL);
          break;

        case 'instance':
          $this->setEntityTypeId($entity_type_id);
          $this->setBundleName($this->entityHelper->getEntityInstanceBundleName($form_entity));
          // New menu link's id is '' instead of NULL, hence checking for empty.
          $this->setInstanceId(!empty($form_entity->id()) ? $form_entity->id() : NULL);
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
  protected function getFormEntity() {
    $form_object = $this->formState->getFormObject();
    if (NULL !== $form_object
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
   * @param array $values
   *
   * @return bool
   *   TRUE if simple_sitemap form values have been altered by the user.
   */
  public function valuesChanged($form, array $values) {
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
   * @param string $priority
   * @return string
   */
  public function formatPriority($priority) {
    return number_format((float) $priority, 1, '.', '');
  }

  /**
   * @param string|int $priority
   * @return bool
   */
  public static function isValidPriority($priority) {
    return is_numeric($priority) && $priority >= 0 && $priority <= 1;
  }
}
