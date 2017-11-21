<?php

namespace Drupal\custom_formatters\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\custom_formatters\FormatterExtrasManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the shortcut set entity edit forms.
 */
class FormatterForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\custom_formatters\FormatterInterface
   */
  protected $entity;

  /**
   * Formatter extras plugin manager.
   *
   * @var FormatterExtrasManager
   */
  protected $formatterExtrasManager;

  /**
   * Field formatter plugin manager.
   *
   * @var FormatterPluginManager
   */
  protected $fieldFormatterManager;

  /**
   * Field type plugin manager.
   *
   * @var FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * Constructs a FormatterForm object.
   */
  public function __construct(FormatterExtrasManager $formatter_extras_manager, FormatterPluginManager $field_formatter_manager, FieldTypePluginManagerInterface $field_type_manager) {
    $this->formatterExtrasManager = $formatter_extras_manager;
    $this->fieldTypeManager = $field_type_manager;
    $this->fieldFormatterManager = $field_formatter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.custom_formatters.formatter_extras'),
      $container->get('plugin.manager.field.formatter'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $formatter_type = $this->entity->getFormatterType();

    $form = parent::form($form, $form_state);

    // Show warning if formatter is currently in use.
    $dependent_entities = $this->entity->getDependentEntities();
    if ($dependent_entities) {
      $form['warning'] = [
        '#theme'           => 'status_messages',
        '#message_list'    => [
          'warning' => [
            $this->t("Changing the field type(s) are currently disabled as this formatter is required by the following configuration(s): @config", [
              '@config' => $this->getDependentEntitiesList($dependent_entities),
            ]),
          ],
        ],
        '#status_headings' => [
          'warning' => t('Warning message'),
        ],
      ];
    }

    $form['label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Formatter name'),
      '#description'   => $this->t('This will appear in the administrative interface to easily identify it.'),
      '#required'      => TRUE,
      '#default_value' => $this->entity->label(),
    ];

    $form['id'] = [
      '#type'          => 'machine_name',
      '#machine_name'  => [
        'exists'          => '\Drupal\custom_formatters\Entity\Formatter::load',
        'source'          => ['label'],
        'replace_pattern' => '[^a-z0-9_]+',
        'replace'         => '_',
      ],
      '#default_value' => $this->entity->isNew() ? NULL : $this->entity->id(),
      '#disabled'      => !$this->entity->isNew(),
      '#maxlength'     => 255,
    ];

    $form['type'] = [
      '#type'  => 'value',
      '#value' => $this->entity->get('type'),
    ];

    $form['status'] = [
      '#type'  => 'value',
      '#value' => TRUE,
    ];

    $form['description'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Description'),
      '#default_value' => $this->entity->get('description'),
    ];

    $form['field_types'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Field type(s)'),
      '#options'       => $this->getFieldTypes(),
      '#default_value' => $this->entity->get('field_types'),
      '#required'      => TRUE,
      '#multiple'      => $formatter_type->getPluginDefinition()['multipleFields'],
      '#ajax'          => [
        'callback' => '::formAjax',
        'wrapper'  => 'plugin-wrapper',
      ],
      '#disabled'      => $dependent_entities,
    ];

    // Get Formatter type settings form.
    $plugin_form = [];
    $form['plugin'] = $formatter_type->settingsForm($plugin_form, $form_state);
    $form['plugin']['#type'] = 'container';
    $form['plugin']['#prefix'] = "<div id='plugin-wrapper'>";
    $form['plugin']['#suffix'] = "</div>";

    // Third party integration settings form.
    $extras = $this->getFormatterExtrasForm();
    if ($extras && is_array($extras)) {
      $form['vertical_tabs'] = [
        '#type'    => 'vertical_tabs',
        '#title'   => $this->t('Extras'),
        '#parents' => ['extras'],
      ];

      $form['extras'] = $extras;
      $form['extras']['#tree'] = TRUE;
    }

    return $form;
  }

  /**
   * Returns the settings form for any available third party integrations.
   */
  public function getFormatterExtrasForm() {
    $form = [];

    $definitions = $this->formatterExtrasManager->getDefinitions();
    if (is_array($definitions) && !empty($definitions)) {
      foreach ($definitions as $definition) {
        $extras_form = $this->formatterExtrasManager->invoke($definition['id'], 'settingsForm', $this->entity);

        if (is_array($extras_form) && !empty($extras_form)) {
          // Extras form.
          $form[$definition['id']] = $extras_form;

          // Extras form details element.
          $form[$definition['id']]['#type'] = 'details';
          $form[$definition['id']]['#title'] = $definition['label'];
          $form[$definition['id']]['#description'] = $definition['description'];
          $form[$definition['id']]['#group'] = 'extras';
        }
      }
    }

    return $form;
  }

  /**
   * Ajax callback for form.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form state object.
   *
   * @return mixed
   *   The ajax form element.
   */
  public function formAjax(array $form, FormStateInterface $form_state) {
    return $form['plugin'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->getFormatterType()->submitForm($form, $form_state);

    $entity = $this->entity;
    $is_new = !$entity->getOriginalId();

    // Invoke all third party integrations save method.
    $this->formatterExtrasManager->invokeAll('settingsSave', $entity, $form, $form_state);

    $entity->save();

    // Clear cached formatters.
    // @TODO - Tag custom formatters?
    $this->fieldFormatterManager->clearCachedDefinitions();

    if ($is_new) {
      drupal_set_message($this->t('Added formatter %formatter.', ['%formatter' => $entity->label()]));
    }
    else {
      drupal_set_message($this->t('Updated formatter %formatter.', ['%formatter' => $entity->label()]));
    }
    $form_state->setRedirectUrl(new Url('entity.formatter.collection'));
  }

  /**
   * Returns a list of dependent entities.
   *
   * @param array $entities
   *   The dependent entities.
   *
   * @return mixed|null
   *   The rendered list of dependent entities.
   */
  protected function getDependentEntitiesList(array $entities = []) {
    $list = [];
    foreach ($entities as $entity) {
      $entity_type_id = $entity->getEntityTypeId();
      if (!isset($list[$entity_type_id])) {
        $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
        // Store the ID and label to sort the entity types and entities later.
        $label = $entity_type->getLabel();
        $list[$entity_type_id] = [
          '#theme' => 'item_list',
          '#title' => $label,
          '#items' => [],
        ];
      }
      $list[$entity_type_id]['#items'][$entity->id()] = $entity->label() ?: $entity->id();
    }
    return render($list);
  }

  /**
   * Returns an array of available field types.
   *
   * @TODO - Allow formatter type plugin to modify this list.
   *
   * @return mixed
   *   Array of field types grouped by their providers.
   */
  protected function getFieldTypes() {
    $options = [];

    $field_types = $this->fieldTypeManager->getDefinitions();
    $this->moduleHandler->alter('custom_formatters_fields', $field_types);

    ksort($field_types);
    foreach ($field_types as $field_type) {
      $options[$field_type['provider']][$field_type['id']] = $field_type['label']->render();
    }
    ksort($options);

    return $options;
  }

}
