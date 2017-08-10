<?php

namespace Drupal\fullcalendar\Plugin\views\style;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\Annotation\ViewsStyle;
use Drupal\Core\Annotation\Translation;
use Drupal\fullcalendar\Plugin\FullcalendarPluginCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * @todo.
 *
 * @ViewsStyle(
 *   id = "fullcalendar",
 *   title = @Translation("FullCalendar"),
 *   help = @Translation("Displays items on a calendar."),
 *   theme = "fullcalendar",
 *   theme_file = "fullcalendar.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class FullCalendar extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Stores the FullCalendar plugins used by this style plugin.
   *
   * @var \Drupal\fullcalendar\Plugin\FullcalendarPluginCollection;
   */
  protected $pluginBag;

  /**
   * {@inheritdoc}
   */
  public function evenEmpty() {
    return TRUE;
  }

  /**
   * @todo.
   *
   * @return \Drupal\fullcalendar\Plugin\FullcalendarPluginCollection;|\Drupal\fullcalendar\Plugin\FullcalendarInterface[]
   */
  public function getPlugins() {
    return $this->pluginBag;
  }

  /**
   * Constructs a new Fullcalendar object.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Component\Plugin\PluginManagerInterface $fullcalendar_manager
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PluginManagerInterface $fullcalendar_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->pluginBag = new FullcalendarPluginCollection($fullcalendar_manager, $this);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.fullcalendar'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    /* @var \Drupal\fullcalendar\Plugin\fullcalendar\type\FullCalendar $plugin */
    $options = parent::defineOptions();
    foreach ($this->getPlugins() as $plugin) {
      $options += $plugin->defineOptions();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    /* @var \Drupal\fullcalendar\Plugin\fullcalendar\type\FullCalendar $plugin */
    parent::buildOptionsForm($form, $form_state);
    foreach ($this->getPlugins() as $plugin) {
      $plugin->buildOptionsForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    // Cast all submitted values to their proper type.
    // @todo Remove once https://drupal.org/node/1653026 is in.
    if ($form_state->getValue('style_options')) {
      $this->castNestedValues($form_state->getValue('style_options'), $form);
    }
  }

  /**
   * Casts form values to a given type, if defined.
   *
   * @param array $values
   *   An array of fullcalendar option values.
   * @param array $form
   *   The fullcalendar option form definition.
   * @param string|null $current_key
   *   (optional) The current key being processed. Defaults to NULL.
   * @param array $parents
   *   (optional) An array of parent keys when recursing through the nested
   *   array. Defaults to an empty array.
   */
  protected function castNestedValues(array &$values, array $form, $current_key = NULL, array $parents = array()) {
    foreach ($values as $key => &$value) {
      // We are leaving a recursive loop, remove the last parent key.
      if (empty($current_key)) {
        array_pop($parents);
      }

      // In case we recurse into an array, or need to specify the key for
      // drupal_array_get_nested_value(), add the current key to $parents.
      $parents[] = $key;

      if (is_array($value)) {
        // Enter another recursive loop.
        $this->castNestedValues($value, $form, $key, $parents);
      }
      else {
        // Get the form definition for this key.
        $form_value = NestedArray::getValue($form, $parents);
        // Check to see if #data_type is specified, if so, cast the value.
        if (isset($form_value['#data_type'])) {
          settype($value, $form_value['#data_type']);
        }
        // Remove the current key from $parents to move on to the next key.
        array_pop($parents);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    /* @var \Drupal\fullcalendar\Plugin\fullcalendar\type\FullCalendar $plugin */
    parent::submitOptionsForm($form, $form_state);
    foreach ($this->getPlugins() as $plugin) {
      $plugin->submitOptionsForm($form, $form_state);
    }
  }

  /**
   * @todo.
   */
  public function parseFields($include_gcal = TRUE) {
    $this->view->initHandlers();
    $labels = $this->displayHandler->getFieldLabels();
    $date_fields = array();
    foreach ($this->view->field as $id => $field) {
      if (fullcalendar_field_is_date($field, $include_gcal)) {
        $date_fields[$id] = $labels[$id];
      }
    }
    return $date_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    if ($this->displayHandler->display['display_plugin'] != 'default' && !$this->parseFields()) {
      drupal_set_message($this->t('Display "@display" requires at least one date field.', array('@display' => $this->displayHandler->display['display_title'])), 'error');
    }
    return parent::validate();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    if (empty($this->view->fullcalendar_ajax)) {
      $this->options['#attached'] = $this->prepareAttached();
    }

    return array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#rows' => $this->prepareEvents(),
      '#options' => $this->options,
    );
  }

  /**
   * Load libraries.
   */
  protected function prepareAttached() {
    /* @var \Drupal\fullcalendar\Plugin\fullcalendar\type\FullCalendar $plugin */
    $attached['attach']['library'][] = 'fullcalendar/drupal.fullcalendar';

    foreach ($this->getPlugins() as $plugin_id => $plugin) {
      $definition = $plugin->getPluginDefinition();
      foreach (array('css', 'js') as $type) {
        if ($definition[$type]) {
          $attached['attach']['library'][] = 'fullcalendar/drupal.fullcalendar.' . $type;
        }
      }
    }

    if ($this->displayHandler->getOption('use_ajax')) {
      $attached['attach']['library'][] = 'fullcalendar/drupal.fullcalendar.ajax';
    }

    $attached['attach']['drupalSettings']['fullcalendar'] = ['.js-view-dom-id-' . $this->view->dom_id => $this->prepareSettings()];

    return $attached['attach'];
  }

  /**
   * @todo.
   */
  protected function prepareSettings() {
    /* @var \Drupal\fullcalendar\Plugin\fullcalendar\type\FullCalendar $plugin */
    $settings = array();
    $weights = array();
    $delta = 0;
    foreach ($this->getPlugins() as $plugin_id => $plugin) {
      $definition = $plugin->getPluginDefinition();
      $plugin->process($settings);
      if (isset($definition['weight']) && !isset($weights[$definition['weight']])) {
        $weights[$definition['weight']] = $plugin_id;
      }
      else {
        while (isset($weights[$delta])) {
          $delta++;
        }
        $weights[$delta] = $plugin_id;
      }
    }
    ksort($weights);
    $settings['weights'] = array_values($weights);
    // @todo.
    $settings['fullcalendar']['disableResizing'] = TRUE;
    return $settings;
  }

  /**
   * @todo.
   */
  protected function prepareEvents() {
    /* @var \Drupal\views\Plugin\views\field\Field $field */
    $events = array();
    foreach ($this->view->result as $delta => $row) {
      // Collect all fields for the customize options.
      $fields = array();
      // Collect only date fields.
      $date_fields = array();
      foreach ($this->view->field as $field_name => $field) {
        $fields[$field_name] = $this->getField($delta, $field_name);
        if (fullcalendar_field_is_date($field)) {
          $field_storage_definitions = \Drupal::entityManager()->getFieldStorageDefinitions($field->definition['entity_type']);
          $field_definition = $field_storage_definitions[$field->definition['field_name']];
          $date_fields[$field_name] = array(
            'value' => $field->getItems($row),
            'field_alias' => $field->field_alias,
            'field_name' => $field_definition->getName(),
            'field_info' => $field_definition,
          );
        }
      }

      // If using a custom date field, filter the fields to process.
      if (!empty($this->options['fields']['date'])) {
        $date_fields = array_intersect_key($date_fields, $this->options['fields']['date_field']);
      }

      // If there are no date fields (gcal only), return.
      if (empty($date_fields)) {
        return $events;
      }

      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $row->_entity;
      $classes = $this->moduleHandler->invokeAll('fullcalendar_classes', array($entity));
      $this->moduleHandler->alter('fullcalendar_classes', $classes, $entity);
      $classes = array_map(['\Drupal\Component\Utility\Html', 'getClass'], $classes);
      $class = (count($classes)) ? implode(' ', array_unique($classes)) : '';

      $event = array();
      foreach ($date_fields as $field) {
        // Filter fields without value.
        if (empty($field['value'])) {
          continue;
        }

        foreach ($field['value'] as $index => $item) {
          $start = $item['raw']->value;
          $end = $item['raw']->end_value;

          $all_day = FALSE;

          // Add a class if the event was in the past or is in the future, based
          // on the end time. We can't do this in hook_fullcalendar_classes()
          // because the date hasn't been processed yet.
          if (($all_day && strtotime($start) < strtotime('today')) || (!$all_day && strtotime($end) < REQUEST_TIME)) {
            $time_class = 'fc-event-past';
          }
          elseif (strtotime($start) > REQUEST_TIME) {
            $time_class = 'fc-event-future';
          }
          else {
            $time_class = 'fc-event-now';
          }

          $url = $entity->urlInfo();
          $url->setOption('attributes', array(
            'data-all-day' => $all_day,
            'data-start' => $start,
            'data-end' => $end,
            'data-editable' => (int) TRUE, //$entity->editable,
            'data-field' => $field['field_name'],
            'data-index' => $index,
            'data-eid' => $entity->id(),
            'data-entity-type' => $entity->getEntityTypeId(),
            'data-cn' => $class . ' ' . $time_class,
            'title' => strip_tags(htmlspecialchars_decode($entity->label(), ENT_QUOTES)),
            'class' => array('fullcalendar-event-details'),
          ));

          $event[] = $url->toRenderArray() + array(
            '#type' => 'link',
            '#title' => $item['raw']->value,
          );
        }
      }

      if (!empty($event)) {
        $events[$delta] = array(
          '#theme' => 'fullcalendar_event',
          '#event' => $event,
          '#entity' => $entity,
        );
      }
    }

    return $events;
  }
}
