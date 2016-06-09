<?php

/**
 * @file
 * Contains \Drupal\entity_browser\Plugin\Field\FieldWidget\EntityReference.
 */

namespace Drupal\entity_browser\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraint;
use Drupal\entity_browser\Events\Events;
use Drupal\entity_browser\Events\RegisterJSCallbacks;
use Drupal\entity_browser\FieldWidgetDisplayManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Plugin implementation of the 'entity_reference' widget for entity browser.

 * @FieldWidget(
 *   id = "entity_browser_entity_reference",
 *   label = @Translation("Entity browser"),
 *   description = @Translation("Uses entity browser to select entities."),
 *   multiple_values = TRUE,
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReference extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Entity manager service
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Field widget display plugin manager.
   *
   * @var \Drupal\entity_browser\FieldWidgetDisplayManager
   */
  protected $fieldDisplayManager;

  /**
   * The depth of the delete button.
   *
   * This property exists so it can be changed if subclasses
   *
   * @var int
   */
  protected static $deleteDepth = 4;

  /**
   * Constructs widget plugin.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Entity manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \Drupal\entity_browser\FieldWidgetDisplayManager $field_display_manager
   *   Field widget display plugin manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityManagerInterface $entity_manager, EventDispatcherInterface $event_dispatcher, FieldWidgetDisplayManager $field_display_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityManager = $entity_manager;
    $this->fieldDisplayManager = $field_display_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity.manager'),
      $container->get('event_dispatcher'),
      $container->get('plugin.manager.entity_browser.field_widget_display')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'entity_browser' => NULL,
      'open' => FALSE,
      'field_widget_display' => NULL,
      'field_widget_edit' => TRUE,
      'field_widget_remove' => TRUE,
      'field_widget_display_settings' => [],
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $browsers = [];
    /** @var \Drupal\entity_browser\EntityBrowserInterface $browser */
    foreach ($this->entityManager->getStorage('entity_browser')->loadMultiple() as $browser) {
      $browsers[$browser->id()] = $browser->label();
    }

    $element['entity_browser'] = [
      '#title' => t('Entity browser'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('entity_browser'),
      '#options' => $browsers,
    ];

    $target_type = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
    $entity_type = \Drupal::entityTypeManager()->getStorage($target_type)->getEntityType();

    $displays = [];
    foreach ($this->fieldDisplayManager->getDefinitions() as $id => $definition) {
      if ($this->fieldDisplayManager->createInstance($id)->isApplicable($entity_type)) {
        $displays[$id] = $definition['label'];
      }
    }

    $id = Html::getUniqueId('field-' . $this->fieldDefinition->getName() . '-display-settings-wrapper');
    $element['field_widget_display'] = [
      '#title' => t('Entity display plugin'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('field_widget_display'),
      '#options' => $displays,
      '#ajax' => [
        'callback' => array($this, 'updateSettingsAjax'),
        'wrapper' => $id,
      ],
    ];

    $element['field_widget_edit'] = [
      '#title' => t('Display Edit button'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('field_widget_edit')
    ];

    $element['field_widget_remove'] = [
      '#title' => t('Display Remove button'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('field_widget_remove')
    ];

    $element['open'] = [
      '#title' => t('Show widget details as open by default'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('open')
    ];

    $element['field_widget_display_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Entity display plugin configuration'),
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $id . '">',
      '#suffix' => '</div>',
    ];

    if ($this->getSetting('field_widget_display')) {
      $element['field_widget_display_settings'] += $this->fieldDisplayManager
        ->createInstance(
          $form_state->getValue(
            ['fields', $this->fieldDefinition->getName(), 'settings_edit_form', 'settings', 'field_widget_display'],
            $this->getSetting('field_widget_display')
          ),
          $form_state->getValue(
            ['fields', $this->fieldDefinition->getName(), 'settings_edit_form', 'settings', 'field_widget_display_settings'],
            $this->getSetting('field_widget_display_settings')
          ) + ['entity_type' => $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type')]
        )
        ->settingsForm($form, $form_state);
    }

    return $element;
  }

  /**
   * Ajax callback that updates field widget display settings fieldset.
   */
  public function updateSettingsAjax(array $form, FormStateInterface $form_state) {
    return $form['fields'][$this->fieldDefinition->getName()]['plugin']['settings_edit_form']['settings']['field_widget_display_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $entity_browser_id = $this->getSetting('entity_browser');
    $field_widget_display = $this->getSetting('field_widget_display');

    if (empty($entity_browser_id)) {
      return [t('No entity browser selected.')];
    }
    else {
      if ($browser = $this->entityManager->getStorage('entity_browser')->load($entity_browser_id)) {
        $summary[] = t('Entity browser: @browser', ['@browser' => $browser->label()]);
      } else {
        drupal_set_message(t('Missing entity browser!'), 'error');
        return [t('Missing entity browser!')];
      }
    }

    if (!empty($field_widget_display)) {
      $plugin = $this->fieldDisplayManager->getDefinition($field_widget_display);
      $summary[] = t('Entity display: @name', ['@name' => $plugin['label']]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    if ($violations->count() > 0) {
      /** @var \Symfony\Component\Validator\ConstraintViolation $violation */
      foreach ($violations as $offset => $violation) {
        // The value of the required field is checked through the "not null"
        // constraint, whose message is not very useful. We override it here for
        // better UX.
        if ($violation->getConstraint() instanceof NotNullConstraint) {
          $violations->set($offset, new ConstraintViolation(
            $this->t('@name field is required.', ['@name' => $items->getFieldDefinition()->getLabel()]),
            '',
            [],
            $violation->getRoot(),
            $violation->getPropertyPath(),
            $violation->getInvalidValue(),
            $violation->getPlural(),
            $violation->getCode(),
            $violation->getConstraint(),
            $violation->getCause()
          ));
        }
      }
    }

    parent::flagErrors($items, $violations, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity_type = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
    $entity_storage = $this->entityManager->getStorage($entity_type);

    $ids = [];
    $entities = [];

    // Determine if we're submitting and if submit came from this widget.
    $is_relevant_submit = FALSE;
    if (($trigger = $form_state->getTriggeringElement())) {
      // Can be triggered by hidden target_id element or "Remove" button.
      if (end($trigger['#parents']) === 'target_id' || (end($trigger['#parents']) === 'remove_button')) {
        $is_relevant_submit = TRUE;

        // In case there are more instances of this widget on the same page we
        // need to check if submit came from this instance.
        $field_name_key = end($trigger['#parents']) === 'target_id' ? 2 : static::$deleteDepth + 1;
        $field_name_key = sizeof($trigger['#parents']) - $field_name_key;
        $is_relevant_submit &= ($trigger['#parents'][$field_name_key] === $this->fieldDefinition->getName());
      }
    };

    if ($is_relevant_submit) {
      // Submit was triggered by hidden "target_id" element when entities were
      // added via entity browser.
      if (!empty($trigger['#ajax']['event']) && $trigger['#ajax']['event'] == 'entity_browser_value_updated') {
        $parents = $trigger['#parents'];
      }
      // Submit was triggered by one of the "Remove" buttons. We need to walk
      // few levels up to read value of "target_id" element.
      elseif ($trigger['#type'] == 'submit' && strpos($trigger['#name'], $this->fieldDefinition->getName() . '_remove_') === 0) {
        $parents = array_merge(array_slice($trigger['#parents'], 0, -static::$deleteDepth), ['target_id']);
      }

      if (isset($parents) && $value = $form_state->getValue($parents)) {
        $ids = explode(' ', $value);
        $entities = $entity_storage->loadMultiple($ids);
      }
    }
    // IDs from a previous request might be saved in the form state.
    elseif ($form_state->has(['entity_browser_widget', $this->fieldDefinition->getName()])) {
      $ids = $form_state->get(['entity_browser_widget', $this->fieldDefinition->getName()]);
      $entities = $entity_storage->loadMultiple($ids);
    }
    // We are loading for for the first time so we need to load any existing
    // values that might already exist on the entity. Also, remove any leftover
    // data from removed entity references.
    else {
      foreach ($items as $item) {
        if (isset($item->target_id)) {
          $entity = $entity_storage->load($item->target_id);
          if (!empty($entity)) {
            $entities[$item->target_id] = $entity;
          }
        }
      }
      $ids = array_keys($entities);
    }
    $ids = array_filter($ids);
    // We store current entity IDs as we might need them in future requests. If
    // some other part of the form triggers an AJAX request with #limit_validation_errors
    // we won't have access to the value of the target_id element and won't be
    // able to build the form as a result of that. This will cause missing
    // submit (Remove, Edit, ...) elements, which might result in unpredictable
    // results.
    $form_state->set(['entity_browser_widget', $this->fieldDefinition->getName()], $ids);

    $hidden_id = Html::getUniqueId('edit-' . $this->fieldDefinition->getName() . '-target-id');
    $details_id = Html::getUniqueId('edit-' . $this->fieldDefinition->getName());
    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity_browser */
    $entity_browser = $this->entityManager->getStorage('entity_browser')->load($this->getSetting('entity_browser'));

    $element += [
      '#id' => $details_id,
      '#type' => 'details',
      '#open' => !empty($ids) || $this->getSetting('open'),
      '#required' => $this->fieldDefinition->isRequired(),
      'target_id' => [
        '#type' => 'hidden',
        '#id' => $hidden_id,
        // We need to repeat ID here as it is otherwise skipped when rendering.
        '#attributes' => ['id' => $hidden_id],
        '#default_value' => $ids,
        // #ajax is officially not supported for hidden elements but if we
        // specify event manually it works.
        '#ajax' => [
          'callback' => [get_class($this), 'updateWidgetCallback'],
          'wrapper' => $details_id,
          'event' => 'entity_browser_value_updated',
        ],
      ],
    ];

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED || count($ids) < $cardinality) {
      $entity_browser_uuid = sha1(implode('-', array_merge($form['#parents'], [$this->fieldDefinition->getName(), $delta])));
      $entity_browser_display = $entity_browser->getDisplay();
      $entity_browser_display->setUuid($entity_browser_uuid);
      $element['entity_browser'] = $entity_browser_display->displayEntityBrowser($form_state);
      $element['#attached']['library'][] = 'entity_browser/entity_reference';
      $element['#attached']['drupalSettings']['entity_browser'] = [
        $entity_browser->getDisplay()->getUuid() => [
          'cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
          'selector' => '#'.$element['target_id']['#attributes']['id'],
        ]
      ];
    }

    $field_parents = $element['#field_parents'];

    $element['current'] = $this->displayCurrentSelection($details_id, $field_parents, $entities);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $ids = empty($values['target_id']) ? [] : explode(' ', trim($values['target_id']));
    $return = [];
    foreach ($ids as $id) {
      $return[]['target_id'] = $id;
    }

    return $return;
  }

  /**
   * AJAX form callback.
   */
  public static function updateWidgetCallback(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    // AJAX requests can be triggered by hidden "target_id" element when entities
    // are added or by one of the "Remove" buttons. Depending on that we need to
    // figure out where root of the widget is in the form structure and use this
    // information to return correct part of the form.
    if (!empty($trigger['#ajax']['event']) && $trigger['#ajax']['event'] == 'entity_browser_value_updated') {
      $parents = array_slice($trigger['#array_parents'], 0, -1);
    }
    elseif ($trigger['#type'] == 'submit' && strpos($trigger['#name'], '_remove_')) {
      $parents = array_slice($trigger['#array_parents'], 0, -static::$deleteDepth);
    }

    return NestedArray::getValue($form, $parents);
  }

  /**
   * Submit callback for remove buttons.
   */
  public static function removeItemSubmit(&$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element['#attributes']['data-entity-id'])) {
      $id = $triggering_element['#attributes']['data-entity-id'];
      $parents = array_slice($triggering_element['#parents'], 0, -static::$deleteDepth);
      $array_parents = array_slice($triggering_element['#array_parents'], 0, -static::$deleteDepth);

      // Find and remove correct entity.
      $values = explode(' ', $form_state->getValue(array_merge($parents, ['target_id'])));
      $values = array_filter(
        $values,
        function($item) use ($id) { return $item != $id; }
      );
      $values = implode(' ', $values);

      // Set new value for this widget.
      $target_id_element = &NestedArray::getValue($form, array_merge($array_parents, ['target_id']));
      $form_state->setValueForElement($target_id_element, $values);
      NestedArray::setValue($form_state->getUserInput(), $target_id_element['#parents'], $values);

      // Rebuild form.
      $form_state->setRebuild();
    }
  }

  /**
   * Builds the render array for displaying the current results.
   *
   * @param string $details_id
   *   The ID for the details element.
   * @param string[] $field_parents
   *   Field parents.
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *
   * @return array
   *   The render array for the current selection.
   */
  protected function displayCurrentSelection($details_id, $field_parents, $entities) {

    $field_widget_display = $this->fieldDisplayManager->createInstance(
      $this->getSetting('field_widget_display'),
      $this->getSetting('field_widget_display_settings') + ['entity_type' => $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type')]
    );

    return [
      '#theme_wrappers' => ['container'],
      '#attributes' => ['class' => ['entities-list']],
      'items' => array_map(
        function (ContentEntityInterface $entity) use ($field_widget_display, $details_id, $field_parents) {
          $display = $field_widget_display->view($entity);
          if (is_string($display)) {
            $display = ['#markup' => $display];
          }
          return [
            '#theme_wrappers' => ['container'],
            '#attributes' => [
              'class' => ['item-container', Html::getClass($field_widget_display->getPluginId())],
              'data-entity-id' => $entity->id()
            ],
            'display' => $display,
            'remove_button' => [
              '#type' => 'submit',
              '#value' => $this->t('Remove'),
              '#ajax' => [
                'callback' => [get_class($this), 'updateWidgetCallback'],
                'wrapper' => $details_id,
              ],
              '#submit' => [[get_class($this), 'removeItemSubmit']],
              '#name' => $this->fieldDefinition->getName() . '_remove_' . $entity->id(),
              '#limit_validation_errors' => [array_merge($field_parents, [$this->fieldDefinition->getName()])],
              '#attributes' => ['data-entity-id' => $entity->id()],
              '#access' => (bool) $this->getSetting('field_widget_remove')
            ],
            'edit_button' => [
              '#type' => 'submit',
              '#value' => $this->t('Edit'),
              '#ajax' => [
                'url' => Url::fromRoute(
                  'entity_browser.edit_form', [
                  'entity_type' => $entity->getEntityTypeId(),
                  'entity' => $entity->id()
                ]
                )
              ],
              '#access' => (bool) $this->getSetting('field_widget_edit')
            ]
          ];
        },
        $entities
      ),
    ];
  }
}
